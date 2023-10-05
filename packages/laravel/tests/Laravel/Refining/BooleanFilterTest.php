<?php

use Hybridly\Refining\Filters\BooleanFilter;
use Hybridly\Tests\Fixtures\Database\ProductFactory;

beforeEach(function () {
    ProductFactory::new()->create(['name' => 'AirPods Pro', 'is_active' => true]);
    ProductFactory::new()->create(['name' => 'Macbook Pro M1', 'is_active' => false]);
});

test('it converts the value to boolean', function (mixed $bool, ?string $name, int $count) {
    $filters = mock_refiner(
        query: ['filters' => ['active' => $bool]],
        refiners: [
            BooleanFilter::make('is_active', alias: 'active'),
        ],
    );

    expect($filters)
        ->first()->name->toBe($name)
        ->count()->toBe($count);
})->with([
    // Truthy values
    [true, 'AirPods Pro', 1],
    ['true', 'AirPods Pro', 1],
    ['yes', 'AirPods Pro', 1],
    ['on', 'AirPods Pro', 1],
    [1, 'AirPods Pro', 1],
    ['1', 'AirPods Pro', 1],

    // Falsy values
    [false, 'Macbook Pro M1', 1],
    ['false', 'Macbook Pro M1', 1],
    ['no', 'Macbook Pro M1', 1],
    ['off', 'Macbook Pro M1', 1],
    ['0', 'Macbook Pro M1', 1],
    [0, 'Macbook Pro M1', 1],

    // Filter not applied
    [null, 'AirPods Pro', 2],
]);
