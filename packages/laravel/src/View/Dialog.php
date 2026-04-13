<?php

namespace Hybridly\View;

use Illuminate\Contracts\Support\Arrayable;

class Dialog implements Arrayable
{
    public function __construct(
        public string $component,
        public array $properties,
        public string $baseUrl,
        public string $redirectUrl,
        public string $key,
        public array $deferred = [],
        public array $mergeable = [],
        public array $paginators = [],
    ) {}

    public function withProperties(array $properties): self
    {
        return new self(
            component: $this->component,
            properties: $properties,
            baseUrl: $this->baseUrl,
            redirectUrl: $this->redirectUrl,
            key: $this->key,
            deferred: $this->deferred,
            mergeable: $this->mergeable,
            paginators: $this->paginators,
        );
    }

    public function toArray()
    {
        return [
            'component' => $this->component,
            'properties' => $this->properties,
            'deferred' => $this->deferred,
            'mergeable' => $this->mergeable,
            'paginators' => $this->paginators,
            'baseUrl' => $this->baseUrl,
            'redirectUrl' => $this->redirectUrl,
            'key' => $this->key,
        ];
    }
}
