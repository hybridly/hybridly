<?php

namespace Hybridly\Support\Properties;

/**
 * Similar to `Arrayable`, but only for hybrid responses.
 *
 * @template TKey of array-key
 * @template TValue
 */
interface Hybridable
{
    /**
     * Get the instance as an array.
     *
     * @return array<TKey, TValue>
     */
    public function toHybridArray(): array;
}
