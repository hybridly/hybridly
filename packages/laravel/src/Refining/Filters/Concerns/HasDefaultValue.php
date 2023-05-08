<?php

namespace Hybridly\Refining\Filters\Concerns;

trait HasDefaultValue
{
    protected mixed $defaultValue = null;

    public function default(mixed $value = true): static
    {
        $this->defaultValue = $value;

        return $this;
    }

    public function getDefaultValue()
    {
        return $this->evaluate($this->defaultValue);
    }
}
