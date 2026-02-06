<?php

namespace Hybridly\Components\Concerns;

trait HasIcon
{
    protected null|string|\Closure $icon = null;

    public function icon(string|\Closure $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function hasIcon(): bool
    {
        return (bool) $this->evaluate($this->icon);
    }

    public function getIcon(): ?string
    {
        return $this->evaluate($this->icon);
    }
}
