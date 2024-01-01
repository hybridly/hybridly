<?php

namespace Hybridly\Support\Configuration;

final class Testing
{
    public function __construct(
        public readonly bool $ensureViewsExist,
    ) {
    }

    public static function fromArray(array $config): static
    {
        return new static(
            ensureViewsExist: $config['ensure_views_exist'] ?? true,
        );
    }
}
