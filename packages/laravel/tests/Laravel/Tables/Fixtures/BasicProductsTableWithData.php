<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;

class BasicProductsTableWithData extends Table
{
    protected string $model = Product::class;
    protected string $data = ProductNameData::class;

    public function defineColumns(): array
    {
        return [
            TextColumn::make('name'),
        ];
    }
}
