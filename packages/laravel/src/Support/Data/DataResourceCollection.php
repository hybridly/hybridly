<?php

namespace Hybridly\Support\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Spatie\LaravelData\DataCollection;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @implements \ArrayAccess<TKey, TValue>
 * @implements  DataCollectable<TKey, TValue>
 */
class DataResourceCollection extends DataCollection
{
    /**
     * @param class-string<TValue> $dataClass
     * @param array|Enumerable<TKey, TValue>|DataCollection $items
     */
    public function __construct(
        public readonly string $dataClass,
        Enumerable|array|DataCollection $items,
    ) {
        if (\is_array($items)) {
            $items = new Collection($items);
        }

        if ($items instanceof DataCollection) {
            $items = $items->toCollection();
        }

        $this->items = $items->map(function ($item) {
            if ($item instanceof Model) {
                return $this->dataClass::from($item)
                    ->usingModel($item);
            }

            return $item instanceof $this->dataClass
                ? $item
                : $this->dataClass::from($item);
        });
    }
}
