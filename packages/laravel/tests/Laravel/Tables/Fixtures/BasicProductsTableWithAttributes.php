<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Refining\Filters\Filter;
use Hybridly\Refining\Sorts\Sort;
use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;

class BasicProductsTableWithAttributes extends Table
{
    protected string $model = Product::class;

    public function defineRefiners(): array
    {
        return [
            Sort::make('name'),
            Filter::make('name'),
        ];
    }

    public function defineColumns(): array
    {
        return [
            TextColumn::make('name')->attributes([
                'color' => 'primary',
            ]),
        ];
    }
}
