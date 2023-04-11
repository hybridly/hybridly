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

    /**
     * Defines how a value should be retrieved.
     */
    public function setValueRetriever(\Closure $callback): static
    {
        $this->getFilterValueFromRequestCallback = $callback;

        return $this;
    }

    public function getFilterValueFromRequest(string $name, array $aliases = []): mixed
    {
        $callback = $this->getFilterValueFromRequestCallback ?? static function (Request $request, string $scope, string $name, array $aliases) {
            $filters = $request->get($scope);

            // If there is no alias, we use the given name to
            // find the value and return null if there is none.
            if (\count($aliases) === 0) {
                return $filters[$name] ?? null;
            }

            // Otherwise, we find the value for the first alias that
            // matches and return that, or null if nothing matches.
            return collect($aliases)->first(fn (string $alias) => $filters[$alias] ?? null);
        };

        return $this->evaluate($callback, [
            'refine' => $this,
            'request' => $this->getRequest(),
            'scope' => $this->formatScope('filters'),
            'name' => $name,
            'aliases' => $aliases,
        ]);
    }
}
