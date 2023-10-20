<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;
use Hybridly\Tests\Fixtures\Vendor;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BasicTableWithDependencyInjectionAndArguments extends Table
{
    protected string $model = Product::class;

    public function __construct(
        private readonly Request $request,
        private readonly string $contains,
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
        return parent::defineQuery()
            ->where('vendor', $this->request->enum('vendor', Vendor::class))
            ->where('name', 'LIKE', '%' . $this->contains . '%');
    }
}
