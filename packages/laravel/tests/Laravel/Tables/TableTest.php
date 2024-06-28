<?php

use Hybridly\Tables\Table;
use Hybridly\Tests\Fixtures\Database\Product;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Hybridly\Tests\Fixtures\Database\UserFactory;
use Hybridly\Tests\Fixtures\Vendor;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicProductsTable;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicProductsTableWithActions;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicProductsTableWithActionsAndFilters;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicProductsTableWithCellMetadata;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicProductsTableWithConditionallyHiddenStuff;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicProductsTableWithData;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicProductsTableWithDataUsingFromModel;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicProductsTableWithHiddenStuff;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicProductsTableWithSoftDeleteAction;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicScopedProductsTable;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicTableWithConstructor;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicTableWithDependencyInjection;
use Hybridly\Tests\Laravel\Tables\Fixtures\BasicTableWithDependencyInjectionAndArguments;
use Illuminate\Support\Facades\Auth;
use Pest\Expectation;

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

it('includes authorization on records by default', function () {
    Auth::login(UserFactory::new()->create());
    ProductFactory::createImmutable();

    $result = BasicProductsTableWithData::make();
    expect($result)->toMatchSnapshot();
    expect($result->getRecords()[0])->toHaveKey('authorization');
});

it('includes authorization on records using custom `fromModel` by default', function () {
    Auth::login(UserFactory::new()->create());
    ProductFactory::createImmutable();

    $result = BasicProductsTableWithDataUsingFromModel::make()->getRecords();
    expect($result[0])->toHaveKey('authorization');
    expect($result[0]['authorization']['returns-true'])->toBeTrue();
    expect($result[0]['authorization']['returns-false'])->toBeFalse();
});

it('excludes authorization on records when specified', function () {
    Auth::login(UserFactory::new()->create());
    ProductFactory::createImmutable();

    $result = BasicProductsTableWithData::make()
        ->withoutResolvingAuthorizations()
        ->getRecords();

    expect($result[0])->not->toHaveKey('authorization');
});

it('excludes authorization on records using custom `fromModel` when specified', function () {
    Auth::login(UserFactory::new()->create());
    ProductFactory::createImmutable();

    $result = BasicProductsTableWithDataUsingFromModel::make()
        ->withoutResolvingAuthorizations()
        ->getRecords();

    expect($result[0])->not->toHaveKey('authorization');
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
        'tableId' => BasicProductsTableWithActions::class,
        'recordId' => $product->id,
    ])->assertRedirect();

    expect(BasicProductsTableWithActions::$name)->toBe($product->name);
});

it('can execute inline action for soft deleted records', function () {
    Table::encodeIdUsing(static fn () => BasicProductsTableWithSoftDeleteAction::class);
    Table::decodeIdUsing(static fn () => BasicProductsTableWithSoftDeleteAction::class);

    $product = ProductFactory::createImmutable();
    $product->delete();

    post(config('hybridly.tables.actions_endpoint'), [
        'type' => 'action:inline',
        'action' => 'say_my_name',
        'tableId' => BasicProductsTableWithSoftDeleteAction::class,
        'recordId' => $product->id,
    ])->assertRedirect();

    expect(BasicProductsTableWithSoftDeleteAction::$name)->toBe($product->name);
});

it('can execute a conditionally hidden inline actions', function () {
    Table::encodeIdUsing(static fn () => BasicProductsTableWithConditionallyHiddenStuff::class);
    Table::decodeIdUsing(static fn () => BasicProductsTableWithConditionallyHiddenStuff::class);

    $product = ProductFactory::createImmutable();

    $this->withoutExceptionHandling();

    post(config('hybridly.tables.actions_endpoint'), [
        'type' => 'action:inline',
        'action' => 'say_my_name',
        'tableId' => BasicProductsTableWithConditionallyHiddenStuff::class,
        'recordId' => $product->id,
    ])->assertRedirect();

    expect(BasicProductsTableWithConditionallyHiddenStuff::$name)->toBe($product->name);
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
        'tableId' => BasicProductsTableWithActions::class,
        'all' => true,
        'only' => [],
        'except' => [],
    ])->assertRedirect();

    expect(BasicProductsTableWithActions::$names)->toBe(['Product 1', 'Product 2', 'Product 3']);
});

