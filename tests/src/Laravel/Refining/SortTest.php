<?php

use Hybridly\Refining\Sorts\FieldSort;
use Hybridly\Refining\Sorts\Sort;
use Hybridly\Tests\Fixtures\Database\ProductFactory;

beforeEach(function () {
    ProductFactory::new()->create(['name' => 'AirPods', 'published_at' => now()->setYear('2016')]);
    ProductFactory::new()->create(['name' => 'AirPods Pro', 'published_at' => now()->setYear('2022')]);
    ProductFactory::new()->create(['name' => 'Macbook Pro M1', 'published_at' => now()->setYear('2020')]);
});

it('can be serialized', function () {
    $sort = FieldSort::make('created_at')
        ->metadata([
            'foo' => 'bar',
        ])
        ->label('Creation date');

    expect($sort)
        ->toBeInstanceOf(Sort::class)
        ->jsonSerialize()->toBe([
            'name' => 'created_at',
            'label' => 'Creation date',
            'metadata' => [
                'foo' => 'bar',
            ],
            'is_active' => false,
            'direction' => null,
            'default' => null,
            'desc' => '-created_at',
            'asc' => 'created_at',
            'next' => '-created_at',
        ]);
});

it('uses its alias as name when serialized', function () {
    expect(FieldSort::make('created_at', alias: 'date'))
        ->toBeInstanceOf(Sort::class)
        ->jsonSerialize()->toBe([
            'name' => 'date',
            'label' => 'Date',
            'metadata' => [],
            'is_active' => false,
            'direction' => null,
            'default' => null,
            'desc' => '-date',
            'asc' => 'date',
            'next' => '-date',
        ]);
});

test('sorts can ascending, descending, or unspecified', function (?string $sort, array $expectedOrder) {
    $sorts = mock_refiner(
        query: array_filter(['sort' => $sort]),
        refiners: [FieldSort::make('published_at')],
    );

    expect($sorts->pluck('name')->toArray())->toEqual($expectedOrder);
})->with([
    ['published_at', ['AirPods', 'Macbook Pro M1', 'AirPods Pro']],
    ['-published_at', ['AirPods Pro', 'Macbook Pro M1', 'AirPods']],
    [null, ['AirPods', 'AirPods Pro', 'Macbook Pro M1']],
]);

test('sorts use the alias when defined', function (?string $sort, array $expectedOrder) {
    $sorts = mock_refiner(
        query: array_filter(['sort' => $sort]),
        refiners: [FieldSort::make('published_at', alias: 'date')],
    );

    expect($sorts->pluck('name')->toArray())->toEqual($expectedOrder);
})->with([
    ['date', ['AirPods', 'Macbook Pro M1', 'AirPods Pro']],
    ['-date', ['AirPods Pro', 'Macbook Pro M1', 'AirPods']],
    [null, ['AirPods', 'AirPods Pro', 'Macbook Pro M1']],
    ['published_at', ['AirPods', 'AirPods Pro', 'Macbook Pro M1']],
    ['-published_at', ['AirPods', 'AirPods Pro', 'Macbook Pro M1']],
]);
