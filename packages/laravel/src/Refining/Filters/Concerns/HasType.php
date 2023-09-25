<?php

namespace Hybridly\Refining\Filters\Concerns;

trait HasType
{
    protected null|string|\Closure $type = null;

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
