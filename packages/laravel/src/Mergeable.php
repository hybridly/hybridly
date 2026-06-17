<?php

namespace Hybridly;

/**
 * Represents a property that will be merged with the previous value of the property, if any.
 */
interface Mergeable
{
    /**
     * Whether this instance should be merged with its previous value.
     */
    public function shouldMerge(): bool;

    /**
     * Whether this instance should be merged with its previous value.
     */
    public function shouldPrepend(): bool;

    /**
     * This instance will replace the previous value if it matches the property at the specified path. Otherwise, it will be merged normally.
     */
    public function uniqueBy(): ?string;

    /**
     * Relative paths within the wrapped property that should be merged into. The rest of the object will be ignored.
     *
     * @return array<int, string>
     */
    public function paths(): array;

    /**
     * Unique keys to use for specific paths.
     *
     * @return array<string, string|null>
     */
    public function uniqueByPath(): array;
}
