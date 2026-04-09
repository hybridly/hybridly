<?php

namespace Hybridly\Support\Properties;

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

    public function evaluate(): mixed
    {
        return App::call($this->callback);
    }
}
