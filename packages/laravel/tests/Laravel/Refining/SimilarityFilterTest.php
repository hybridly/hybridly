<?php

use Hybridly\Refining\Filters\Filter;
use Hybridly\Refining\Filters\SimilarityFilter;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Pest\Expectation;

beforeEach(function () {
    ProductFactory::new()->create(['name' => 'AirPods']);
    ProductFactory::new()->create(['name' => 'AirPods Pro']);
    ProductFactory::new()->create(['name' => 'Macbook Pro M1']);
});

it('can be serialized', function () {
    $filter = SimilarityFilter::make('name', mode: SimilarityFilter::BEGINS_WITH_STRICT)
        ->metadata([
            'foo' => 'bar',
        ])
        ->label('Product name');

    expect($filter)
        ->toBeInstanceOf(Filter::class)
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

test('in `begins_with_strict` mode, it only includes records that begin with the specified value', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => 'Macbook']],
        refiners: [
            SimilarityFilter::make('name', mode: SimilarityFilter::BEGINS_WITH_STRICT),
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
            SimilarityFilter::make('name', mode: SimilarityFilter::ENDS_WITH_STRICT),
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
            SimilarityFilter::make('name', mode: SimilarityFilter::LOOSE),
        ],
    );

    expect($filters->get())->sequence(
        fn (Expectation $product) => $product->name->toBe('AirPods'),
        fn (Expectation $product) => $product->name->toBe('AirPods Pro'),
    )->count()->toBe(2);
});
