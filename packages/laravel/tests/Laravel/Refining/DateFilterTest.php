<?php

use Carbon\CarbonImmutable;
use Hybridly\Refining\Filters\DateFilter;
use Hybridly\Refining\Filters\Operator;
use Hybridly\Refining\Filters\TimeframeSuggestion;
use Hybridly\Refining\Filters\TimeSuggestion;
use Hybridly\Refining\FilterState;
use Hybridly\Refining\RefinementState;
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

    expect($filter->jsonSerialize()['metadata']['suggestions'])
        ->toMatchArray([
            [
                'type' => 'time',
                'label' => 'Yesterday',
                'date' => '2024-01-01T00:00:00+00:00',
                'is_current' => false,
            ],
            [
                'type' => 'time',
                'label' => 'Today',
                'date' => '2024-01-02T00:00:00+00:00',
                'is_current' => false,
            ],
        ]);
});

test('it marks current time suggestion', function () {
    $refiner = mock_refiner(
        query: ['filters' => ['published_at' => ['value' => '2024-01-02', 'operator' => 'equals']]],
        refiners: [
            DateFilter::make('published_at')
                ->suggest([
                    new TimeSuggestion('Yesterday', CarbonImmutable::parse('2024-01-01')),
                    new TimeSuggestion('Today', CarbonImmutable::parse('2024-01-02')),
                ]),
        ],
        apply: true,
    );

    $suggestions = $refiner->getFilters()[0]->jsonSerialize()['metadata']['suggestions'];

    expect($suggestions[0]['is_current'])->toBeFalse();
    expect($suggestions[1]['is_current'])->toBeTrue();
});

test('it marks current timeframe suggestion', function () {
    $refiner = mock_refiner(
        query: ['filters' => ['period' => ['value' => ['start' => '2024-02-01', 'end' => '2024-04-30'], 'operator' => 'between']]],
        refiners: [
            DateFilter::make('period')
                ->timeframe(start: 'published_at', end: 'created_at')
                ->suggest([
                    new TimeframeSuggestion(
                        label: 'Previous quarter',
                        start: CarbonImmutable::parse('2023-11-01'),
                        end: CarbonImmutable::parse('2024-01-31'),
                    ),
                    new TimeframeSuggestion(
                        label: 'Current quarter',
                        start: CarbonImmutable::parse('2024-02-01'),
                        end: CarbonImmutable::parse('2024-04-30'),
                    ),
                ]),
        ],
        apply: true,
    );

    $suggestions = $refiner->getFilters()[0]->jsonSerialize()['metadata']['suggestions'];

    expect($suggestions[0]['is_current'])->toBeFalse();
    expect($suggestions[1]['is_current'])->toBeTrue();
});

test('it marks current timeframe suggestion from default value', function () {
    $refiner = mock_refiner(
        refiners: [
            DateFilter::make('period')
                ->timeframe(start: 'published_at', end: 'created_at')
                ->default([
                    'start' => CarbonImmutable::parse('2024-02-01'),
                    'end' => CarbonImmutable::parse('2024-04-30'),
                ])
                ->suggest([
                    new TimeframeSuggestion(
                        label: 'Current quarter',
                        start: CarbonImmutable::parse('2024-02-01'),
                        end: CarbonImmutable::parse('2024-04-30'),
                    ),
                ]),
        ],
        apply: true,
    );

    $suggestions = $refiner->getFilters()[0]->jsonSerialize()['metadata']['suggestions'];

    expect($suggestions[0]['is_current'])->toBeTrue();
});

