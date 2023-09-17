<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Refining\Filters\ExactFilter;
use Hybridly\Refining\Sorts\FieldSort;
use Hybridly\Tables\Actions\BulkAction;
use Hybridly\Tables\Actions\InlineAction;
use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;
use Illuminate\Support\Collection;

class BasicScopedProductsTable extends Table
{
    protected string $model = Product::class;
    protected string $scope = 'custom-scope';

    public function defineRefinements(): array
    {
        return [
            FieldSort::make('name'),
            ExactFilter::make('name'),
        ];
    }

    public function defineActions(): array
    {
        return [
            BulkAction::make('say_our_names')->action(fn (Collection $records) => $records->each(fn (Product $record) => $record->name)),
            InlineAction::make('say_my_name')->action(fn (Product $record) => $record->name),
        ];
    }

    public function defineColumns(): array
    {
        return [
            TextColumn::make('name'),
        ];
    }
}
