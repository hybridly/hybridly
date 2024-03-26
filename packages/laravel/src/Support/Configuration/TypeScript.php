<?php

namespace Hybridly\Support\Configuration;

use Hybridly\Support\TypeScriptTransformer\GlobalPropertiesNamespaceTransformer;

final class TypeScript
{
    public function __construct(
        public array $basePaths,
        public ?string $namespaceTransformer,
    ) {
    }

    public static function fromArray(array $config): static
    {
        return new static(
            basePaths: $config['base_paths'] ?? [
                base_path('app'),
                base_path('src'),
            ],
            namespaceTransformer: $config['namespace_transformer'] ?? GlobalPropertiesNamespaceTransformer::class,
        );
    }
}
