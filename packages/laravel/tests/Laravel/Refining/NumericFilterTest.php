<?php

use Hybridly\Refining\Filters\NumericFilter;
use Hybridly\Tests\Fixtures\Database\Product;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Pest\Expectation;

beforeEach(function () {
    ProductFactory::new()->create(['price' => 100]);
    ProductFactory::new()->create(['price' => 200]);
    ProductFactory::new()->create(['price' => 300]);
    ProductFactory::new()->create(['price' => 400]);
    ProductFactory::new()->create(['price' => 500]);
});

it('can be serialized', function () {
    $filter = NumericFilter::make('price')
        ->metadata(['foo' => 'bar'])
        ->label('Product price');

    $serialized = $filter->jsonSerialize();

    expect($serialized)
        ->toMatchArray([
            'name' => 'price',
            'hidden' => false,
            'label' => 'Product price',
            'type' => 'numeric',
            'is_active' => false,
            'value' => null,
            'default' => null,
        ]);

    expect($serialized['metadata'])
        ->toHaveKey('foo', 'bar');
});

test('it can filter with equals operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['price' => ['value' => 300, 'operator' => 'equals']]],
        refiners: [
            NumericFilter::make('price'),
        ],
    );

    expect($filters)
        ->first()
        ->price
        ->toBe(300)
        ->count()
        ->toBe(1);
});

test('it can filter with not equals operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['price' => ['value' => 300, 'operator' => 'not_equals']]],
        refiners: [
            NumericFilter::make('price'),
        ],
    );

    expect($filters->get())
        ->sequence(
            fn (Expectation $product) => $product->price->toBe(100),
            fn (Expectation $product) => $product->price->toBe(200),
            fn (Expectation $product) => $product->price->toBe(400),
            fn (Expectation $product) => $product->price->toBe(500),
        )
        ->count()
        ->toBe(4);
});

test('it can filter with greater than operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['price' => ['value' => 300, 'operator' => 'greater_than']]],
        refiners: [
            NumericFilter::make('price'),
        ],
    );

    expect($filters->get())
        ->sequence(
            fn (Expectation $product) => $product->price->toBe(400),
            fn (Expectation $product) => $product->price->toBe(500),
        )
        ->count()
        ->toBe(2);
});

test('it can filter with greater than or equal operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['price' => ['value' => 300, 'operator' => 'greater_than_or_equal']]],
        refiners: [
            NumericFilter::make('price'),
        ],
    );

    expect($filters->get())
        ->sequence(
            fn (Expectation $product) => $product->price->toBe(300),
            fn (Expectation $product) => $product->price->toBe(400),
            fn (Expectation $product) => $product->price->toBe(500),
        )
        ->count()
        ->toBe(3);
});

test('it can filter with less than operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['price' => ['value' => 300, 'operator' => 'less_than']]],
        refiners: [
            NumericFilter::make('price'),
        ],
    );

    expect($filters->get())
        ->sequence(
            fn (Expectation $product) => $product->price->toBe(100),
            fn (Expectation $product) => $product->price->toBe(200),
        )
        ->count()
        ->toBe(2);
});

test('it can filter with less than or equal operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['price' => ['value' => 300, 'operator' => 'less_than_or_equal']]],
        refiners: [
            NumericFilter::make('price'),
        ],
    );

    expect($filters->get())
        ->sequence(
            fn (Expectation $product) => $product->price->toBe(100),
            fn (Expectation $product) => $product->price->toBe(200),
            fn (Expectation $product) => $product->price->toBe(300),
        )
        ->count()
        ->toBe(3);
});

test('it can filter with between operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['price' => ['value' => [200, 400], 'operator' => 'between']]],
        refiners: [
            NumericFilter::make('price'),
        ],
    );

    expect($filters->get())
        ->sequence(
            fn (Expectation $product) => $product->price->toBe(200),
            fn (Expectation $product) => $product->price->toBe(300),
            fn (Expectation $product) => $product->price->toBe(400),
        )
        ->count()
        ->toBe(3);
});

test('it can filter with not between operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['price' => ['value' => [200, 400], 'operator' => 'not_between']]],
        refiners: [
            NumericFilter::make('price'),
        ],
    );

    expect($filters->get())
        ->sequence(
            fn (Expectation $product) => $product->price->toBe(100),
            fn (Expectation $product) => $product->price->toBe(500),
        )
        ->count()
        ->toBe(2);
});

test('it can filter with is null operator', function () {
    ProductFactory::new()->create(['price' => null]);

    $filters = mock_refiner(
        query: ['filters' => ['price' => ['operator' => 'is_null']]],
        refiners: [
            NumericFilter::make('price'),
        ],
    );

    expect($filters)
        ->first()
        ->price
        ->toBeNull()
        ->count()
        ->toBe(1);
});

test('it can filter with is not null operator', function () {
    ProductFactory::new()->create(['price' => null]);

    $filters = mock_refiner(
        query: ['filters' => ['price' => ['operator' => 'is_not_null']]],
        refiners: [
            NumericFilter::make('price'),
        ],
    );

    expect($filters)->count()->toBe(5);
});
