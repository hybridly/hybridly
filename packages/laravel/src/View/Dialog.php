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
    ) {
    }

    public function toArray()
    {
        return [
            'component' => $this->component,
            'properties' => $this->properties,
            'baseUrl' => $this->baseUrl,
            'redirectUrl' => $this->redirectUrl,
        ];
    }
}
