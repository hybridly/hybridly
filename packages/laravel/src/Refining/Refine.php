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

    protected ?RefinementState $baseline = null;

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

        if (! $query instanceof Builder) {
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

    /** Replaces defaults declared by filters and sorts with the given baseline. */
    public function withBaseline(?RefinementState $baseline): static
    {
        $this->baseline = $baseline;

        return $this;
    }

    public function getBaseline(): ?RefinementState
    {
        return $this->baseline;
    }

    public function hasReplacementBaseline(): bool
    {
        return $this->baseline !== null;
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

    /** Validates and normalizes persisted state against the configured refiners. */
    public function normalizeState(RefinementState $state): RefinementState
    {
        $filters = collect($this->getFilters())
            ->keyBy(
                fn (BaseFilter $filter): string => $filter->getName(),
            );
        $sorts = collect($this->getSorts())
            ->keyBy(
                fn (BaseSort $sort): string => $sort->getName(),
            );

        foreach ($state->filters as $name => $filterState) {
            $filter = $filters->get($name);

            if (! $filter instanceof BaseFilter) {
                throw new InvalidArgumentException("Unknown filter [{$name}].");
            }

            $filter->setRefineInstance($this);
            $normalizedFilters[$name] = $filter->normalizeState($filterState);
        }

        foreach ($state->sorts as $sortState) {
            $sort = $sorts->get($sortState->name);

            if (! $sort instanceof BaseSort) {
                throw new InvalidArgumentException("Unknown sort [{$sortState->name}].");
            }

            $normalizedSorts[] = $sort->normalizeState($sortState);
        }

        return new RefinementState(
            filters: $normalizedFilters ?? [],
            sorts: $normalizedSorts ?? [],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'sorts' => $this->getSorts(),
            'filters' => $this->getFilters(),
            'scope' => $this->formatScope(),
            'keys' => [
                'sorts' => $this->formatScope($this->getSortsKey()),
                'sorts_cleared' => $this->formatScope($this->getSortsClearedKey()),
                'filters' => $this->formatScope($this->getFiltersKey()),
            ],
        ];
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) {
            self::class => [$this],
            default => [],
        };
    }
}
