<?php

namespace Hybridly\Concerns;

use Hybridly\Support\Configuration\Configuration;

trait HasRootView
{
    protected null|\Closure|string $rootView = null;

    /**
     * Sets the root view for the next response.
     */
    public function setRootView(\Closure|string $rootView = null): static
    {
        $this->rootView = $rootView;

        return $this;
    }

    /**
     * Gets the root view for the next response.
     */
    public function getRootView(): string
    {
        $rootView = $this->rootView instanceof \Closure
            ? app()->call($this->rootView)
            : $this->rootView;

        $rootView ??= Configuration::get()->architecture->rootView;

        return (string) $rootView;
    }
}
