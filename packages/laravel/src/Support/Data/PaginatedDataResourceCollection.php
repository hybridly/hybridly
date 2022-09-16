<?php

namespace Monolikit\Support\Data;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\PaginatedDataCollection;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @implements  DataCollectable<TKey, TValue>
 */
class PaginatedDataResourceCollection extends PaginatedDataCollection
{
    /**
     * @param class-string<TValue> $dataClass
     * @param Paginator $items
     */
    public function __construct(
        public readonly string $dataClass,
        Paginator $items,
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
