<?php

namespace Hybridly\Refining\Concerns;

trait ConfiguresKeys
{
    protected null|\Closure|string $sortsKey;
    protected null|\Closure|string $filtersKey;

    /**
     * Sets the sorts key for this refinement.
     */
    public function sortsKey(string|\Closure $sortsKey): static
    {
        $this->sortsKey = $sortsKey;

        return $this;
    }

    /**
     * Sets the filters key for this refinement.
     */
    public function filtersKey(string|\Closure $filtersKey): static
    {
        $this->filtersKey = $filtersKey;

        return $this;
    }

    /**
     * Gets the sorts key for this refinement.
     */
    public function getSortsKey(): string
    {
        return $this->evaluate($this->sortsKey ?? config('hybridly.refining.sorts_key'));
    }

    /**
     * Gets the filters key for this refinement.
     */
    public function getFiltersKey(): string
    {
        return $this->evaluate($this->filtersKey ?? config('hybridly.refining.filters_key'));
    }
}
