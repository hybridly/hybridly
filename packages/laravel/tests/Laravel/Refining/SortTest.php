<?php

use Hybridly\Refining\Sorts\BaseSort;
use Hybridly\Refining\Sorts\Sort;
use Hybridly\Support\Configuration\Configuration;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    ProductFactory::new()->create(['name' => 'AirPods', 'published_at' => now()->setYear(2016)]);
    ProductFactory::new()->create(['name' => 'AirPods Pro', 'published_at' => now()->setYear(2022)]);
    ProductFactory::new()->create(['name' => 'Macbook Pro M1', 'published_at' => now()->setYear(2020)]);
});

it('can be serialized', function () {
    $sort = Sort::make('created_at')
        ->metadata(['foo' => 'bar'])
        ->label('Creation date');

    expect($sort)
        ->toBeInstanceOf(BaseSort::class)
        ->jsonSerialize()->toBe([
            'name' => 'created_at',
            'hidden' => false,
            'label' => 'Creation date',
            'metadata' => [
                'foo' => 'bar',
            ],
            'is_active' => false,
            'direction' => null,
            'default' => null,
            'desc' => '-created_at',
            'asc' => 'created_at',
            'next' => 'created_at',
        ]);
});

it('uses its alias as name when serialized', function () {
    expect(Sort::make('created_at', alias: 'date'))
        ->toBeInstanceOf(BaseSort::class)
        ->jsonSerialize()->toBe([
            'name' => 'date',
            'hidden' => false,
            'label' => 'Date',
            'metadata' => [],
            'is_active' => false,
            'direction' => null,
            'default' => null,
            'desc' => '-date',
            'asc' => 'date',
            'next' => 'date',
        ]);
});

test('sorts can ascending, descending, or unspecified', function (?string $sort, array $expectedOrder) {
    $sorts = mock_refiner(
        query: array_filter(['sort' => $sort]),
        refiners: [Sort::make('published_at')],
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
        refiners: [Sort::make('published_at', alias: 'date')],
    );

    expect($sorts->pluck('name')->toArray())->toEqual($expectedOrder);
})->with([
    ['date', ['AirPods', 'Macbook Pro M1', 'AirPods Pro']],
    ['-date', ['AirPods Pro', 'Macbook Pro M1', 'AirPods']],
    [null, ['AirPods', 'AirPods Pro', 'Macbook Pro M1']],
    ['published_at', ['AirPods', 'AirPods Pro', 'Macbook Pro M1']],
    ['-published_at', ['AirPods', 'AirPods Pro', 'Macbook Pro M1']],
]);

test('serialization takes current state into account', function () {
    $sorts = mock_refiner(
        query: array_filter(['sort' => '-date']),
        refiners: [Sort::make('published_at', alias: 'date')],
        apply: true,
    );

    expect(data_get(json_decode(json_encode($sorts)), 'sorts.0'))->toMatchArray([
        'name' => 'date',
        'label' => 'Date',
        'metadata' => [],
        'is_active' => true,
        'direction' => 'desc',
        'default' => null,
        'desc' => '-date',
        'asc' => 'date',
        'next' => null,
    ]);
});

test('sorts key is globally configurable', function () {
    Configuration::get()->refining->sortsKey = 'product-sorts';

    $sorts = mock_refiner(
        query: ['product-sorts' => '-name'],
        refiners: [
            Sort::make('name'),
        ],
    );

    expect($sorts)
        ->first()->name->toBe('Macbook Pro M1')
        ->count()->toBe(3);
});

test('sorts key is locally configurable', function () {
    $sorts = mock_refiner(
        query: ['product-sorts' => '-name'],
        refiners: [
            Sort::make('name'),
        ],
    )->sortsKey('product-sorts');

    expect($sorts)
        ->first()->name->toBe('Macbook Pro M1')
        ->count()->toBe(3);
});

test('sorts keys respect the scope', function () {
    $sorts = mock_refiner(
        query: ['products-sorting' => '-name'],
        refiners: [
            Sort::make('name'),
        ],
    )->scope('products')->sortsKey('sorting');

    expect($sorts)
        ->first()->name->toBe('Macbook Pro M1')
        ->count()->toBe(3);
});

test('`next` toggles between possible sorts', function (?string $query, ?string $next) {
    $sorts = mock_refiner(
        query: ['sort' => $query],
        refiners: [
            Sort::make('name'),
        ],
        apply: true,
    );

    expect($sorts->getSorts()[0]->jsonSerialize()['next'])->toBe($next);
})->with([
    [null, 'name'],
    ['name', '-name'],
    ['-name', null],
]);

test('direction cycle can be inverted', function (?string $query, ?string $next) {
    $sorts = mock_refiner(
        query: ['sort' => $query],
        refiners: [
            Sort::make('name')->invertDirectionCycle(),
        ],
        apply: true,
    );

    expect($sorts->getSorts()[0]->jsonSerialize()['next'])->toBe($next);
})->with([
    [null, '-name'],
    ['name', null],
    ['-name', 'name'],
]);

test('order by statements can be unqualified', function () {
    DB::listen(function (QueryExecuted $query) {
        expect($query->sql)->toContain('order by "name"');
    });

    mock_refiner(
        query: ['sort' => 'name'],
        refiners: [
            Sort::make('name')->withoutQualifyingColumn(),
        ],
        apply: true,
    )->get();
});
