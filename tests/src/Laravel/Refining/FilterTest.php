<?php

use Hybridly\Refining\Contracts\Filter as ContractsFilter;
use Hybridly\Refining\Filters\ExactFilter;
use Hybridly\Refining\Filters\Filter;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Illuminate\Contracts\Database\Eloquent\Builder;

beforeEach(function () {
    ProductFactory::new()->create(['name' => 'AirPods']);
    ProductFactory::new()->create(['name' => 'AirPods Pro']);
    ProductFactory::new()->create(['name' => 'Macbook Pro M1']);

    $this->filter = new class () implements ContractsFilter {
        public function getType(): string
        {
            return 'callback';
        }

        public function __invoke(Builder $builder, mixed $value, string $property): void
        {
            $builder->where($property, '=', $value);
        }
    };
});

test('filters can have a default value', function () {
    $filters = mock_refiner(
        refiners: [
            (new Filter(
                filter: $this->filter,
                property: 'name',
                alias: null,
            ))->default('AirPods Pro'),
        ],
    );

    expect($filters)
        ->first()->name->toBe('AirPods Pro')
        ->count()->toBe(1);
});

test('filters are applied using their property', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => 'AirPods Pro']],
        refiners: [
            new Filter(
                filter: $this->filter,
                property: 'name',
                alias: null,
            ),
        ],
    );

    expect($filters)
        ->first()->name->toBe('AirPods Pro')
        ->count()->toBe(1);
});

test('filters are not applied when their property is used but an alias is defined', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => 'AirPods Pro']],
        refiners: [
            new Filter(
                filter: $this->filter,
                property: 'name',
                alias: 'product',
            ),
        ],
    );

    expect($filters)->count()->toBe(3);
});

test('filters use the alias when defined', function () {
    $filters = mock_refiner(
        query: ['filters' => ['product' => 'AirPods Pro']],
        refiners: [
            new Filter(
                filter: $this->filter,
                property: 'name',
                alias: 'product',
            ),
        ],
    );

    expect($filters)->count()->toBe(1);
});

test('filters can be serialized', function () {
    $filter = new Filter(
        filter: $this->filter,
        property: 'airpods_gen',
        alias: null,
    );

    expect($filter)
        ->toBeInstanceOf(Filter::class)
        ->jsonSerialize()->toBe([
            'name' => 'airpods_gen',
            'hidden' => false,
            'label' => 'Airpods gen',
            'type' => 'callback',
            'metadata' => [],
            'is_active' => false,
            'value' => null,
        ]);
});

test('filters use their alias as name when defined', function () {
    $filter = new Filter(
        filter: $this->filter,
        property: 'generation',
        alias: 'airpods_generation',
    );

    expect($filter)
        ->toBeInstanceOf(Filter::class)
        ->jsonSerialize()->toBe([
            'name' => 'airpods_generation',
            'hidden' => false,
            'label' => 'Airpods generation',
            'type' => 'callback',
            'metadata' => [],
            'is_active' => false,
            'value' => null,
        ]);
});

test('serialization takes current state into account', function () {
    $filters = mock_refiner(
        query: ['filters' => ['product' => 'AirPods Pro']],
        refiners: [
            new Filter(
                filter: $this->filter,
                property: 'name',
                alias: 'product',
            ),
        ],
        apply: true,
    );

    expect(data_get(json_decode(json_encode($filters)), 'filters.0'))->toMatchArray([
        'name' => 'product',
        'label' => 'Product',
        'type' => 'callback',
        'metadata' => [],
        'is_active' => true,
        'value' => 'AirPods Pro',
    ]);
});

test('filters key is globally configurable', function () {
    config(['hybridly.refining.filters_key' => 'product-filters']);

    $filters = mock_refiner(
        query: ['product-filters' => ['name' => 'AirPods Pro']],
        refiners: [
            ExactFilter::make('name'),
        ],
    );

    expect($filters)
        ->first()->name->toBe('AirPods Pro')
        ->count()->toBe(1);
});

test('filters key is locally configurable', function () {
    $filters = mock_refiner(
        query: ['product-filters' => ['name' => 'AirPods Pro']],
        refiners: [
            ExactFilter::make('name'),
        ],
    )->filtersKey('product-filters');

    expect($filters)
        ->first()->name->toBe('AirPods Pro')
        ->count()->toBe(1);
});

test('filters key respects the scope', function () {
    $filters = mock_refiner(
        query: ['products-filtering' => ['name' => 'AirPods Pro']],
        refiners: [
            ExactFilter::make('name'),
        ],
    )->scope('products')->filtersKey('filtering');

    expect($filters)
        ->first()->name->toBe('AirPods Pro')
        ->count()->toBe(1);
});
