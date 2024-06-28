<?php

namespace Hybridly\Tables\Concerns;

use Hybridly\Tables\Columns\BaseColumn;
use Illuminate\Support\Collection;

trait HasColumns
{
    private mixed $cachedColumns = null;

    public function getTableColumns(): Collection
    {
        return $this->cachedColumns ??= collect($this->defineColumns())
            ->filter(static fn (BaseColumn $column): bool => !$column->isHidden());
    }

    protected function defineColumns(): array
    {
        return [];
    }
}
