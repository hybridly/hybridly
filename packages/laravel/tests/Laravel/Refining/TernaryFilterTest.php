<?php

use Hybridly\Refining\Filters\BaseFilter;
use Hybridly\Refining\Filters\TernaryFilter;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Illuminate\Contracts\Database\Eloquent\Builder;

beforeEach(function () {
    ProductFactory::new()->create(['name' => 'Active Product', 'is_active' => true]);
    ProductFactory::new()->create(['name' => 'Inactive Product', 'is_active' => false]);
    ProductFactory::new()->create(['name' => 'Another Active', 'is_active' => true]);
});

it('can be serialized', function () {
    $filter = TernaryFilter::make('active')
        ->placeholder('Select status')
        ->trueLabel('All items')
        ->falseLabel('Only inactive')
        ->queries(
            true: fn (Builder $query) => $query,
            false: fn (Builder $query) => $query->where('is_active', false),
            blank: fn (Builder $query) => $query->where('is_active', true),
        )
        ->metadata([
            'custom' => 'data',
        ]);

    expect($filter)
        ->toBeInstanceOf(BaseFilter::class)
        ->jsonSerialize()
        ->toBe([
            'name' => 'active',
            'hidden' => false,
            'label' => 'Active',
            'type' => 'ternary',
            'metadata' => [
                'custom' => 'data',
                'true_label' => 'All items',
                'false_label' => 'Only inactive',
                'placeholder' => 'Select status',
            ],
            'is_active' => false,
            'value' => null,
            'default' => null,
        ]);
});

it('applies the true query when value is true', function () {
    $result = mock_refiner(
        query: ['filters' => ['status' => true]],
        refiners: [
            TernaryFilter::make('is_active', alias: 'status')
                ->queries(
                    true: fn (Builder $query) => $query,
                    false: fn (Builder $query) => $query->where('is_active', false),
                    blank: fn (Builder $query) => $query->where('is_active', true),
                ),
        ],
    )->get();

    expect($result)->toHaveCount(3);
});

it('applies the false query when value is false', function () {
    $result = mock_refiner(
        query: ['filters' => ['status' => false]],
        refiners: [
            TernaryFilter::make('is_active', alias: 'status')
                ->queries(
                    true: fn (Builder $query) => $query,
                    false: fn (Builder $query) => $query->where('is_active', false),
                    blank: fn (Builder $query) => $query->where('is_active', true),
                ),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->name->toBe('Inactive Product');
});

it('applies the blank query when value is not provided', function () {
    $result = mock_refiner(
        query: ['filters' => []],
        refiners: [
            TernaryFilter::make('is_active')
                ->queries(
                    true: fn (Builder $query) => $query,
                    false: fn (Builder $query) => $query->where('is_active', false),
                    blank: fn (Builder $query) => $query->where('is_active', true),
                ),
        ],
    )->get();

    expect($result)
        ->toHaveCount(2)
        ->pluck('name')
        ->toContain('Active Product')
        ->toContain('Another Active');
});

it('normalizes string true values', function () {
    $result = mock_refiner(
        query: ['filters' => ['status' => '1']],
        refiners: [
            TernaryFilter::make('is_active', alias: 'status')
                ->queries(
                    true: fn (Builder $query) => $query,
                    false: fn (Builder $query) => $query->where('is_active', false),
                    blank: fn (Builder $query) => $query->where('is_active', true),
                ),
        ],
    )->get();

    expect($result)->toHaveCount(3);
});

it('normalizes string false values', function () {
    $result = mock_refiner(
        query: ['filters' => ['status' => '0']],
        refiners: [
            TernaryFilter::make('is_active', alias: 'status')
                ->queries(
                    true: fn (Builder $query) => $query,
                    false: fn (Builder $query) => $query->where('is_active', false),
                    blank: fn (Builder $query) => $query->where('is_active', true),
                ),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->name->toBe('Inactive Product');
});

it('works without a blank query', function () {
    $result = mock_refiner(
        query: ['filters' => []],
        refiners: [
            TernaryFilter::make('is_active')
                ->queries(
                    true: fn (Builder $query) => $query,
                    false: fn (Builder $query) => $query->where('is_active', false),
                ),
        ],
    )->get();

    expect($result)->toHaveCount(3);
});

it('works without a true query', function () {
    $result = mock_refiner(
        query: ['filters' => ['status' => true]],
        refiners: [
            TernaryFilter::make('is_active', alias: 'status')
                ->queries(
                    false: fn (Builder $query) => $query->where('is_active', false),
                    blank: fn (Builder $query) => $query->where('is_active', true),
                ),
        ],
    )->get();

    expect($result)->toHaveCount(3);
});

it('works without a false query', function () {
    $result = mock_refiner(
        query: ['filters' => ['status' => false]],
        refiners: [
            TernaryFilter::make('is_active', alias: 'status')
                ->queries(
                    true: fn (Builder $query) => $query,
                    blank: fn (Builder $query) => $query->where('is_active', true),
                ),
        ],
    )->get();

    expect($result)->toHaveCount(3);
});

it('injects builder parameter by type', function () {
    $result = mock_refiner(
        query: ['filters' => ['status' => true]],
        refiners: [
            TernaryFilter::make('is_active', alias: 'status')
                ->queries(
                    true: fn (Builder $qb) => $qb->where('is_active', true),
                ),
        ],
    )->get();

    expect($result)
        ->toHaveCount(2)
        ->pluck('name')
        ->toContain('Active Product')
        ->toContain('Another Active');
});

it('injects named parameters', function () {
    $appliedValue = null;
    $appliedProperty = null;

    mock_refiner(
        query: ['filters' => ['status' => true]],
        refiners: [
            TernaryFilter::make('is_active', alias: 'status')
                ->queries(
                    true: function (Builder $query, mixed $value, string $property) use (&$appliedValue, &$appliedProperty) {
                        $appliedValue = $value;
                        $appliedProperty = $property;

                        return $query;
                    },
                ),
        ],
    )->get();

    expect($appliedValue)->toBe(true);
    expect($appliedProperty)->toBe('is_active');
});

it('supports closures for labels and placeholder', function () {
    $filter = TernaryFilter::make('active')
        ->placeholder(fn () => 'Dynamic placeholder')
        ->trueLabel(fn () => 'Dynamic true')
        ->falseLabel(fn () => 'Dynamic false');

    $serialized = $filter->jsonSerialize();

    expect($serialized['metadata'])
        ->toHaveKey('placeholder', 'Dynamic placeholder')
        ->toHaveKey('true_label', 'Dynamic true')
        ->toHaveKey('false_label', 'Dynamic false');
});

it('applies complex queries with method chaining', function () {
    $result = mock_refiner(
        query: ['filters' => ['status' => false]],
        refiners: [
            TernaryFilter::make('is_active', alias: 'status')
                ->queries(
                    true: fn (Builder $query) => $query,
                    false: fn (Builder $query) => $query
                        ->where('is_active', false),
                    blank: fn (Builder $query) => $query->where('is_active', true),
                ),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->name->toBe('Inactive Product');
});
