<?php

namespace Hybridly\Refining\Sorts\Concerns;

trait HasDefault
{
    protected null|\Closure|string $defaultDirection = null;
    protected \Closure|bool $sole = true;

    public function default(\Closure|string $direction = 'asc', \Closure|bool $sole = true): static
    {
        $this->defaultDirection = $direction;
        $this->sole = $sole;

        return $this;
    }

    public function sole(\Closure|bool $sole = true): static
    {
        $this->sole = $sole;

        return $this;
    }

    public function getDefaultDirection(): ?string
    {
        return $this->evaluate($this->defaultDirection);
    }

    public function isSole(): bool
    {
        return $this->evaluate($this->sole);
    }
}
