<?php

use Hybridly\Refining\Filters\ExactFilter;
use Hybridly\Refining\Filters\Filter;
use Hybridly\Tests\Fixtures\Database\ProductFactory;

it('can be serialized', function () {
    $filter = ExactFilter::make('name')->metadata([
        'foo' => 'bar',
    ])->label('Product name');

    expect($filter)
        ->toBeInstanceOf(Filter::class)
        ->jsonSerialize()->toBe([
            'name' => 'name',
            'hidden' => false,
            'label' => 'Product name',
            'type' => 'exact',
            'metadata' => [
                'foo' => 'bar',
            ],
            'is_active' => false,
            'value' => null,
            'default' => null,
        ]);
});

it('filters out records that do not correspond exactly to the specified value', function () {
    ProductFactory::new()->create([
        'name' => 'AirPods',
    ]);

    ProductFactory::new()->create([
        'name' => 'AirPods Pro',
    ]);

    $filters = mock_refiner(
        query: ['filters' => ['name' => 'AirPods']],
        refiners: [
            ExactFilter::make('name'),
        ],
    );

    expect($filters)
        ->first()->name->toBe('AirPods')
        ->count()->toBe(1);
});
