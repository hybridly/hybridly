<?php

namespace Hybridly;

use Hybridly\Tables\Table;

/**
 * Represents a table property that should merge records and cells together.
 */
final class MergeTable implements Property, Mergeable
{
    public function __construct(
        private Table $table,
        private(set) bool $prepend = false,
    ) {}

    public function shouldMerge(): bool
    {
        return true;
    }

    public function shouldPrepend(): bool
    {
        return $this->prepend;
    }

    public function uniqueBy(): ?string
    {
        return null;
    }

    public function paths(): array
    {
        return ['records', 'cells'];
    }

    public function uniqueByPath(): array
    {
        $keyName = $this->table->getRecordKeyName();

        if (! $keyName) {
            return [];
        }

        return [
            'records' => $keyName,
            'cells' => 'key',
        ];
    }

    public function evaluate(): mixed
    {
        return $this->table;
    }
}
