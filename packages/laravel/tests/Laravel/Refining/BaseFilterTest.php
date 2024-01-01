<?php

use Hybridly\Refining\Filters\BaseFilter;
use Hybridly\Refining\Filters\CallbackFilter;
use Hybridly\Refining\Filters\Filter;
use Hybridly\Support\Configuration\Configuration;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Illuminate\Contracts\Database\Eloquent\Builder;

beforeEach(function () {
    ProductFactory::new()->create(['name' => 'AirPods']);
    ProductFactory::new()->create(['name' => 'AirPods Pro']);
    ProductFactory::new()->create(['name' => 'Macbook Pro M1']);

    $this->filter = new class ()
    {
        public function __invoke(Builder $builder, mixed $value): void
        {
            $builder->where('name', '=', $value);
        }

        public function getType(): string
        {
            return 'callback';
        }
    };
});

test('filters can have a default value', function () {
    $filters = mock_refiner(
        refiners: [
            CallbackFilter::make('name', $this->filter)->default('AirPods Pro'),
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
            CallbackFilter::make('name', $this->filter),
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
            CallbackFilter::make('product', $this->filter),
        ],
    );

    expect($filters)->count()->toBe(3);
});

test('filters use the alias when defined', function () {
    $filters = mock_refiner(
        query: ['filters' => ['product' => 'AirPods Pro']],
        refiners: [
            CallbackFilter::make('product', $this->filter),
        ],
    );

    expect($filters)->count()->toBe(1);
});

test('filters can be serialized', function () {
    expect(Filter::make('airpods_gen'))
        ->toBeInstanceOf(BaseFilter::class)
        ->jsonSerialize()->toBe([
            'name' => 'airpods_gen',
            'hidden' => false,
            'label' => 'Airpods gen',
            'type' => 'exact',
            'metadata' => [],
            'is_active' => false,
            'value' => null,
            'default' => null,
        ]);
});

test('filters use their alias as name when defined', function () {
    expect(Filter::make('airpods_gen', alias: 'airpods_generation'))
        ->toBeInstanceOf(BaseFilter::class)
        ->jsonSerialize()->toBe([
            'name' => 'airpods_generation',
            'hidden' => false,
            'label' => 'Airpods generation',
            'type' => 'exact',
            'metadata' => [],
            'is_active' => false,
            'value' => null,
            'default' => null,
        ]);
});

test('serialization takes current state into account', function () {
    $filters = mock_refiner(
        query: ['filters' => ['product' => 'AirPods Pro']],
        refiners: [
            Filter::make('name', alias: 'product'),
        ],
        apply: true,
    );

    expect(data_get(json_decode(json_encode($filters)), 'filters.0'))->toMatchArray([
        'name' => 'product',
        'label' => 'Product',
        'type' => 'exact',
        'metadata' => [],
        'is_active' => true,
        'value' => 'AirPods Pro',
    ]);
});

test('filters key is globally configurable', function () {
    Configuration::get()->refining->filtersKey = 'product-filters';

    $filters = mock_refiner(
        query: ['product-filters' => ['name' => 'AirPods Pro']],
        refiners: [
            Filter::make('name'),
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
            Filter::make('name'),
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
            Filter::make('name'),
        ],
    )->scope('products')->filtersKey('filtering');

    expect($filters)
        ->first()->name->toBe('AirPods Pro')
        ->count()->toBe(1);
});
