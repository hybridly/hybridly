<?php

namespace Hybridly\Concerns;

trait HasVersion
{
    protected \Closure|string|false|null $version = null;

    /**
     * Sets the asset version for the next response.
     */
    public function setVersion(\Closure|string|false|null $version = null): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Gets the asset version for the next response.
     */
    public function getVersion(): ?string
    {
        $version = \is_callable($this->version)
            ? app()->call($this->version)
            : $this->version;

        return $version;
    }
}
