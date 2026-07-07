<?php

namespace Hybridly\Refining\Filters\Concerns;

trait HasDefaultValue
{
    protected mixed $defaultValue = null;
    protected bool $hasDefaultValue = false;

    public function default(mixed $value = true): static
    {
        $this->defaultValue = $value;
        $this->hasDefaultValue = true;

        return $this;
    }

    public function getDefaultValue()
    {
        return $this->evaluate($this->defaultValue);
    }

    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }
}