it('takes filters into account when executing bulk actions', function () {
    Table::encodeIdUsing(static fn () => BasicProductsTableWithActionsAndFilters::class);
    Table::decodeIdUsing(static fn () => BasicProductsTableWithActionsAndFilters::class);

    ProductFactory::new()->create(['id' => 1, 'vendor' => Vendor::Apple, 'is_active' => true]);
    ProductFactory::new()->create(['id' => 2, 'vendor' => Vendor::Microsoft, 'is_active' => true]);
    ProductFactory::new()->create(['id' => 3, 'vendor' => Vendor::Apple, 'is_active' => true]);

    post(config('hybridly.tables.actions_endpoint'), [
        'type' => 'action:bulk',
        'action' => 'deactivate',
        'tableId' => BasicProductsTableWithActionsAndFilters::class,
        'all' => true,
        'only' => [],
        'except' => [],
        'filters' => [
            'vendor' => Vendor::Microsoft->value,
        ],
    ])->assertRedirect();

    expect(Product::find(2))->is_active->toBe(false);
    expect(Product::where('is_active', true)->get())
        ->toHaveCount(2)
        ->sequence(
            fn (Expectation $expect) => $expect->id->toBe(1),
            fn (Expectation $expect) => $expect->id->toBe(3),
        );
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
        'tableId' => BasicProductsTableWithActions::class,
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
        'tableId' => BasicProductsTableWithActions::class,
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

it('supports dependency injection on the constructor', function () {
    ProductFactory::new()->create(['name' => 'Product 1', 'vendor' => Vendor::Apple]);
    ProductFactory::new()->create(['name' => 'Product 2', 'vendor' => Vendor::Microsoft]);

    mock_request(bind: true, query: [
        'vendor' => 'microsoft',
    ]);

    $table = BasicTableWithDependencyInjection::make();

    expect($table->getRecords())
        ->toHaveCount(1)
        ->sequence(fn ($expect) => $expect->name->toBe('Product 2'));
});

it('supports custom arguments on the constructor', function () {
    ProductFactory::new()->create(['name' => 'Product 1', 'vendor' => Vendor::Apple]);
    ProductFactory::new()->create(['name' => 'Product 2', 'vendor' => Vendor::Microsoft]);

    $table = new BasicTableWithConstructor(Vendor::Microsoft);

    expect($table->getRecords())
        ->toHaveCount(1)
        ->sequence(fn ($expect) => $expect->name->toBe('Product 2'));
});

it('supports custom arguments on `make`', function () {
    ProductFactory::new()->create(['name' => 'Product 1', 'vendor' => Vendor::Apple]);
    ProductFactory::new()->create(['name' => 'Product 2', 'vendor' => Vendor::Microsoft]);

    $table = BasicTableWithConstructor::make([
        'vendor' => Vendor::Microsoft,
    ]);

    expect($table->getRecords())
        ->toHaveCount(1)
        ->sequence(fn ($expect) => $expect->name->toBe('Product 2'));
});

it('supports dependency injection and custom arguments on `make`', function () {
    ProductFactory::new()->create(['name' => 'Product 1', 'vendor' => Vendor::Apple]);
    ProductFactory::new()->create(['name' => 'Product foo', 'vendor' => Vendor::Microsoft]);
    ProductFactory::new()->create(['name' => 'Product bar', 'vendor' => Vendor::Microsoft]);

    mock_request(bind: true, query: [
        'vendor' => 'microsoft',
    ]);

    $table = BasicTableWithDependencyInjectionAndArguments::make([
        'contains' => 'bar',
    ]);

    expect($table->getRecords())
        ->toHaveCount(1)
        ->sequence(fn ($expect) => $expect->name->toBe('Product bar'));
});

it('may have cell metadata', function () {
    ProductFactory::new()->create(['name' => 'Product 1', 'vendor' => Vendor::Apple]);
    ProductFactory::new()->create(['name' => 'Product 2', 'vendor' => Vendor::Microsoft]);

    $table = BasicProductsTableWithCellMetadata::make();

    expect($table->getRecords())->toMatchSnapshot();
})->only();
