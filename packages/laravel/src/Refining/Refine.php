<?php

namespace Hybridly\Refining;

use Hybridly\Components;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\ForwardsCalls;
use InvalidArgumentException;

/** @mixin Builder */
class Refine extends Components\Component
{
    use Components\Concerns\HasScope;
    use Concerns\HasBuilderInstance;
    use Concerns\HasRefiners;
    use Concerns\HasRequest;
    use Concerns\HasSorts;
    use ForwardsCalls;

    final public function __construct(Request $request)
    {
        $this->setRequest($request);
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
     * Adds the given filters or sorts to the refiner.
     */
    public function with(iterable $refiners): static
    {
        return $this->addRefiners($refiners);
    }

    public function __call($name, $arguments)
    {
        // Applies refiners at the last possible moment, when the
        // underlying builder instance is called and the query is ran.
        $this->applyRefiners();

        return $this->forwardCallTo($this->getBuilderInstance(), $name, $arguments);
    }

    protected function getDefaultEvaluationParameters(): array
    {
        return [
            'filters' => $this,
        ];
    }

    public function jsonSerialize(): array
    {
        // TODO
        return [];
    }
}
