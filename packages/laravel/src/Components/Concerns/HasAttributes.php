<?php

namespace Hybridly\Components\Concerns;

trait HasAttributes
{
    protected array|\Closure $attributes = [];

    public function attributes(array|\Closure $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getAttributes(): array
    {
        return $this->evaluate($this->attributes);
    }
}
