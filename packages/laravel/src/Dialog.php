<?php

namespace Hybridly;

use Illuminate\Contracts\Support\Arrayable;

final class Dialog implements Arrayable
{
    public function __construct(
        public string $component,
        public array $properties,
        public string $baseUrl,
        public string $redirectUrl,
        public string $key,
    ) {}

    public function withProperties(array $properties): self
    {
        return new self(
            component: $this->component,
            properties: $properties,
            baseUrl: $this->baseUrl,
            redirectUrl: $this->redirectUrl,
            key: $this->key,
        );
    }

    public function toArray()
    {
        return [
            'component' => $this->component,
            'properties' => $this->properties,
            'baseUrl' => $this->baseUrl,
            'redirectUrl' => $this->redirectUrl,
            'key' => $this->key,
        ];
    }
}
