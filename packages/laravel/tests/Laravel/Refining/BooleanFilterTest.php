<?php

use Hybridly\Refining\Filters\BooleanFilter;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Illuminate\Contracts\Database\Eloquent\Builder;

beforeEach(function () {
    ProductFactory::new()->create(['name' => 'AirPods Pro', 'is_active' => true]);
    ProductFactory::new()->create(['name' => 'Macbook Pro M1', 'is_active' => false]);
});

test('it converts the value to boolean', function (mixed $bool, ?string $name, int $count) {
    $filters = mock_refiner(
        query: $bool === null ? [] : ['filters' => ['active' => ['value' => $bool]]],
        refiners: [
            BooleanFilter::make('is_active', alias: 'active'),
        ],
    );

    expect($filters)->count()->toBe($count);

    if ($count === 1) {
        expect($filters->first()->name)->toBe($name);
    }
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
    // Filter not applied when not in query
    [null, null, 2],
]);

test('it can use custom true query', function () {
    $result = mock_refiner(
        query: ['filters' => ['active' => ['value' => true]]],
        refiners: [
            BooleanFilter::make('is_active', alias: 'active')
                ->queries(
                    true: fn (Builder $query) => $query->where('is_active', true),
                ),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->name->toBe('AirPods Pro');
});

test('it can use custom false query', function () {
    $result = mock_refiner(
        query: ['filters' => ['active' => ['value' => false]]],
        refiners: [
            BooleanFilter::make('is_active', alias: 'active')
                ->queries(
                    false: fn (Builder $query) => $query->where('is_active', false),
                ),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->name->toBe('Macbook Pro M1');
});

test('it falls back to default behavior when no custom query is provided', function () {
    $result = mock_refiner(
        query: ['filters' => ['active' => ['value' => true]]],
        refiners: [
            BooleanFilter::make('is_active', alias: 'active')
                ->queries(
                    false: fn (Builder $query) => $query->where('is_active', false),
                ),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->name->toBe('AirPods Pro');
});

test('it includes labels in metadata', function () {
    $filter = BooleanFilter::make('is_active')
        ->trueLabel('Active items')
        ->falseLabel('Inactive items');

    $serialized = $filter->jsonSerialize();

    expect($serialized['metadata'])
        ->toHaveKey('true_label', 'Active items')
        ->toHaveKey('false_label', 'Inactive items');
});

test('it shows current value label when filter is active', function () {
    $refiner = mock_refiner(
        query: ['filters' => ['active' => ['value' => true]]],
        refiners: [
            BooleanFilter::make('is_active', alias: 'active')
                ->trueLabel('Active items')
                ->falseLabel('Inactive items'),
        ],
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata['current_value_label'])->toBe('Active items');
});

test('it supports closures for labels', function () {
    $filter = BooleanFilter::make('is_active')
        ->trueLabel(fn () => 'Dynamic true label')
        ->falseLabel(fn () => 'Dynamic false label');

    $serialized = $filter->jsonSerialize();

    expect($serialized['metadata'])
        ->toHaveKey('true_label', 'Dynamic true label')
        ->toHaveKey('false_label', 'Dynamic false label');
});

test('it injects builder parameter by type', function () {
    $result = mock_refiner(
        query: ['filters' => ['active' => ['value' => true]]],
        refiners: [
            BooleanFilter::make('is_active', alias: 'active')
                ->queries(
                    true: fn (Builder $qb) => $qb->where('is_active', true),
                ),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->name->toBe('AirPods Pro');
});

test('it injects named parameters', function () {
    $appliedValue = null;
    $appliedProperty = null;

    mock_refiner(
        query: ['filters' => ['active' => ['value' => false]]],
        refiners: [
            BooleanFilter::make('is_active', alias: 'active')
                ->queries(
                    false: function (Builder $query, mixed $value, string $property) use (&$appliedValue, &$appliedProperty) {
                        $appliedValue = $value;
                        $appliedProperty = $property;

                        return $query;
                    },
                ),
        ],
    )->get();

    expect($appliedValue)->toBeFalse();
    expect($appliedProperty)->toBe('is_active');
});
