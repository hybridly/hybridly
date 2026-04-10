<?php

namespace App\KitchenSink\DataLoading\Mergeable;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class Message extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $content,
        public readonly string $side,
        public readonly CarbonImmutable $sent_at,
    ) {}
}
