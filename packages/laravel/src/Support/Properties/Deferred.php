<?php

namespace Hybridly\Support\Properties;

use Illuminate\Support\Facades\App;

/**
 * Represents a property that will automatically get evaluated in a subsequent partial reload after the view has loaded.
 */
final class Deferred implements Property, IgnoreFirstLoad, Mergeable
{
    public function __construct(
        private \Closure $callback,
        private(set) bool $append = false,
        private(set) bool $prepend = false,
        private(set) ?string $uniqueBy = null,
        private ?string $group = null,
    ) {}

    public function shouldMerge(): bool
    {
        return $this->append || $this->prepend;
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

    public function group(): string
    {
        return $this->group ?? 'default';
    }
}
