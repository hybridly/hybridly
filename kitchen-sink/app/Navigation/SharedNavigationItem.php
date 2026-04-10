<?php

declare(strict_types=1);

namespace App\Navigation;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class SharedNavigationItem extends Data
{
    public function __construct(
        public string $label,
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $href = null,
        public bool $external = false,
        public bool $active = false,
        public ?string $slot = null,
        /** @var array<string> $shortcuts */
        public array $shortcuts = [],
        /** @var SharedNavigationItem[] */
        public array $children = [],
    ) {}
}
