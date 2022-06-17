<?php

namespace Sleightful\Concerns;

use Sleightful\Sleightful;

trait HasRootView
{
    protected \Closure|string $rootView = Sleightful::DEFAULT_ROOT_VIEW;

    /**
     * Sets the root view for the next response.
     */
    public function setRootView(\Closure|string $rootView = null): void
    {
        $this->rootView = $rootView;
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
