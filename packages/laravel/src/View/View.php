<?php

namespace Hybridly\View;

use Illuminate\Contracts\Support\Arrayable;

class View implements Arrayable
{
    public function __construct(
        public ?string $component,
        public array $properties,
    ) {
    }

    public function toArray()
    {
        return [
            'component' => $this->component,
            'properties' => $this->properties,
        ];
    }
}
