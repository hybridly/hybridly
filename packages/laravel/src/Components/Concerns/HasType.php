<?php

namespace Hybridly\Components\Concerns;

trait HasType
{
    protected string|\Closure $type;

    public function type(string|\Closure $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->evaluate($this->type);
    }
}
