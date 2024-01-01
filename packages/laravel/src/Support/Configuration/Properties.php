<?php

namespace Hybridly\Support\Configuration;

final class Properties
{
    public const CAMEL = 'camel';
    public const SNAKE = 'snake';
    public const KEBAB = 'kebab';

    public function __construct(
        public readonly false|string $forceInputCase,
        public readonly false|string $forceOutputCase,
    ) {
    }

    public static function fromArray(array $config): static
    {
        return new static(
            forceInputCase: $config['force_input_case'] ?? false,
            forceOutputCase: $config['force_output_case'] ?? false,
        );
    }
}
