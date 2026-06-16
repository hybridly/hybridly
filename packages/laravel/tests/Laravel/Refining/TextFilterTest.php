<?php

use Hybridly\Refining\Filters\BaseFilter;
use Hybridly\Refining\Filters\TextFilter;
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
    $filter = TextFilter::make('name')
        ->metadata(['foo' => 'bar'])
        ->label('Product name');

    $serialized = $filter->jsonSerialize();

    expect($serialized)
        ->toMatchArray([
            'name' => 'name',
            'hidden' => false,
            'label' => 'Product name',
            'type' => 'text',
            'is_active' => false,
            'value' => null,
            'default' => null,
        ]);

    expect($serialized['metadata'])
        ->toHaveKey('foo', 'bar');
});

test('it can use a different operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'AirPods', 'operator' => 'not_equals']]],
        refiners: [
            TextFilter::make('name'),
        ],
    );

    expect($filters->get())
        ->sequence(
            fn (Expectation $product) => $product->name->toBe('AirPods Pro'),
            fn (Expectation $product) => $product->name->toBe('Macbook Pro M1'),
        )
        ->count()
        ->toBe(2);
});

test('it only includes records that begin with the specified value', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'Macbook', 'operator' => 'begins_with']]],
        refiners: [
            TextFilter::make('name'),
        ],
    );

    expect($filters)
        ->first()
        ->name
        ->toBe('Macbook Pro M1')
        ->count()
        ->toBe(1);
});

test('it only includes records that end with the specified value', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'M1', 'operator' => 'ends_with']]],
        refiners: [
            TextFilter::make('name'),
        ],
    );

    expect($filters)
        ->first()
        ->name
        ->toBe('Macbook Pro M1')
        ->count()
        ->toBe(1);
});

test('it only includes records that contain the specified value', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'airpods', 'operator' => 'contains']]],
        refiners: [
            TextFilter::make('name'),
        ],
    );

    expect($filters->get())
        ->sequence(
            fn (Expectation $product) => $product->name->toBe('AirPods'),
            fn (Expectation $product) => $product->name->toBe('AirPods Pro'),
        )
        ->count()
        ->toBe(2);
});

test('it can use a not contains operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'airpods', 'operator' => 'not_contains']]],
        refiners: [
            TextFilter::make('name'),
        ],
    );

    expect($filters->get())
        ->first()
        ->name
        ->toBe('Macbook Pro M1')
        ->count()
        ->toBe(1);
});

test('order by statements can be unqualified', function () {
    DB::listen(function (QueryExecuted $query) {
        expect($query->sql)->toContain('where "vendor"');
    });

    mock_refiner(
        query: ['filters' => ['vendor' => ['value' => Vendor::Microsoft->value]]],
        refiners: [TextFilter::make('vendor')->withoutQualifyingColumn()],
        apply: true,
    )->get();
});

test('it can filter with is null operator', function () {
    ProductFactory::new()->create(['name' => null]);

    $filters = mock_refiner(
        query: ['filters' => ['name' => ['operator' => 'is_null']]],
        refiners: [
            TextFilter::make('name'),
        ],
    );

    expect($filters)
        ->first()
        ->name
        ->toBeNull()
        ->count()
        ->toBe(1);
});

test('it can filter with is not null operator', function () {
    ProductFactory::new()->create(['name' => null]);

    $filters = mock_refiner(
        query: ['filters' => ['name' => ['operator' => 'is_not_null']]],
        refiners: [
            TextFilter::make('name'),
        ],
    );

    expect($filters)->count()->toBe(3);
});
