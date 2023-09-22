<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Spatie\LaravelData\Contracts\DataCollectable;

class BasicProductsTableWithData extends Table
{
    protected string $model = Product::class;

    public function defineColumns(): array
    {
        return [
            TextColumn::make('name'),
        ];
    }

    public function transformRecords(Paginator|CursorPaginator $paginator): DataCollectable
    {
        /** @var DataCollectable */
        $records = ProductNameData::collection($paginator);

        return $records;
    }
}
