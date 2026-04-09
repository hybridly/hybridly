<?php

namespace Hybridly\Support\Properties;

use Closure;
use Illuminate\Support\Facades\App;

/**
 * Represents a property that should merge itself with its current instance.
 */
final class Merge implements Property, Mergeable
{
    public function __construct(
        private Closure|iterable $value,
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
        return \is_callable($this->value)
            ? App::call($this->value)
            : $this->value;
    }
}
