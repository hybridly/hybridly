<?php

use Hybridly\Refining\Sorts\CallbackSort;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Illuminate\Contracts\Database\Eloquent\Builder;

beforeEach(function () {
    ProductFactory::new()->create(['name' => 'AirPods', 'published_at' => now()->setYear('2016')]);
    ProductFactory::new()->create(['name' => 'AirPods Pro', 'published_at' => now()->setYear('2022')]);
    ProductFactory::new()->create(['name' => 'Macbook Pro M1', 'published_at' => now()->setYear('2020')]);
});

test('sorts according to the given callback', function () {
    $sorts = mock_refiner(
        query: ['sort' => '-published_at'],
        refiners: [
            CallbackSort::make('published_at', function (Builder $builder, string $direction, string $property) {
                $builder->orderBy(
                    column: $builder->qualifyColumn($property),
                    direction: $direction,
                );
            }),
        ],
    );

    expect($sorts)
        ->first()->name->toBe('AirPods Pro')
        ->count()->toBe(3);
});

test('custom parameters can be passed', function () {
    $sorts = mock_refiner(
        query: ['sort' => 'published_at'],
        refiners: [
            CallbackSort::make('published_at', function (Builder $builder, string $direction, string $property, string $foo) {
                expect($foo)->toBe('bar'); // yeah not a lot of inspiration there
                $builder->orderBy(
                    column: $builder->qualifyColumn($property),
                    direction: $direction,
                );
            }, parameters: ['foo' => 'bar']),
        ],
    );

    expect($sorts)->count()->toBe(3);
});
