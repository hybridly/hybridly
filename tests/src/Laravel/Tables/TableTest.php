<?php

use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicProductsTable;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicProductsTableWithActions;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicProductsTableWithData;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicProductsTableWithHiddenStuff;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicScopedProductsTable;

use function Pest\Laravel\post;

beforeEach(function () {
    Table::encodeIdUsing(static fn () => 'products-table');
    Table::decodeIdUsing(static fn () => 'products-table');
});

it('serializes a basic table', function () {
    ProductFactory::createImmutable();
    expect(BasicProductsTable::make())->toMatchSnapshot();
});

it('serializes a basic scoped table', function () {
    ProductFactory::createImmutable();
    expect(BasicScopedProductsTable::make())->toMatchSnapshot();
});

it('can transform records using Laravel Data', function () {
    ProductFactory::createImmutable();
    expect(BasicProductsTableWithData::make())->toMatchSnapshot();
});

it('hides hidden refinements, columns and actions in serialization', function () {
    ProductFactory::createImmutable();
    expect(BasicProductsTableWithHiddenStuff::make())->toMatchSnapshot();
});

it('can execute inline actions', function () {
    Table::encodeIdUsing(static fn () => BasicProductsTableWithActions::class);
    Table::decodeIdUsing(static fn () => BasicProductsTableWithActions::class);

    $product = ProductFactory::createImmutable();

    post(config('hybridly.tables.actions_endpoint'), [
        'type' => 'action:inline',
        'action' => 'say_my_name',
        'id' => BasicProductsTableWithActions::class,
        'record' => $product->id,
    ])->assertRedirect();

    expect(BasicProductsTableWithActions::$name)->toBe($product->name);
});

it('can execute bulk actions with all records', function () {
    BasicProductsTableWithActions::$names = [];

    Table::encodeIdUsing(static fn () => BasicProductsTableWithActions::class);
    Table::decodeIdUsing(static fn () => BasicProductsTableWithActions::class);

    ProductFactory::new()->create(['id' => 1, 'name' => 'Product 1']);
    ProductFactory::new()->create(['id' => 2, 'name' => 'Product 2']);
    ProductFactory::new()->create(['id' => 3, 'name' => 'Product 3']);

    post(config('hybridly.tables.actions_endpoint'), [
        'type' => 'action:bulk',
        'action' => 'say_our_names',
        'id' => BasicProductsTableWithActions::class,
        'all' => true,
        'only' => [],
        'except' => [],
    ])->assertRedirect();

    expect(BasicProductsTableWithActions::$names)->toBe(['Product 1', 'Product 2', 'Product 3']);
});

it('can execute bulk actions with selected records', function (array $recordIds, array $expectedNames) {
    BasicProductsTableWithActions::$names = [];

    Table::encodeIdUsing(static fn () => BasicProductsTableWithActions::class);
    Table::decodeIdUsing(static fn () => BasicProductsTableWithActions::class);

    $product1 = ProductFactory::new()->create(['id' => 1, 'name' => 'Product 1']);
    $product2 = ProductFactory::new()->create(['id' => 2, 'name' => 'Product 2']);
    $product3 = ProductFactory::new()->create(['id' => 3, 'name' => 'Product 3']);

    post(config('hybridly.tables.actions_endpoint'), [
        'type' => 'action:bulk',
        'action' => 'say_our_names',
        'id' => BasicProductsTableWithActions::class,
        'all' => false,
        'only' => $recordIds,
        'except' => [],
    ])->assertRedirect();

    expect(BasicProductsTableWithActions::$names)->toBe($expectedNames);
})->with([
    [[1], ['Product 1']],
    [[2, 3], ['Product 2', 'Product 3']],
    [[1, 3], ['Product 1', 'Product 3']],
]);

it('can execute bulk actions with excluded records', function (array $recordIds, array $expectedNames) {
    BasicProductsTableWithActions::$names = [];

    Table::encodeIdUsing(static fn () => BasicProductsTableWithActions::class);
    Table::decodeIdUsing(static fn () => BasicProductsTableWithActions::class);

    ProductFactory::new()->create(['id' => 1, 'name' => 'Product 1']);
    ProductFactory::new()->create(['id' => 2, 'name' => 'Product 2']);
    ProductFactory::new()->create(['id' => 3, 'name' => 'Product 3']);

    post(config('hybridly.tables.actions_endpoint'), [
        'type' => 'action:bulk',
        'action' => 'say_our_names',
        'id' => BasicProductsTableWithActions::class,
        'all' => true,
        'only' => [],
        'except' => $recordIds,
    ])->assertRedirect();

    expect(BasicProductsTableWithActions::$names)->toBe($expectedNames);
})->with([
    [[1], ['Product 2', 'Product 3']],
    [[2, 3], ['Product 1']],
    [[1, 3], ['Product 2']],
]);
