<?php

namespace Hybridly\Refining\Concerns;

use Hybridly\Refining\Contracts\Refiner;
use Hybridly\Refining\Refine;
use Illuminate\Http\Request;

/** @mixin Refine */
trait HasRefiners
{
    protected array $refiners = [];
    protected ?\Closure $getFilterValueFromRequestCallback = null;

    public function addRefiners(iterable $refiners): static
    {
        $this->refiners = array_merge($this->refiners, $refiners);

        return $this;
    }

    /**
     * @return array<Refiner>
     */
    protected function getRefiners(): array
    {
        return $this->refiners;
    }

    protected function applyRefiners(): static
    {
        foreach ($this->getRefiners() as $refiner) {
            $refiner->refine($this, $this->getBuilderInstance());
        }

        return $this;
    }

    public function getSortDirectionFromRequest(string $property, ?string $alias = null): ?string
    {
        $callback = static function (Request $request, string $scope, string $property, ?string $alias) {
            $sorts = collect(explode(',', $request->get($scope)));
            $property = $alias ?? $property;

            return $sorts->first(fn (string $sort) => ltrim($sort, '-') === $property);
        };

        $sort = $this->evaluate($callback, [
            'request' => $this->getRequest(),
            'scope' => $this->formatScope($this->getSortsKey()),
            'property' => $property,
            'alias' => $alias,
        ]);

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
}
