<?php

namespace Hybridly\View;

use Illuminate\Contracts\Support\Arrayable;

final class View implements Arrayable
{
    public function __construct(
        public ?string $component,
        public array $properties,
        public array $deferred = [],
        public array $mergeable = [],
        public array $paginators = [],
    ) {}

    public function toArray()
    {
        return [
            'component' => $this->component,
            'properties' => $this->properties,
            'deferred' => $this->deferred,
            'mergeable' => $this->mergeable,
            'paginators' => $this->paginators,
        ];
    }
}
