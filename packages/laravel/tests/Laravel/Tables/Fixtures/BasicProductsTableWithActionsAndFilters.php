<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Refining\Filters\Filter;
use Hybridly\Tables\Actions\BulkAction;
use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;
use Illuminate\Support\Collection;

class BasicProductsTableWithActionsAndFilters extends Table
{
    protected string $model = Product::class;

    public function defineRefiners(): array
    {
        return [
            Filter::make('vendor'),
            Filter::make('is_active'),
        ];
    }

    public function defineActions(): array
    {
        return [
            BulkAction::make('deactivate')
                ->action(fn (Collection $records) => $records->each(fn (Product $record) => $record->update(['is_active' => false]))),
        ];
    }

    public function defineColumns(): array
    {
        return [
            TextColumn::make('vendor'),
            TextColumn::make('is_active'),
        ];
    }
}
