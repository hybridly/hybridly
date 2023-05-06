<?php

use Carbon\CarbonInterface;
use Hybridly\Refining\Filters\Filter;
use Hybridly\Refining\Filters\TrashedFilter;
use Hybridly\Tests\Fixtures\Database\ProductFactory;

it('can be serialized', function () {
    $filter = TrashedFilter::make('deleted')->metadata([
        'foo' => 'bar',
    ])->label('Show deleted');

    expect($filter)
        ->toBeInstanceOf(Filter::class)
        ->jsonSerialize()->toBe([
            'name' => 'deleted',
            'label' => 'Show deleted',
            'type' => 'trashed',
            'metadata' => [
                'foo' => 'bar',
            ],
            'is_active' => false,
            'value' => null,
        ]);
});

it('filters out deleted products when `trashed` is not set', function () {
    ProductFactory::new()->create();
    ProductFactory::new()->create(['deleted_at' => now()]);

    $filters = mock_refiner([
        TrashedFilter::make(),
    ]);

    expect($filters)
        ->first()->deleted_at->toBeNull()
        ->count()->toBe(1);
});

it('filters out non-deleted products when `trashed` is set to `only`', function () {
    ProductFactory::new()->create();
    ProductFactory::new()->create(['deleted_at' => now()]);

    $filters = mock_refiner(
        query: ['filters' => ['trashed' => 'only']],
        refiners: [
            TrashedFilter::make(),
        ],
    );

    expect($filters)
        ->first()->deleted_at->toBeInstanceOf(CarbonInterface::class)
        ->count()->toBe(1);
});

it('includes deleted products when `trashed` is set to `with`', function () {
    ProductFactory::new()->create();
    ProductFactory::new()->create(['deleted_at' => now()]);

    $filters = mock_refiner(
        query: ['filters' => ['trashed' => 'with']],
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
        query: ['filters' => ['trashed' => 'wofhwoiefopiwef']],
        refiners: [
            TrashedFilter::make(),
        ],
    );

    expect($filters)
        ->first()->deleted_at->toBeNull()
        ->count()->toBe(1);
});
