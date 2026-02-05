<?php

namespace Hybridly\Components\Concerns;

trait HasMetadata
{
    protected array|\Closure $metadata = [];

    /** @var array<array|\Closure> */
    protected array $metadataToAppend = [];

    public function metadata(array|\Closure $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function appendMetadata(array|\Closure $metadata): static
    {
        $this->metadataToAppend[] = $metadata;

        return $this;
    }

    public function getMetadata(): array
    {
        return [
            ...$this->evaluate($this->metadata),
            ...collect($this->metadataToAppend)->flatMap(fn ($metadata) => $this->evaluate($metadata))->toArray(),
        ];
    }
}
