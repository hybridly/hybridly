<?php

namespace Hybridly\Support\Configuration;

/** @see https://hybridly.dev/guide/refining.html */
final class Refining
{
    public function __construct(
        public string $sortsKey,
        public string $filtersKey,
    ) {
    }

    public static function fromArray(array $config): static
    {
        return new static(
            sortsKey: $config['sorts_key'] ?? 'sort',
            filtersKey: $config['filters_key'] ?? 'filters',
        );
    }
}
