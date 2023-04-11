<?php

namespace Hybridly\Components\Concerns;

trait HasIdentifier
{
    protected ?string $identifier = null;

    public function identifier(string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }
}
