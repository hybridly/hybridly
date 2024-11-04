<?php

use Hybridly\Refining\Filters\BaseFilter;
use Hybridly\Refining\Filters\Filter;
use Hybridly\Tests\Fixtures\Database\Product;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Hybridly\Tests\Fixtures\Vendor;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Pest\Expectation;

beforeEach(function () {
    ProductFactory::new()->create(['name' => 'AirPods']);
    ProductFactory::new()->create(['name' => 'AirPods Pro']);
    ProductFactory::new()->create(['name' => 'Macbook Pro M1']);
});

it('can be serialized', function () {
    $filter = Filter::make('name')->beginsWithStrict()
        ->metadata(['foo' => 'bar'])
        ->label('Product name');

    expect($filter)
        ->toBeInstanceOf(BaseFilter::class)
        ->jsonSerialize()->toBe([
            'name' => 'name',
            'hidden' => false,
            'label' => 'Product name',
            'type' => 'similar:begins_with_strict',
            'metadata' => [
                'foo' => 'bar',
            ],
            'is_active' => false,
            'value' => null,
            'default' => null,
        ]);
});

test('in `exact` mode, it can use a different operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => 'AirPods']],
        refiners: [
            Filter::make('name')->operator('!='),
        ],
    );

    expect($filters->get())->sequence(
        fn (Expectation $product) => $product->name->toBe('AirPods Pro'),
        fn (Expectation $product) => $product->name->toBe('Macbook Pro M1'),
    )->count()->toBe(2);
});

test('in `begins_with_strict` mode, it only includes records that begin with the specified value', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => 'Macbook']],
        refiners: [
            Filter::make('name')->beginsWithStrict(),
        ],
    );

    expect($filters)
        ->first()->name->toBe('Macbook Pro M1')
        ->count()->toBe(1);
});

test('in `ends_with_strict` mode, it only includes records that end with the specified value', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => 'M1']],
        refiners: [
            Filter::make('name')->endsWithStrict(),
        ],
    );

    expect($filters)
        ->first()->name->toBe('Macbook Pro M1')
        ->count()->toBe(1);
});

test('in `loose` mode, it only includes records that match the specified value', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => 'airpods']],
        refiners: [
            Filter::make('name')->loose(),
        ],
    );

    expect($filters->get())->sequence(
        fn (Expectation $product) => $product->name->toBe('AirPods'),
        fn (Expectation $product) => $product->name->toBe('AirPods Pro'),
    )->count()->toBe(2);
});

test('in non-`strict` mode, it can use a `NOT LIKE` operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => 'airpods']],
        refiners: [
            Filter::make('name')
                ->operator('NOT LIKE')
                ->loose(),
        ],
    );

    expect($filters->get())
        ->first()->name->toBe('Macbook Pro M1')
        ->count()->toBe(1);
});

it('supports filtering with enums', function () {
    Product::query()->truncate();

    ProductFactory::new()->create(['vendor' => Vendor::Apple]);
    ProductFactory::new()->create(['vendor' => Vendor::Microsoft]);

    $filters = mock_refiner(
        query: ['filters' => ['vendor' => Vendor::Microsoft->value]],
        refiners: [Filter::make('vendor')->enum(Vendor::class)],
        apply: true,
    );

    expect($filters)
        ->first()->vendor->toBe(Vendor::Microsoft)
        ->count()->toBe(1);

    $filters = mock_refiner(
        query: ['filters' => ['vendor' => 'foobar']],
        refiners: [Filter::make('vendor')->enum(Vendor::class)],
        apply: true,
    );

    expect($filters)->count()->toBe(2);
});

test('order by statements can be unqualified', function () {
    DB::listen(function (QueryExecuted $query) {
        expect($query->sql)->toContain('where "vendor"');
    });

    mock_refiner(
        query: ['filters' => ['vendor' => Vendor::Microsoft->value]],
        refiners: [Filter::make('vendor')->withoutQualifyingColumn()],
        apply: true,
    )->get();
});
