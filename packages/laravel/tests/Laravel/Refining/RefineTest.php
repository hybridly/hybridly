<?php

use Hybridly\Refining\Filters\TextFilter;
use Hybridly\Refining\Group;
use Hybridly\Refining\Sorts\Sort;
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
                    'options' => [],
                ],
            ],
            'scope' => 'products',
            'keys' => [
                'sorts' => 'products-sort',
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
