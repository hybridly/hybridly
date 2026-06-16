<?php

namespace Hybridly\Refining\Filters\Concerns;

trait HasType
{
    protected string|\Closure|null $type = null;

    public function type(string|\Closure $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->evaluate($this->type);
    }
}
