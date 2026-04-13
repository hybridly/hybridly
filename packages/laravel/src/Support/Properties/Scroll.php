<?php

namespace Hybridly\Support\Properties;

use Closure;
use Hybridly\Support\Pagination\PaginatorMetadata;
use Hybridly\Support\Pagination\ScrollMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use JsonSerializable;

final class Scroll implements Property, Mergeable
{
    private null|JsonSerializable|iterable $resolved = null;

    public function __construct(
        private Closure|JsonSerializable|iterable $value,
        private(set) ?string $wrapper = null,
        private Closure|ScrollMetadata|null $metadata = null,
    ) {}

    public function shouldMerge(): bool
    {
        return true;
    }

    public function shouldPrepend(): bool
    {
        return false;
    }

    public function uniqueBy(): ?string
    {
        return null;
    }

    public function mergePaths(): ?array
    {
        $wrapper = $this->wrapper ?? PaginatorMetadata::inferWrapper($this->evaluate());

        if ($wrapper === null) {
            return null;
        }

        return [$wrapper];
    }

    public function evaluate(): JsonSerializable|iterable
    {
        return $this->resolved ??= \is_callable($this->value)
            ? App::call($this->value)
            : $this->value;
    }

    public function resolveMetadata(Request $request): ?ScrollMetadata
    {
        if ($this->metadata instanceof ScrollMetadata) {
            return $this->metadata;
        }

        if ($this->metadata instanceof Closure) {
            $metadata = App::call($this->metadata, [
                'request' => $request,
                'value' => $this->evaluate(),
            ]);

            if ($metadata instanceof ScrollMetadata) {
                return $metadata;
            }
        }

        return PaginatorMetadata::extract($request, $this->evaluate());
    }
}
