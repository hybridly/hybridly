<?php

namespace Hybridly\Support\Properties;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

/**
 * Represents a property that will always be loaded.
 */
final class Persistent implements Property, Mergeable
{
    public function __construct(
        private \Closure $callback,
        private(set) bool $prepend = false,
        private(set) ?string $uniqueBy = null,
        private(set) ?array $path = null,
    ) {}

    public function shouldMerge(): bool
    {
        return true;
    }

    public function shouldPrepend(): bool
    {
        return $this->prepend;
    }

    public function uniqueBy(): ?string
    {
        return $this->uniqueBy;
    }

    public function paths(): array
    {
        return Arr::wrap($this->path);
    }

    public function evaluate(): mixed
    {
        return App::call($this->callback);
    }
}
