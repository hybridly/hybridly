<?php

namespace Hybridly\Components\Concerns;

trait HasMetadata
{
    protected array|\Closure $metadata = [];

    public function metadata(array|\Closure $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->evaluate($this->metadata);
    }
}
