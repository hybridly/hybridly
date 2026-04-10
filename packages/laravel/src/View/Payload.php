<?php

namespace Hybridly\View;

use Illuminate\Contracts\Support\Arrayable;

class Payload implements Arrayable
{
    /**
     * @param array<string, array<string, string>> $validation
     */
    public function __construct(
        public ?View $view,
        public string $url,
        public ?string $version,
        public array $validation,
        public ?Dialog $dialog,
    ) {}

    public function toArray()
    {
        return [
            'view' => $this->view?->toArray(),
            'dialog' => $this->dialog?->toArray(),
            'url' => $this->url,
            'version' => $this->version,
            'validation' => (object) $this->validation,
        ];
    }
}
