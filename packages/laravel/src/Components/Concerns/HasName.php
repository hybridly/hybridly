<?php

namespace Hybridly\Components\Concerns;

trait HasName
{
    protected string|\Closure $name;

    public function name(string|\Closure $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->evaluate($this->name);
    }
}
