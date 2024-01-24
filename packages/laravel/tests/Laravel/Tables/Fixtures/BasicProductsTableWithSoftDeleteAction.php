<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Refining\Filters\Filter;
use Hybridly\Refining\Sorts\Sort;
use Hybridly\Tables\Actions\InlineAction;
use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;

class BasicProductsTableWithSoftDeleteAction extends Table
{
    public static ?string $name = null;
    public static ?array $names = [];

    protected string $model = Product::class;

    public function defineRefiners(): array
    {
        return [
            Sort::make('name'),
            Filter::make('name'),
        ];
    }

    public function defineActions(): array
    {
        return [
            InlineAction::make('say_my_name')->action(fn (Product $record) => self::$name = $record->name)->resolveModelUsing(fn (string $modelClass, $data) => $modelClass::withTrashed()->findOrFail($data->recordId)),
        ];
    }

    public function defineColumns(): array
    {
        return [
            TextColumn::make('name'),
        ];
    }
}
