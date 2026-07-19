<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;

final class BasicProductsTableWithTransformedData extends Table
{
    protected string $model = Product::class;
    protected string $data = TransformedProductData::class;

    public function defineColumns(): array
    {
        return [
            TextColumn::make('date'),
        ];
    }
}
