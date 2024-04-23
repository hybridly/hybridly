<?php

namespace Hybridly\Support;

/**
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
