<?php

namespace Hybridly;

/**
 * Similar to `Arrayable`, but only for hybrid responses.
 *
 * @template TKey of array-key
 * @template TValue
 */
interface SerializesProperties
{
    /**
     * Get the instance as an array for hybrid responses.
     *
     * @return array<TKey, TValue>
     */
    public function toHybridArray(): array;
}
