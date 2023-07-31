<?php

use Hybridly\Refining\Filters\SimilarityFilter;
use Hybridly\Refining\Sorts\FieldSort;
use Hybridly\Refining\Sorts\Sort;
use Hybridly\Tests\Fixtures\Database\ProductFactory;

beforeEach(function () {
    ProductFactory::new()->create(['name' => 'AirPods']);
    ProductFactory::new()->create(['name' => 'AirPods Pro']);
    ProductFactory::new()->create(['name' => 'Macbook Pro M1']);
});

test('refiners should execute only once', function () {
    $refine = mock_refiner(
        query: array_filter(['sort' => '-date']),
        refiners: [
            new Sort(
                sort: $fieldSort = Mockery::spy(FieldSort::class),
                property: 'published_at',
                alias: 'date',
            ),
        ],
    );

    $fieldSort->shouldNotHaveReceived('__invoke');
    $refine->applyRefiners();
    $fieldSort->shouldHaveReceived('__invoke')->once();
    $refine->applyRefiners();
    $fieldSort->shouldHaveReceived('__invoke')->once();
});

test('the refine instance can be serialized', function () {
    $refine = mock_refiner(
        refiners: [
            FieldSort::make('created_at', alias: 'date'),
            SimilarityFilter::make('name'),
        ],
    )->scope('products');

    expect(json_decode(json_encode($refine), associative: true))->toBe([
        'sorts' => [
            [
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
            ],
        ],
        'filters' => [
            [
                'name' => 'name',
                'hidden' => false,
                'label' => 'Name',
                'type' => 'similar:loose',
                'metadata' => [],
                'is_active' => false,
                'value' => null,
                'default' => null,
            ],
        ],
        'scope' => 'products',
        'keys' => [
            'sorts' => 'products-sort',
            'filters' => 'products-filters',
        ],
    ]);
});
