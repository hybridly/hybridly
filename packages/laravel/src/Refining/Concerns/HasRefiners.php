<?php

namespace Hybridly\Refining\Concerns;

use Hybridly\Refining\Contracts\Refiner;
use Hybridly\Refining\Contracts\Sort;
use Hybridly\Refining\Refine;
use Hybridly\Refining\SortGroup;
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

        $this->orderSortsFromQuery();

        foreach ($this->getRefiners() as $refiner) {
            $refiner->refine($this, $this->getBuilderInstance());
        }

        $this->refined = true;

        return $this;
    }

    /**
     * Order the sorts group from query.
     *
     * @return void
     */
    protected function orderSortsFromQuery(): void
    {
        // Get the 'sorts' query string from the request
        $query = $this->request->query('sorts');

        // Check if the query exists...
        if ($query) {
            // Find the first refiner of type Sort in the refiners collection
            /** @var SortGroup|null $sort_group */
            $sort_group = collect($this->refiners)->first(static fn($refiner): bool => $refiner instanceof SortGroup);

            // check if sort group exists...
            if ($sort_group) {
                // Get the keys of the query
                $keys = array_keys($query);

                // Convert the array returned by getRefiners into a collection and sort the elements based on the index of their keys in the query
                $refiners_ordered = collect($sort_group->getRefiners())->sortBy(static fn(Sort $sort) => array_search($sort->getName(), $keys, true));

                // Update the refiners of the sorter with the ordered collection.
                $sort_group->refiners($refiners_ordered->all());
            }
        }
    }

    public function getSortDirectionFromRequest(string $property, ?string $alias = null): ?string
    {
        $callback = static function (Request $request, string $scope, string $property, ?string $alias) {
            $sorts = $request->get($scope);

            // If there is no alias, we use the given name to
            // find the value and return null if there is none.
            if (\is_null($alias)) {
                return $sorts[$property] ?? null;
            }

            // Otherwise, we find the value for the alias if it exists.
            return $sorts[$alias] ?? null;
        };

        $sort = $this->evaluate(
            value: $callback,
            named: [
                'request' => $this->getRequest(),
                'scope' => $this->formatScope($this->getSortsKey()),
                'property' => $property,
                'alias' => $alias,
            ],
            typed: [
                Request::class => $this->getRequest(),
            ],
        );

        // If we didn't get a sort value, there is no sort.
        if (!$sort) {
            return null;
        }

        // Otherwise, we infer the direction depending on the presence of `-`.
        return '-' === $sort[0]
            ? 'desc'
            : 'asc';
    }

    public function getFilterValueFromRequest(string $property, ?string $alias = null): mixed
    {
        $callback = static function (Request $request, string $scope, string $property, ?string $alias) {
            $filters = $request->get($scope);

            // If there is no alias, we use the given name to
            // find the value and return null if there is none.
            if (\is_null($alias)) {
                return $filters[$property] ?? null;
            }

            // Otherwise, we find the value for the alias if it exists.
            return $filters[$alias] ?? null;
        };

        return $this->evaluate($callback, [
            'request' => $this->getRequest(),
            'scope' => $this->formatScope($this->getFiltersKey()),
            'property' => $property,
            'alias' => $alias,
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
