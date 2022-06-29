<?php

namespace Monolikit\Concerns;

use Monolikit\Monolikit;

trait HasRootView
{
    protected \Closure|string $rootView = Monolikit::DEFAULT_ROOT_VIEW;

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

        return (string) $rootView;
    }
}
