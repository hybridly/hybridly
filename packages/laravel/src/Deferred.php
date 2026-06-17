<?php

namespace Hybridly;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

/**
 * Represents a property that will automatically get evaluated in a subsequent partial reload after the view has loaded.
 */
final class Deferred implements Property, IgnoreFirstLoad, Mergeable
{
    private ?array $paths = [];
    private bool $merge = false;
    private bool $prepend = false;
    private ?string $uniqueBy = null;

    public function __construct(
        private \Closure $callback,
        private ?string $group = null,
    ) {}

    public function merge(?string $uniqueBy = null, bool $prepend = false, string|array $paths = []): self
    {
        $this->merge = true;
        $this->prepend = $prepend;
        $this->uniqueBy = $uniqueBy;
        $this->paths = $paths;

        return $this;
    }

    public function shouldMerge(): bool
    {
        return $this->merge;
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
        return Arr::wrap($this->paths);
    }

    public function uniqueByPath(): array
    {
        return [];
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
