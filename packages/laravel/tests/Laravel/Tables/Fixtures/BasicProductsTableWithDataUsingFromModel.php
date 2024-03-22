<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;

class BasicProductsTableWithDataUsingFromModel extends Table
{
    protected string $model = Product::class;
    protected string $data = ProductNameDataUsingFromModel::class;

    public function defineColumns(): array
    {
        return [
            TextColumn::make('name'),
            TextColumn::make('created_at'),
        ];
    }
}
