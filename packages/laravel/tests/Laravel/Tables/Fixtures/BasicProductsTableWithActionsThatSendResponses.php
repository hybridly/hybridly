<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Tables\Actions\InlineAction;
use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;

use function Hybridly\to_external_url;
use function Hybridly\view;

class BasicProductsTableWithActionsThatSendResponses extends Table
{
    public static ?string $name = null;
    public static ?array $names = [];

    protected string $model = Product::class;

    public function defineActions(): array
    {
        return [
            InlineAction::make('hybrid_response')->action(fn (Product $record) => view('some_dialog', ['product' => $record])->base('product-details', ['product' => $record])),
            InlineAction::make('external_redirect')->action(fn (Product $record) => to_external_url('https://google.com?q=' . urlencode($record->name))),
            InlineAction::make('internal_redirect')->action(fn (Product $record) => redirect()->to("/products/{$record->id}")),
            InlineAction::make('flash_message')->action(fn (Product $record) => back()->with('message', "Got product [{$record->id}]")),
            InlineAction::make('no_op')->action(function (Product $record) {
                // no op
            }),
        ];
    }
}
