<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Refining\Filters\TextFilter;
use Hybridly\Refining\Sorts\Sort;
use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;

class BasicProductsTableWithMetadata extends Table
{
    protected string $model = Product::class;

    public function defineRefiners(): array
    {
        return [
            Sort::make('name'),
            TextFilter::make('name'),
        ];
    }

    public function defineColumns(): array
    {
        return [
            TextColumn::make('name')->metadata([
                'color' => 'primary',
            ]),
        ];
    }
}
