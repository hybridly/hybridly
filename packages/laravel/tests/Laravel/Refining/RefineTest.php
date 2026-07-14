<?php

use Hybridly\Refining\Filters\Operator;
use Hybridly\Refining\Filters\TextFilter;
use Hybridly\Refining\FilterState;
use Hybridly\Refining\Group;
use Hybridly\Refining\RefinementState;
use Hybridly\Refining\Sorts\Sort;
use Hybridly\Refining\SortState;
use Hybridly\Tests\Fixtures\Database\ProductFactory;

beforeEach(function () {
    ProductFactory::new()->create(['name' => 'AirPods', 'published_at' => '2020-01-01']);
    ProductFactory::new()->create(['name' => 'AirPods Pro', 'published_at' => '2020-01-02']);
    ProductFactory::new()->create(['name' => 'Macbook Pro M1', 'published_at' => '2020-01-03']);
});

test('refiners should execute only once', function () {
    $refine = mock_refiner(
        query: array_filter(['sort' => '-date']),
        refiners: [
            $refiner = Mockery::spy(Sort::make('published_at', alias: 'date')),
        ],
    );

    $refiner->shouldNotHaveReceived('refine');
    $refine->applyRefiners();
    $refiner->shouldHaveReceived('refine')->once();
    $refine->applyRefiners();
    $refiner->shouldHaveReceived('refine')->once();

    Mockery::close();

    expect($refine->get()->map->name)
        ->toMatchArray([
            'Macbook Pro M1',
            'AirPods Pro',
            'AirPods',
        ]);
});

test('the refine instance can be serialized', function () {
    $refine = mock_refiner(
        refiners: [
            Sort::make('created_at', alias: 'date'),
            TextFilter::make('name'),
        ],
    )
        ->scope('products');

    expect(json_decode(json_encode($refine), associative: true))
        ->toBe([
            'sorts' => [
                [
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
                ],
            ],
            'filters' => [
                [
                    'name' => 'name',
                    'hidden' => false,
                    'label' => 'Name',
                    'type' => 'text',
                    'icon' => null,
                    'metadata' => [
                        'current_value_label' => null,
                    ],
                    'is_active' => false,
                    'value' => null,
                    'search_query' => null,
                    'operator' => 'equals',
                    'default_operator' => 'equals',
                    'supported_operators' => [
                        'equals',
                        'not_equals',
                        'contains',
                        'not_contains',
                        'begins_with',
                        'ends_with',
                        'is_empty',
                        'is_not_empty',
                        'is_null',
                        'is_not_null',
                    ],
                    'default' => null,
                    'has_default' => false,
                    'default_options' => [],
                    'default_suggestion_key' => null,
                    'suggestion_key' => null,
                    'is_overridden' => false,
                    'is_cleared' => false,
                    'options' => [],
                ],
            ],
            'scope' => 'products',
            'keys' => [
                'sorts' => 'products-sort',
                'sorts_cleared' => 'products-sort_cleared',
                'filters' => 'products-filters',
            ],
        ]);
});

it('serializes flattened filters and sorts when grouping', function () {
    $refiner = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'AirPods']]],
        refiners: [
            Sort::make('created_at', alias: 'date'),
            Group::make()
                ->refiners([
                    TextFilter::make('name'),
                    TextFilter::make('description'),
                ])
                ->booleanMode('or'),
        ],
    );

    expect($refiner->jsonSerialize())->toMatchSnapshot();
});

it('serializes and restores refinement state', function () {
    $state = new RefinementState(
        filters: [
            'status' => new FilterState(
                value: [
                    'pending',
                    false,
                    2.75,
                ],
                options: ['empty' => true],
                suggestionKey: 'today',
            ),
        ],
        sorts: [
            new SortState(name: 'created_at', direction: 'desc'),
            new SortState(name: 'name', direction: 'asc'),
        ],
    );

    expect(RefinementState::fromArray($state->toArray())->toArray())->toBe($state->toArray());
});

it('preserves every JSON scalar type in filter state', function (mixed $value) {
    expect(FilterState::fromArray(['value' => $value])->value)->toBe($value);
})->with([
    'null' => null,
    'false' => false,
    'true' => true,
    'zero' => 0,
    'integer' => 10,
    'decimal' => 2.75,
    'string' => 'pending',
    'array' => [['nested' => false, 'decimal' => 1.25]],
]);

it('hydrates filter state operators from values and instances', function () {
    expect(FilterState::fromArray(['value' => 'pending', 'operator' => 'equals'])->operator)
        ->toBe(Operator::EQUALS);
    expect(new FilterState(value: 'pending', operator: Operator::NOT_EQUALS)->operator)
        ->toBe(Operator::NOT_EQUALS);
});

it('rejects invalid refinement state values', function (callable $state) {
    expect($state)->toThrow(InvalidArgumentException::class);
})->with([
    'object filter value' => fn () => new FilterState(value: new stdClass()),
    'nested object filter value' => fn () => new FilterState(value: ['nested' => new stdClass()]),
    'non-finite filter value' => fn () => new FilterState(value: \INF),
    'invalid operator' => fn () => FilterState::fromArray(['value' => 'pending', 'operator' => 'invalid']),
    'non-string operator' => fn () => FilterState::fromArray(['value' => 'pending', 'operator' => true]),
    'non-array options' => fn () => FilterState::fromArray(['value' => 'pending', 'options' => 'invalid']),
    'non-string suggestion key' => fn () => FilterState::fromArray(['value' => 'pending', 'suggestion_key' => 1]),
    'empty sort name' => fn () => new SortState(name: '', direction: 'asc'),
    'invalid sort direction' => fn () => new SortState(name: 'created_at', direction: 'up'),
    'non-string sort state' => fn () => SortState::fromArray(['name' => 1, 'direction' => true]),
]);
