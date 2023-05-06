<?php

namespace Hybridly\Refining\Sorts\Concerns;

trait HasDefault
{
    protected null|\Closure|string $defaultDirection = null;

    public function default(\Closure|string $direction = 'asc'): static
    {
        $this->defaultDirection = $direction;

        return $this;
    }

    public function getDefaultDirection(): ?string
    {
        return $this->evaluate($this->defaultDirection);
    }
}
