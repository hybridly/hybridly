<?php

namespace Hybridly\View;

use Illuminate\Contracts\Support\Arrayable;

class View implements Arrayable
{
    public function __construct(
        public ?string $component,
        public array $properties,
        public array $deferred = [],
    ) {
        // Converts identifiers using PascalCase to proper kebab-cased identifiers.
        if (preg_match('/[A-Z]/', $this->component)) {
            $expected = str($this->component)
                ->after('::')
                ->explode('.')
                ->map(fn (string $s) => str($s)->kebab()->trim('-'))
                ->join('.');

            if (str_contains($this->component, '::')) {
                $expected = str($this->component)
                    ->before('::')
                    ->kebab()
                    ->append('::')
                    ->append($expected);
            }

            trigger_deprecation('hybridly/laravel', '0.7', "Passing component names with uppercase to Hybridly is deprecated, you should use kebab case instead (Received: {$this->component}, expected: {$expected})");
            $this->component = $expected;
        }
    }

    public function toArray()
    {
        return [
            'component' => $this->component,
            'properties' => $this->properties,
            'deferred' => $this->deferred,
        ];
    }
}
