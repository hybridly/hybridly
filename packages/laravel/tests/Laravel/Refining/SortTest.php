<?php

use Hybridly\Configuration\Configuration;
use Hybridly\Refining\RefinementState;
use Hybridly\Refining\Sorts\BaseSort;
use Hybridly\Refining\Sorts\Sort;
use Hybridly\Refining\SortState;
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
        ->jsonSerialize()
        ->toBe([
            'name' => 'created_at',
            'hidden' => false,
            'label' => 'Creation date',
            'metadata' => [
                'foo' => 'bar',
            ],
            'is_active' => false,
            'direction' => null,
            'default' => null,
            'has_default' => false,
            'current_order' => null,
            'default_order' => null,
            'is_overridden' => false,
            'is_cleared' => false,
            'desc' => '-created_at',
            'asc' => 'created_at',
            'next' => 'created_at',
        ]);
});

it('serializes default directions as explicitly configured', function () {
    expect(Sort::make('created_at')->default('desc')->jsonSerialize())
        ->toMatchArray([
            'default' => 'desc',
            'has_default' => true,
        ]);
});

it('treats conditionally null default directions as absent', function () {
    $sort = Sort::make('published_at')->default(fn (): ?string => null);

    expect($sort->jsonSerialize())->toMatchArray([
        'default' => null,
        'has_default' => false,
    ]);

    $sorts = mock_refiner(refiners: [$sort]);

    expect($sorts->toSql())->not->toContain('order by');
    expect($sorts->getSorts()[0]->jsonSerialize())->toMatchArray([
        'is_active' => false,
        'direction' => null,
        'default' => null,
        'has_default' => false,
    ]);
});

