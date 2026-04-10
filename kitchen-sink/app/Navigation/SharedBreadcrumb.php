<?php

declare(strict_types=1);

namespace App\Navigation;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class SharedBreadcrumb extends Data
{
    public function __construct(
        public string $label,
        public ?string $href = null,
    ) {}
}
