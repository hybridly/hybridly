<?php

namespace Hybridly\Refining;

use Hybridly\Components;
use Hybridly\Refining\Contracts\Refiner;
use Hybridly\Refining\Filters\BaseFilter;
use Hybridly\Refining\Sorts\BaseSort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\ForwardsCalls;
use InvalidArgumentException;

/** @mixin Builder */
class Refine extends Components\Component
{
    use Components\Concerns\HasScope;
    use Concerns\ConfiguresKeys;
    use Concerns\HasBuilderInstance;
    use Concerns\HasGroup;
    use Concerns\HasRefiners;
    use Concerns\HasRequest;
    use ForwardsCalls;

    final public function __construct(Request $request)
    {
        $this->setRequest($request);
    }

    public function __call($name, $arguments)
    {
        // Applies refiners at the last possible moment, when the
        // underlying builder instance is called and the query is ran.
        $this->applyRefiners();

        return $this->forwardDecoratedCallTo($this->getBuilderInstance(), $name, $arguments);
    }

    /**
     * Refines the given model.
     */
    public static function model(Model|string $model): static
    {
        return static::query($model);
    }

    /**
     * Refines the given query.
     */
    public static function query(Model|string|Builder $query): static
    {
        if ($query instanceof Model) {
            $query = $query::query();
        }

        if (\is_string($query) && class_exists($query) && is_subclass_of($query, Model::class)) {
            $query = $query::query();
        }

        if (!$query instanceof Builder) {
            throw new InvalidArgumentException('Expected a model class name or a query instance.');
        }

        return resolve(static::class)->setBuilderInstance($query);
    }

    /**
     * Gets the serialized refinements for the front-end to use.
     */
    public function refinements(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Adds the given filters or sorts to the refiner.
     */
    public function with(iterable $refiners): static
    {
        return $this->addRefiners($refiners);
    }

    public function getSorts(): array
    {
        return collect($this->getRefiners())
            ->flatMap(fn (Refiner $refiner) => $refiner instanceof Group ? $refiner->getRefiners() : [$refiner])
            ->filter(fn (Refiner $refiner) => $refiner instanceof BaseSort)
            ->values()
            ->toArray();
    }

    public function getFilters(): array
    {
        return collect($this->getRefiners())
            ->flatMap(fn (Refiner $refiner) => $refiner instanceof Group ? $refiner->getRefiners() : [$refiner])
            ->filter(fn (Refiner $refiner) => $refiner instanceof BaseFilter)
            ->values()
            ->toArray();
    }

    public function jsonSerialize(): array
    {
        return [
            'sorts' => $this->getSorts(),
            'filters' => $this->getFilters(),
            'scope' => $this->formatScope(),
            'keys' => [
                'sorts' => $this->formatScope($this->getSortsKey()),
                'filters' => $this->formatScope($this->getFiltersKey()),
            ],
        ];
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) {
            self::class => [$this],
            default => []
        };
    }
}