test('it does not mark suggestions current without a current value', function () {
    $serializedTimeFilter = DateFilter::make('published_at')
        ->suggest([
            new TimeSuggestion('Today', CarbonImmutable::parse('2024-01-02')),
        ])
        ->jsonSerialize();

    $serializedTimeframeFilter = DateFilter::make('period')
        ->timeframe(start: 'published_at', end: 'created_at')
        ->suggest([
            new TimeframeSuggestion(
                label: 'Current quarter',
                start: CarbonImmutable::parse('2024-02-01'),
                end: CarbonImmutable::parse('2024-04-30'),
            ),
        ])
        ->jsonSerialize();

    expect($serializedTimeFilter['metadata']['suggestions'][0]['is_current'])->toBeFalse();
    expect($serializedTimeframeFilter['metadata']['suggestions'][0]['is_current'])->toBeFalse();
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
    expect($filters->get())
        ->sequence(
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
    expect($filters->get())
        ->sequence(
            fn (Expectation $product) => $product->published_at->format('Y-m-d')->toBe('2024-01-15'),
            fn (Expectation $product) => $product->published_at->format('Y-m-d')->toBe('2024-02-20'),
        );
});

test('nullary date operators do not parse an absent value as today', function (string $operator, int $expectedCount) {
    $refiner = mock_refiner(
        query: ['filters' => ['published_at' => ['operator' => $operator]]],
        refiners: [DateFilter::make('published_at')],
    );

    expect($refiner->count())->toBe($expectedCount);
})->with([
    'is null' => ['is_null', 0],
    'is not null' => ['is_not_null', 5],
]);

test('value-less non-nullary date requests are inactive', function (mixed $value) {
    $refiner = mock_refiner(
        query: ['filters' => ['published_at' => ['value' => $value, 'operator' => 'equals']]],
        refiners: [DateFilter::make('published_at')],
    );

    expect($refiner->count())->toBe(5);
    expect($refiner->getFilters()[0]->jsonSerialize())->toMatchArray([
        'is_active' => false,
        'value' => null,
    ]);
})->with([
    'null' => [null],
    'empty string' => [''],
    'blank string' => ['   '],
    'array' => [[]],
]);

test('malformed timeframe date requests are inactive', function (mixed $value) {
    $refiner = mock_refiner(
        query: ['filters' => ['period' => ['value' => $value, 'operator' => 'between']]],
        refiners: [DateFilter::make('period')->timeframe(start: 'published_at', end: 'created_at')],
    );

    expect($refiner->count())->toBe(5);
    expect($refiner->getFilters()[0]->jsonSerialize()['is_active'])->toBeFalse();
})->with([
    'null' => [null],
    'empty array' => [[]],
    'missing end' => [['start' => '2024-01-01']],
    'blank start' => [['start' => '', 'end' => '2024-01-31']],
    'blank end' => [['start' => '2024-01-01', 'end' => ' ']],
]);

test('value-less non-nullary date baselines are inactive', function () {
    $refiner = mock_refiner(
        refiners: [DateFilter::make('published_at')],
    )->withBaseline(new RefinementState(filters: [
        'published_at' => new FilterState(value: null, operator: Operator::EQUALS),
    ]));

    expect($refiner->count())->toBe(5);
    expect($refiner->getFilters()[0]->jsonSerialize())->toMatchArray([
        'is_active' => false,
        'has_default' => false,
        'value' => null,
    ]);
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
    expect($filters->get())
        ->sequence(
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

test('it uses timeframe suggestion label for current value label when dates match', function () {
    $refiner = mock_refiner(
        query: ['filters' => ['period' => ['value' => ['start' => '2024-02-01', 'end' => '2024-04-30'], 'operator' => 'between']]],
        refiners: [
            DateFilter::make('period')
                ->timeframe(start: 'published_at', end: 'created_at')
                ->suggest([
                    new TimeframeSuggestion(
                        label: 'Current quarter',
                        start: CarbonImmutable::parse('2024-02-01'),
                        end: CarbonImmutable::parse('2024-04-30'),
                    ),
                ]),
        ],
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $serialized = $filter->jsonSerialize();

    expect($serialized['metadata']['current_value_label'])->toBe('Current quarter');
});

test('it serializes stable suggestion keys', function () {
    $filter = DateFilter::make('period')
        ->timeframe(start: 'published_at', end: 'created_at')
        ->suggest([
            new TimeframeSuggestion(
                label: 'Today',
                start: CarbonImmutable::parse('2024-02-01'),
                end: CarbonImmutable::parse('2024-02-01'),
                key: 'today',
            ),
        ]);

    expect($filter->jsonSerialize()['metadata']['suggestions'][0])->toMatchArray([
        'key' => 'today',
        'is_current' => false,
    ]);
});

test('it resolves keyed semantic baseline suggestions on each request', function () {
    CarbonImmutable::setTestNow('2024-02-20 12:00:00');

    $makeRefiner = static fn () => mock_refiner(
        refiners: [
            DateFilter::make('published_at')
                ->suggest([
                    new TimeSuggestion(
                        label: 'Today',
                        date: CarbonImmutable::now(),
                        key: 'today',
                    ),
                ]),
        ],
    )->withBaseline(new RefinementState(filters: [
        'published_at' => new FilterState(
            value: null,
            suggestionKey: 'today',
        ),
    ]));

    expect($makeRefiner()->get()->pluck('published_at')->map->format('Y-m-d')->all())->toBe(['2024-02-20']);

    CarbonImmutable::setTestNow('2024-03-10 12:00:00');

    expect($makeRefiner()->get()->pluck('published_at')->map->format('Y-m-d')->all())->toBe(['2024-03-10']);
});

test('it resolves keyed request suggestions and preserves exact custom ranges', function () {
    $suggestions = [
        new TimeframeSuggestion(
            label: 'First quarter',
            start: CarbonImmutable::parse('2024-01-01'),
            end: CarbonImmutable::parse('2024-03-31'),
            key: 'first-quarter',
        ),
    ];

    $semantic = mock_refiner(
        query: [
            'filters' => [
                'period' => [
                    'value' => ['start' => '2000-01-01', 'end' => '2000-01-02'],
                    'suggestion_key' => 'first-quarter',
                    'operator' => 'between',
                ],
            ],
        ],
        refiners: [
            DateFilter::make('period')
                ->timeframe(start: 'published_at', end: 'published_at')
                ->suggest($suggestions),
        ],
    );

    expect($semantic->get())->toHaveCount(3);
    expect($semantic->getFilters()[0]->jsonSerialize())->toMatchArray([
        'value' => [
            'start' => '2024-01-01T00:00:00+00:00',
            'end' => '2024-03-31T00:00:00+00:00',
        ],
        'suggestion_key' => 'first-quarter',
    ]);

    $exact = mock_refiner(
        query: [
            'filters' => [
                'period' => [
                    'value' => ['start' => '2024-02-01', 'end' => '2024-04-30'],
                    'operator' => 'between',
                ],
            ],
        ],
        refiners: [
            DateFilter::make('period')
                ->timeframe(start: 'published_at', end: 'published_at')
                ->suggest($suggestions),
        ],
    );

    expect($exact->get())->toHaveCount(3);
    expect($exact->getFilters()[0]->jsonSerialize())->toMatchArray([
        'value' => [
            'start' => '2024-02-01T00:00:00+00:00',
            'end' => '2024-04-30T00:00:00+00:00',
        ],
        'suggestion_key' => null,
    ]);
});

test('it deactivates unknown semantic baseline suggestions', function () {
    $refiner = mock_refiner(
        refiners: [
            DateFilter::make('published_at')
                ->suggest([
                    new TimeSuggestion(
                        label: 'Today',
                        date: CarbonImmutable::parse('2024-02-20'),
                        key: 'today',
                    ),
                ]),
        ],
    )->withBaseline(new RefinementState(filters: [
        'published_at' => new FilterState(
            value: '2000-01-01',
            suggestionKey: 'removed-suggestion',
        ),
    ]));

    expect($refiner->get())->toHaveCount(5);
    expect($refiner->getFilters()[0]->jsonSerialize())->toMatchArray([
        'is_active' => false,
        'value' => null,
        'default' => null,
        'has_default' => false,
        'suggestion_key' => null,
    ]);
});

test('it deactivates unknown semantic request suggestions', function () {
    $refiner = mock_refiner(
        query: [
            'filters' => [
                'published_at' => [
                    'value' => '2000-01-01',
                    'suggestion_key' => 'removed-suggestion',
                ],
            ],
        ],
        refiners: [
            DateFilter::make('published_at')
                ->suggest([
                    new TimeSuggestion(
                        label: 'Today',
                        date: CarbonImmutable::parse('2024-02-20'),
                        key: 'today',
                    ),
                ]),
        ],
    );

    expect($refiner->get())->toHaveCount(5);
    expect($refiner->getFilters()[0]->jsonSerialize())->toMatchArray([
        'is_active' => false,
        'value' => null,
        'suggestion_key' => null,
    ]);
});

test('it deactivates semantic suggestions with the wrong suggestion type', function () {
    $refiner = mock_refiner(
        refiners: [
            DateFilter::make('published_at')
                ->suggest([
                    new TimeframeSuggestion(
                        label: 'Today',
                        start: CarbonImmutable::parse('2024-02-20'),
                        end: CarbonImmutable::parse('2024-02-20'),
                        key: 'today',
                    ),
                ]),
        ],
    )->withBaseline(new RefinementState(filters: [
        'published_at' => new FilterState(
            value: null,
            suggestionKey: 'today',
        ),
    ]));

    expect($refiner->get())->toHaveCount(5);
    expect($refiner->getFilters()[0]->jsonSerialize()['has_default'])->toBeFalse();
});
