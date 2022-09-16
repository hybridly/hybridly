<?php

namespace Hybridly\Support\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\CursorPaginator;
use Spatie\LaravelData\CursorPaginatedDataCollection;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @implements  DataCollectable<TKey, TValue>
 */
class CursorPaginatedDataResourceCollection extends CursorPaginatedDataCollection
{
    /**
     * @param class-string<TValue> $dataClass
     * @param CursorPaginator<TValue> $items
     */
    public function __construct(
        public readonly string $dataClass,
        CursorPaginator $items,
    ) {
        $this->items = $items->through(function ($item) {
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
