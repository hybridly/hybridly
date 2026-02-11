<?php

namespace Hybridly\Tables\Actions;

use Hybridly\Tables\Actions\DataTransferObjects\BulkActionData;
use Hybridly\Tables\Actions\DataTransferObjects\BulkSelection;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scopes the query to the selected records.
 */
final class BulkSelected
{
    public function __construct(
        private(set) BulkActionData|BulkSelection $bulk,
        private(set) ?string $keyName = null,
    ) {}

    public function __invoke(Builder $builder): Builder
    {
        return match (true) {
            $this->bulk->all === true => $builder->whereNotIn($this->keyName ?? $builder->getModel()->getKeyName(), $this->bulk->except),
            default => $builder->whereIn($this->keyName ?? $builder->getModel()->getKeyName(), $this->bulk->only),
        };
    }
}
