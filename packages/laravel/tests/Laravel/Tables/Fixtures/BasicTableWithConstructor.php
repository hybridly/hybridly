<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;
use Hybridly\Tests\Fixtures\Vendor;
use Illuminate\Contracts\Database\Eloquent\Builder;

class BasicTableWithConstructor extends Table
{
    protected string $model = Product::class;

    public function __construct(
        private readonly Vendor $vendor,
    ) {
    }

    public function defineColumns(): array
    {
        return [
            TextColumn::make('name'),
            TextColumn::make('vendor'),
        ];
    }

    protected function defineQuery(): Builder
    {
        return parent::defineQuery()->where('vendor', $this->vendor);
    }
}
