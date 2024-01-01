<?php

namespace Hybridly\Refining\Concerns;

use Hybridly\Support\Configuration\Configuration;

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
        return $this->evaluate($this->sortsKey ?? Configuration::get()->refining->sortsKey);
    }

    /**
     * Gets the filters key for this refinement.
     */
    public function getFiltersKey(): string
    {
        return $this->evaluate($this->filtersKey ?? Configuration::get()->refining->filtersKey);
    }
}