it('uses its alias as name when serialized', function () {
    expect(Sort::make('created_at', alias: 'date'))
        ->toBeInstanceOf(BaseSort::class)
        ->jsonSerialize()
        ->toBe([
            'name' => 'date',
            'hidden' => false,
            'label' => 'Date',
            'metadata' => [],
            'is_active' => false,
            'direction' => null,
            'default' => null,
            'has_default' => false,
            'current_order' => null,
            'default_order' => null,
            'is_overridden' => false,
            'is_cleared' => false,
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

test('sorts apply default directions unchanged', function () {
    $sorts = mock_refiner(
        refiners: [Sort::make('published_at')->default('desc')],
    );

    expect($sorts->pluck('name')->toArray())->toEqual(['AirPods Pro', 'Macbook Pro M1', 'AirPods']);
});

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

    expect(data_get(json_decode(json_encode($sorts)), 'sorts.0'))
        ->toMatchArray([
            'name' => 'date',
            'label' => 'Date',
            'metadata' => [],
            'is_active' => true,
            'direction' => 'desc',
            'default' => null,
            'has_default' => false,
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
        ->first()
        ->name
        ->toBe('Macbook Pro M1')
        ->count()
        ->toBe(3);
});

test('sorts key is locally configurable', function () {
    $sorts = mock_refiner(
        query: ['product-sorts' => '-name'],
        refiners: [
            Sort::make('name'),
        ],
    )->sortsKey('product-sorts');

    expect($sorts)
        ->first()
        ->name
        ->toBe('Macbook Pro M1')
        ->count()
        ->toBe(3);
});

test('sorts keys respect the scope', function () {
    $sorts = mock_refiner(
        query: ['products-sorting' => '-name'],
        refiners: [
            Sort::make('name'),
        ],
    )
        ->scope('products')
        ->sortsKey('sorting');

    expect($sorts)
        ->first()
        ->name
        ->toBe('Macbook Pro M1')
        ->count()
        ->toBe(3);
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

test('replacement baselines supersede declared sort defaults and preserve order', function () {
    $sorts = mock_refiner(
        refiners: [
            Sort::make('name')->default('asc'),
            Sort::make('published_at')->default('asc'),
        ],
    )->withBaseline(new RefinementState(sorts: [
        new SortState(name: 'published_at', direction: 'desc'),
        new SortState(name: 'name', direction: 'asc'),
    ]));

    expect($sorts->toSql())->toContain('order by "products"."published_at" desc, "products"."name" asc');

    $serialized = $sorts->getSorts();
    expect($serialized[0]->jsonSerialize())->toMatchArray([
        'name' => 'name',
        'direction' => 'asc',
        'default' => 'asc',
        'current_order' => 1,
        'default_order' => 1,
        'is_overridden' => false,
    ]);
    expect($serialized[1]->jsonSerialize())->toMatchArray([
        'name' => 'published_at',
        'direction' => 'desc',
        'default' => 'desc',
        'current_order' => 0,
        'default_order' => 0,
        'is_overridden' => false,
    ]);
});

test('request sorts override replacement baselines', function () {
    $sorts = mock_refiner(
        query: ['sort' => 'name,-published_at'],
        refiners: [
            Sort::make('published_at'),
            Sort::make('name'),
        ],
    )->withBaseline(new RefinementState(sorts: [
        new SortState(name: 'published_at', direction: 'desc'),
        new SortState(name: 'name', direction: 'asc'),
    ]));

    expect($sorts->toSql())->toContain('order by "products"."name" asc, "products"."published_at" desc');
    expect($sorts->getSorts()[0]->jsonSerialize()['is_overridden'])->toBeTrue();
    expect($sorts->getSorts()[1]->jsonSerialize()['is_overridden'])->toBeTrue();
});

test('explicit sort requests preserve declared sole defaults as ordered siblings', function () {
    $sorts = mock_refiner(
        query: ['sort' => 'name,-published_at'],
        refiners: [
            Sort::make('published_at')->default('desc'),
            Sort::make('name'),
        ],
    );

    expect($sorts->toSql())->toContain('order by "products"."name" asc, "products"."published_at" desc');
    expect($sorts->getSorts()[0]->jsonSerialize())->toMatchArray([
        'name' => 'published_at',
        'direction' => 'desc',
        'current_order' => 1,
    ]);
});

test('request sorts equal to replacement baselines are not overridden', function () {
    $sorts = mock_refiner(
        query: ['sort' => '-published_at,name'],
        refiners: [
            Sort::make('published_at'),
            Sort::make('name'),
        ],
    )->withBaseline(new RefinementState(sorts: [
        new SortState(name: 'published_at', direction: 'desc'),
        new SortState(name: 'name', direction: 'asc'),
    ]));

    $sorts->applyRefiners();

    expect($sorts->getSorts()[0]->jsonSerialize()['is_overridden'])->toBeFalse();
    expect($sorts->getSorts()[1]->jsonSerialize()['is_overridden'])->toBeFalse();
});

test('all effective default sorts can be explicitly cleared', function () {
    $sorts = mock_refiner(
        query: ['sort_cleared' => true],
        refiners: [Sort::make('published_at')->default('asc')],
    )->withBaseline(new RefinementState(sorts: [
        new SortState(name: 'published_at', direction: 'desc'),
    ]));

    expect($sorts->toSql())->not->toContain('order by');
    expect($sorts->getSorts()[0]->jsonSerialize())->toMatchArray([
        'is_active' => false,
        'direction' => null,
        'default' => 'desc',
        'has_default' => true,
        'is_overridden' => true,
        'is_cleared' => true,
    ]);
});

test('empty replacement baselines suppress declared sort defaults in queries and metadata', function () {
    $sorts = mock_refiner(
        refiners: [Sort::make('published_at')->default('desc')],
        apply: false,
    )->withBaseline(new RefinementState());

    $sorts->applyRefiners();

    expect($sorts->toSql())->not->toContain('order by');
    expect($sorts->getSorts()[0]->jsonSerialize())->toMatchArray([
        'is_active' => false,
        'direction' => null,
        'default' => null,
        'has_default' => false,
        'current_order' => null,
        'default_order' => null,
        'is_overridden' => false,
        'is_cleared' => false,
    ]);
});
