<?php

namespace Hybridly\View;

use Illuminate\Contracts\Support\Arrayable;

class Payload implements Arrayable
{
    public function __construct(
        public View $view,
        public string $url,
        public ?string $version,
        public ?Dialog $dialog,
    ) {
    }

    public function toArray()
    {
        return [
            'view' => $this->view->toArray(),
            'dialog' => $this->dialog?->toArray(),
            'url' => $this->url,
            'version' => $this->version,
        ];
    }
}
