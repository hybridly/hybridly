<?php

use Carbon\CarbonImmutable;
use Hybridly\Refining\Filters\DateFilter;
use Hybridly\Refining\Filters\TimeframeSuggestion;
use Hybridly\Refining\Filters\TimeSuggestion;
use Hybridly\Tests\Fixtures\Database\Product;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Pest\Expectation;

beforeEach(function () {
    ProductFactory::new()->create(['published_at' => '2024-01-15 10:00:00']);
    ProductFactory::new()->create(['published_at' => '2024-02-20 14:30:00']);
    ProductFactory::new()->create(['published_at' => '2024-03-10 08:15:00']);
    ProductFactory::new()->create(['published_at' => '2024-04-05 16:45:00']);
    ProductFactory::new()->create(['published_at' => '2024-05-25 12:00:00']);
});

it('can be serialized', function () {
    $filter = DateFilter::make('published_at')
        ->metadata(['foo' => 'bar'])
        ->label('Published date');

    $serialized = $filter->jsonSerialize();

    expect($serialized)
        ->toMatchArray([
            'name' => 'published_at',
            'hidden' => false,
            'label' => 'Published date',
            'type' => 'date',
            'is_active' => false,
            'value' => null,
            'default' => null,
        ]);

    expect($serialized['metadata'])
        ->toHaveKey('foo', 'bar');
});

it('can serialize with suggestions', function () {
    $filter = DateFilter::make('published_at')
        ->suggest([
            new TimeSuggestion('Yesterday', CarbonImmutable::parse('2024-01-01')),
            new TimeSuggestion('Today', CarbonImmutable::parse('2024-01-02')),
        ]);

    expect($filter->jsonSerialize()['metadata']['suggestions'])->toHaveCount(2);
});

it('can serialize timeframe filter', function () {
    $filter = DateFilter::make('active_period')
        ->timeframe(start: 'published_at', end: 'created_at');

    $serialized = $filter->jsonSerialize();

    expect($serialized['metadata'])
        ->toHaveKey('is_timeframe', true)
        ->toHaveKey('start_column', 'published_at')
        ->toHaveKey('end_column', 'created_at');
});

test('it can filter with equals operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['published_at' => ['value' => '2024-02-20', 'operator' => 'equals']]],
        refiners: [
            DateFilter::make('published_at'),
        ],
    );

    expect($filters)->count()->toBe(1);
    expect($filters->first()->published_at->format('Y-m-d'))->toBe('2024-02-20');
});

test('it can filter with not equals operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['published_at' => ['value' => '2024-02-20', 'operator' => 'not_equals']]],
        refiners: [
            DateFilter::make('published_at'),
        ],
    );

    expect($filters)->count()->toBe(4);
    expect($filters->pluck('published_at')->map->format('Y-m-d'))
        ->not
        ->toContain('2024-02-20');
});

test('it can filter with after operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['published_at' => ['value' => '2024-03-01', 'operator' => 'after']]],
        refiners: [
            DateFilter::make('published_at'),
        ],
    );

    expect($filters)->count()->toBe(3);
    expect($filters->get())->sequence(
        fn (Expectation $product) => $product->published_at->format('Y-m-d')->toBe('2024-03-10'),
        fn (Expectation $product) => $product->published_at->format('Y-m-d')->toBe('2024-04-05'),
        fn (Expectation $product) => $product->published_at->format('Y-m-d')->toBe('2024-05-25'),
    );
});

test('it can filter with before operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['published_at' => ['value' => '2024-03-01', 'operator' => 'before']]],
        refiners: [
            DateFilter::make('published_at'),
        ],
    );

    expect($filters)->count()->toBe(2);
    expect($filters->get())->sequence(
        fn (Expectation $product) => $product->published_at->format('Y-m-d')->toBe('2024-01-15'),
        fn (Expectation $product) => $product->published_at->format('Y-m-d')->toBe('2024-02-20'),
    );
});

test('it can filter with timeframe between operator', function () {
    // Clear beforeEach data and create specific data with known dates
    Product::query()->delete();
    ProductFactory::new()->create([
        'published_at' => '2024-02-10 10:00:00',
        'created_at' => '2024-03-15 10:00:00',
    ]);
    ProductFactory::new()->create([
        'published_at' => '2024-03-01 10:00:00',
        'created_at' => '2024-04-15 10:00:00',
    ]);
    ProductFactory::new()->create([
        'published_at' => '2024-01-01 10:00:00',
        'created_at' => '2024-05-15 10:00:00',
    ]);

    $filters = mock_refiner(
        query: ['filters' => ['period' => ['value' => ['start' => '2024-02-01', 'end' => '2024-04-30'], 'operator' => 'between']]],
        refiners: [
            DateFilter::make('period')->timeframe(start: 'published_at', end: 'created_at'),
        ],
    );

    // Should match records where published_at >= 2024-02-01 AND created_at <= 2024-04-30
    expect($filters)->count()->toBe(2);
});

test('it can filter with timeframe not between operator', function () {
    $filters = mock_refiner(
        query: ['filters' => ['period' => ['value' => ['start' => '2024-02-15', 'end' => '2024-03-15'], 'operator' => 'not_between']]],
        refiners: [
            DateFilter::make('period')->timeframe(start: 'published_at', end: 'created_at'),
        ],
    );

    expect($filters)->count()->toBeGreaterThan(0);
});

test('it can use custom query callback', function () {
    $filters = mock_refiner(
        query: ['filters' => ['published_at' => ['value' => '2024-02-20', 'operator' => 'equals']]],
        refiners: [
            DateFilter::make('published_at')
                ->query(function ($builder, $date) {
                    $builder->whereDate('published_at', '>=', $date);
                }),
        ],
    );

    expect($filters)->count()->toBe(4);
    expect($filters->get())->sequence(
        fn (Expectation $product) => $product->published_at->format('Y-m-d')->toBe('2024-02-20'),
        fn (Expectation $product) => $product->published_at->format('Y-m-d')->toBe('2024-03-10'),
        fn (Expectation $product) => $product->published_at->format('Y-m-d')->toBe('2024-04-05'),
        fn (Expectation $product) => $product->published_at->format('Y-m-d')->toBe('2024-05-25'),
    );
});

test('it can use custom query callback for timeframe', function () {
    $filters = mock_refiner(
        query: ['filters' => ['period' => ['value' => ['start' => '2024-02-01', 'end' => '2024-04-30'], 'operator' => 'between']]],
        refiners: [
            DateFilter::make('period')
                ->timeframe(start: 'published_at', end: 'created_at')
                ->query(function ($builder, $start, $end) {
                    $builder->whereBetween('published_at', [$start, $end]);
                }),
        ],
    );

    expect($filters)->count()->toBe(3);
});

test('it provides current value label for single date', function () {
    $refiner = mock_refiner(
        query: ['filters' => ['published_at' => ['value' => '2024-02-20', 'operator' => 'equals']]],
        refiners: [
            DateFilter::make('published_at'),
        ],
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $serialized = $filter->jsonSerialize();

    expect($serialized['metadata']['current_value_label'])->toBe('Feb 20, 2024');
});

test('it provides current value label for timeframe', function () {
    $refiner = mock_refiner(
        query: ['filters' => ['period' => ['value' => ['start' => '2024-02-01', 'end' => '2024-04-30'], 'operator' => 'between']]],
        refiners: [
            DateFilter::make('period')->timeframe(start: 'published_at', end: 'created_at'),
        ],
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $serialized = $filter->jsonSerialize();

    expect($serialized['metadata']['current_value_label'])->toBe('Feb 1, 2024 - Apr 30, 2024');
});
