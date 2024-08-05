<?php

namespace Hybridly\Refining\Sorts\Concerns;

trait HasDefault
{
    protected null|\Closure|string $defaultDirection = null;
    protected \Closure|bool $sole = true;

    /**
     * Applies this sort by default.
     * If `sole` is set to `false`, the default sort will not be applied if other sorts are active.
     *
     * @see https://hybridly.dev/guide/refining.html#specifying-a-default-sort
     */
    public function default(\Closure|string $direction = 'asc', \Closure|bool $sole = true): static
    {
        $this->defaultDirection = $direction;
        $this->sole = $sole;

        return $this;
    }

    /**
     * If set to `false`, the default sort will not be applied if other sorts are active.
     *
     * @default false
     * @see https://hybridly.dev/guide/refining.html#specifying-a-default-sort
     */
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
