<?php

namespace Hybridly\Tables\Concerns;

use Hybridly\Tables\Columns\BaseColumn;
use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Illuminate\Support\Collection;
use ReflectionClass;
use Spatie\LaravelData\Data;

/**
 * @mixin Table
 */
trait HasColumns
{
    private mixed $cachedColumns = null;

    /** @return Collection<BaseColumn> */
    public function getTableColumns(): Collection
    {
        return $this->cachedColumns ??= collect($this->defineColumns())
            ->filter(static fn (BaseColumn $column): bool => ! $column->isHidden());
    }

    /** @return BaseColumn[] */
    protected function defineColumns(): array
    {
        if (isset($this->data) && is_a($this->data, Data::class, allow_string: true)) {
            return $this->extractColumnsFromData();
        }

        return [];
    }

    /** @return BaseColumn[] */
    protected function extractColumnsFromData(): array
    {
        $columns = [];
        $data = new ReflectionClass($this->data);

        foreach ($data->getConstructor()?->getParameters() ?? [] as $parameter) {
            if (! $parameter->isPromoted()) {
                continue;
            }

            $columns[] = TextColumn::make($parameter->getName());
        }

        return $columns;
    }
}
