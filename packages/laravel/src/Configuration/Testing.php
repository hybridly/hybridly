<?php

namespace Hybridly\Configuration;

final class Testing
{
    public function __construct(
        public readonly bool $disableVersioning,
        public readonly bool $ensureViewsExist,
    ) {}

    public static function fromArray(array $config): static
    {
        return new static(
            disableVersioning: $config['disable_versioning'] ?? true,
            ensureViewsExist: $config['ensure_views_exist'] ?? true,
        );
    }
}
