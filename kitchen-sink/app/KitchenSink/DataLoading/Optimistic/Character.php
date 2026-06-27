<?php

namespace App\KitchenSink\DataLoading\Optimistic;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class Character extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $title,
        public readonly string $description,
        public readonly int $likes,
        public readonly bool $liked,
    ) {}
}
