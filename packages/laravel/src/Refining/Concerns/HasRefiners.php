<?php

namespace Hybridly\Refining\Concerns;

use Hybridly\Refining\Filters\Operator;
use Hybridly\Refining\Filters\QueryFilter;
use Hybridly\Refining\FilterState;
use Hybridly\Refining\Refine;
use Hybridly\Refining\Sorts\BaseSort;
use Hybridly\Refining\SortState;
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

        foreach ($this->getOrderedRefiners() as $refiner) {
            $refiner->refine($this, $this->getBuilderInstance());
        }

        $this->refined = true;

        return $this;
    }

    public function hasOtherSorts(BaseSort $sort): bool
    {
        return collect($this->getRequestedSortValues())
            ->filter()
            ->contains(fn (string $sortName) => ltrim($sortName, '-') !== ($sort->getAlias() ?? $sort->getProperty()));
    }

    public function hasRequestedSorts(): bool
    {
        return $this->getRequest()->query->has($this->formatScope($this->getSortsKey()));
    }

    public function areSortsCleared(): bool
    {
        return $this->getRequest()->boolean($this->formatScope($this->getSortsClearedKey()));
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

    public function getEffectiveSortDefault(BaseSort $sort): ?SortState
    {
        if ($this->hasReplacementBaseline()) {
            return $this->getBaseline()?->sort($sort->getName());
        }

        if (! $sort->hasDefaultDirection()) {
            return null;
        }

        $direction = $sort->getDefaultDirection();

        if ($direction === null) {
            return null;
        }

        return new SortState(
            name: $sort->getName(),
            direction: $direction,
        );
    }

    public function getEffectiveSortDefaultOrder(BaseSort $sort): ?int
    {
        return array_find_key(
            $this->getEffectiveDefaultSorts(),
            static fn (SortState $state): bool => $state->name === $sort->getName(),
        );
    }

    public function getEffectiveCurrentSortOrder(BaseSort $sort): ?int
    {
        return array_find_key(
            $this->getEffectiveCurrentSorts(),
            static fn (SortState $state): bool => $state->name === $sort->getName(),
        );
    }

    /** @return array<SortState> */
    public function getEffectiveDefaultSorts(): array
    {
        if ($this->hasReplacementBaseline()) {
            return $this->getBaseline()->sorts ?? [];
        }

        return collect($this->getSortRefiners())
            ->filter(static fn (BaseSort $sort): bool => $sort->hasDefaultDirection())
            ->map(static function (BaseSort $sort): ?SortState {
                $direction = $sort->getDefaultDirection();

                return $direction === null
                    ? null
                    : new SortState(name: $sort->getName(), direction: $direction);
            })
            ->filter()
            ->values()
            ->all();
    }

    /** @return array<SortState> */
    public function getEffectiveCurrentSorts(): array
    {
        if ($this->areSortsCleared()) {
            return [];
        }

        if ($this->hasRequestedSorts()) {
            $requested = array_map(
                static fn (string $sort): SortState => new SortState(
                    name: ltrim($sort, '-'),
                    direction: str_starts_with($sort, '-') ? 'desc' : 'asc',
                ),
                $this->getRequestedSortValues(),
            );

            if ($this->hasReplacementBaseline()) {
                return $requested;
            }

            $requestedNames = array_map(
                static fn (SortState $sort): string => $sort->name,
                $requested,
            );

            return [
                ...$requested,
                ...collect($this->getSortRefiners())
                    ->filter(static fn (BaseSort $sort): bool => $sort->hasDefaultDirection() && ! $sort->isSole() && ! in_array($sort->getName(), $requestedNames, strict: true))
                    ->map(static function (BaseSort $sort): ?SortState {
                        $direction = $sort->getDefaultDirection();

                        return $direction === null
                            ? null
                            : new SortState(name: $sort->getName(), direction: $direction);
                    })
                    ->filter()
                    ->values()
                    ->all(),
            ];
        }

        return $this->getEffectiveDefaultSorts();
    }

    public function hasFilterRequest(string $name): bool
    {
        return array_key_exists($name, $this->getRequest()->array($this->formatScope($this->getFiltersKey())));
    }

    public function isFilterCleared(string $name): bool
    {
        return filter_var(
            data_get($this->getRequest()->array($this->formatScope($this->getFiltersKey())), "{$name}.disabled", false),
            \FILTER_VALIDATE_BOOLEAN,
        );
    }

    public function getEffectiveFilterDefault(string $name, ?FilterState $declaredDefault): ?FilterState
    {
        if ($this->hasReplacementBaseline()) {
            return $this->getBaseline()?->filter($name);
        }

        return $declaredDefault;
    }

    /**
     * Gets the filter value for the given property from the request. If an alias is provided, it will be used instead of the property name to look for the value in the request. Returns null if no value is found, or a QueryFilter with the default value if configured.
     */
    public function getQueryFilterFromRequest(string $property, ?string $alias, ?FilterState $default): ?QueryFilter
    {
        $callback = static function (Request $request, string $scope, string $property, ?string $alias, ?FilterState $default) {
            $filters = $request->array($scope);
            $key = $alias ?? $property;

            if (! array_key_exists($key, $filters)) {
                return $default === null
                    ? null
                    : new QueryFilter(
                        value: $default->value,
                        operator: $default->operator,
                        options: $default->options,
                        suggestionKey: $default->suggestionKey,
                    );
            }

            if (filter_var(data_get($filters, "{$key}.disabled", false), \FILTER_VALIDATE_BOOLEAN)) {
                return null;
            }

            $value = data_get($filters, "{$key}.value");
            $operator = data_get($filters, "{$key}.operator");

            $operator = is_string($operator)
                ? Operator::tryFrom($operator)
                : null;

            return new QueryFilter(
                value: $value,
                search: data_get($filters, "{$key}.search"),
                operator: $operator,
                options: data_get($filters, "{$key}.options", default: []),
                suggestionKey: data_get($filters, "{$key}.suggestion_key"),
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

    /** @return array<Refiner> */
    protected function getOrderedRefiners(): array
    {
        $sortOrder = collect($this->getEffectiveCurrentSorts())
            ->mapWithKeys(static fn (SortState $sort, int $index): array => [$sort->name => $index]);

        return collect($this->getRefiners())
            ->sortBy(static function ($refiner, int $index) use ($sortOrder): int {
                if (! $refiner instanceof BaseSort) {
                    return $index;
                }

                return $sortOrder->has($refiner->getName())
                    ? 10_000 + $sortOrder->get($refiner->getName())
                    : 20_000 + $index;
            })
            ->values()
            ->all();
    }

    /** @return array<BaseSort> */
    protected function getSortRefiners(): array
    {
        return collect($this->getRefiners())
            ->flatMap(static fn ($refiner) => $refiner instanceof \Hybridly\Refining\Group ? $refiner->getRefiners() : [$refiner])
            ->filter(static fn ($refiner): bool => $refiner instanceof BaseSort)
            ->values()
            ->all();
    }

    /** @return array<string> */
    protected function getRequestedSortValues(): array
    {
        return array_values(array_filter(
            explode(',', (string) $this->getRequest()->get($this->formatScope($this->getSortsKey()))),
        ));
    }

    /**
     * @return array<Refiner>
     */
    protected function getRefiners(): array
    {
        return $this->refiners;
    }
}
