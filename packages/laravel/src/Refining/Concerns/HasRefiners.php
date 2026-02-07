<?php

namespace Hybridly\Refining\Concerns;

use Hybridly\Refining\Filters\Operator;
use Hybridly\Refining\Filters\QueryFilter;
use Hybridly\Refining\Refine;
use Hybridly\Refining\Sorts\BaseSort;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;

/** @mixin Refine */
trait HasRefiners
{
    protected array $refiners = [];
    protected ?\Closure $getFilterValueFromRequestCallback = null;
    protected bool $refined = false;

    public function addRefiners(iterable $refiners): static
    {
        if ($refiners instanceof Arrayable) {
            $refiners = $refiners->toArray();
        }

        $this->refiners = array_merge($this->refiners, $refiners);

        return $this;
    }

    /**
     * Applies refiner. Automatically called when accessing the underlying query.
     */
    public function applyRefiners(): static
    {
        if ($this->refined) {
            return $this;
        }

        foreach ($this->getRefiners() as $refiner) {
            $refiner->refine($this, $this->getBuilderInstance());
        }

        $this->refined = true;

        return $this;
    }

    public function hasOtherSorts(BaseSort $sort): bool
    {
        return collect(explode(',', $this->getRequest()->get($this->formatScope($this->getSortsKey()))))
            ->filter()
            ->contains(fn (string $sortName) => ltrim($sortName, '-') !== ($sort->getAlias() ?? $sort->getProperty()));
    }

    public function getSortDirectionFromRequest(BaseSort $sort): ?string
    {
        $callback = static function (Request $request, string $scope, string $property, ?string $alias) {
            $sorts = collect(explode(',', $request->get($scope)));
            $property = $alias ?? $property;

            return $sorts->first(fn (string $sort) => ltrim($sort, '-') === $property);
        };

        $sort = $this->evaluate(
            value: $callback,
            named: [
                'request' => $this->getRequest(),
                'scope' => $this->formatScope($this->getSortsKey()),
                'property' => $sort->getProperty(),
                'alias' => $sort->getAlias(),
            ],
            typed: [
                Request::class => $this->getRequest(),
            ],
        );

        // If we didn't get a sort value, there is no sort.
        if (! $sort) {
            return null;
        }

        // Otherwise, we infer the direction depending on the presence of `-`.
        return '-' === $sort[0]
            ? 'desc'
            : 'asc';
    }

    /**
     * Gets the filter value for the given property from the request. If an alias is provided, it will be used instead of the property name to look for the value in the request. Returns null if no value is found, or a QueryFilter with the default value if provided.
     */
    public function getQueryFilterFromRequest(string $property, ?string $alias = null, mixed $default = null): ?QueryFilter
    {
        $callback = static function (Request $request, string $scope, string $property, ?string $alias, mixed $default) {
            $filters = $request->array($scope);
            $key = $alias ?? $property;

            if (! isset($filters[$key])) {
                return $default !== null ? new QueryFilter(value: $default) : null;
            }

            $value = data_get($filters, "{$key}.value");
            $operator = data_get($filters, "{$key}.operator");

            if ($operator) {
                $operator = Operator::tryFrom($operator);
            }

            return new QueryFilter(
                value: $value === 'null' ? null : $value,
                search: data_get($filters, "{$key}.search"),
                operator: $operator,
                options: data_get($filters, "{$key}.options", default: []),
            );
        };

        return $this->evaluate($callback, [
            'request' => $this->getRequest(),
            'scope' => $this->formatScope($this->getFiltersKey()),
            'property' => $property,
            'alias' => $alias,
            'default' => $default,
        ]);
    }

    /**
     * @return array<Refiner>
     */
    protected function getRefiners(): array
    {
        return $this->refiners;
    }
}
