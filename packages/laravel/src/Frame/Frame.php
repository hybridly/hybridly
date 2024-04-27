<?php

namespace Hybridly\Frame;

use Illuminate\Contracts\Support\Arrayable;

final class Frame implements Arrayable
{
    public function __construct(
        public ?string $component,
        public array $properties = [],
    ) {
    }

    public function withProperties(array $properties): self
    {
        return new self(
            component: $this->component,
            properties: $properties,
        );
    }

    public function toArray()
    {
        return [
            'component' => $this->component,
            'properties' => $this->properties,
        ];
    }
}
