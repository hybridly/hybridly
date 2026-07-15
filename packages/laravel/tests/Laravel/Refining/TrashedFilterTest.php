<?php

use Carbon\CarbonInterface;
use Hybridly\Refining\Filters\TrashedFilter;
use Hybridly\Tests\Fixtures\Database\ProductFactory;

it('can be serialized', function () {
    $filter = TrashedFilter::make('deleted')
        ->metadata([
            'foo' => 'bar',
        ])
        ->label('Show deleted');

    $serialized = $filter->jsonSerialize();

    expect($serialized)
        ->toMatchArray([
            'name' => 'deleted',
            'hidden' => false,
            'label' => 'Show deleted',
            'type' => 'trashed',
            'is_active' => false,
            'value' => null,
            'default' => null,
        ]);

    expect($serialized['metadata'])->toHaveKey('foo', 'bar');
});

it('filters out deleted products when `trashed` is not set', function () {
    ProductFactory::new()->create();
    ProductFactory::new()->create(['deleted_at' => now()]);

    $filters = mock_refiner([
        TrashedFilter::make(),
    ]);

    expect($filters)
        ->first()
        ->deleted_at
        ->toBeNull()
        ->count()
        ->toBe(1);
});

it('filters out non-deleted products when `trashed` is set to `only`', function () {
    ProductFactory::new()->create();
    ProductFactory::new()->create(['deleted_at' => now()]);

    $filters = mock_refiner(
        query: ['filters' => ['trashed' => ['value' => 'only']]],
        refiners: [
            TrashedFilter::make(),
        ],
    );

    expect($filters)
        ->first()
        ->deleted_at
        ->toBeInstanceOf(CarbonInterface::class)
        ->count()
        ->toBe(1);
});

it('includes deleted products when `trashed` is set to `with`', function () {
    ProductFactory::new()->create();
    ProductFactory::new()->create(['deleted_at' => now()]);

    $filters = mock_refiner(
        query: ['filters' => ['trashed' => ['value' => 'with']]],
        refiners: [
            TrashedFilter::make(),
        ],
    );

    expect($filters)->count()->toBe(2);
});

it('ignores the filter when `trashed` is set to an unknown value', function () {
    ProductFactory::new()->create();
    ProductFactory::new()->create(['deleted_at' => now()]);

    $filters = mock_refiner(
        query: ['filters' => ['trashed' => ['value' => 'wofhwoiefopiwef']]],
        refiners: [
            TrashedFilter::make(),
        ],
    );

    expect($filters)
        ->first()
        ->deleted_at
        ->toBeNull()
        ->count()
        ->toBe(1);
});
