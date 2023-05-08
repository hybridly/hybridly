<?php

namespace Hybridly\Refining\Filters\Concerns;

trait HasType
{
    protected ?string $type = null;

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }
}
