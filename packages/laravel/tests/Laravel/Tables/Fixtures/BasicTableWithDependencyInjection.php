<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;
use Hybridly\Tests\Fixtures\Vendor;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BasicTableWithDependencyInjection extends Table
{
    protected string $model = Product::class;

    public function __construct(
        private readonly Request $request,
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
        return parent::defineQuery()->where('vendor', $this->request->enum('vendor', Vendor::class));
    }
}
