<?php

use Hybridly\Refining\Filters\CallbackFilter;
use Hybridly\Refining\Filters\Filter;
use Hybridly\Refining\Group;
use Hybridly\Refining\Refine;
use Hybridly\Refining\Sorts\Sort;
use Hybridly\Tests\Fixtures\Database\ProductFactory;

it('applies specified boolean mode on filters in a group', function () {
    ProductFactory::new()->create(['name' => 'MacBook Pro', 'description' => 'It is slick.']);
    ProductFactory::new()->create(['name' => 'AirPods', 'description' => 'They are very good.']);
    ProductFactory::new()->create(['name' => 'Earbuds', 'description' => 'They are not better than the AirPods.']);
    ProductFactory::new()->create(['name' => 'Galaxy S23', 'description' => 'Nice photos.']);

    $refine = mock_refiner(
        query: array_filter(['filters' => ['query' => 'AirPods']]),
        refiners: [
            Sort::make('created_at', alias: 'date'),
            Group::make()->refiners([
                Filter::make('name', alias: 'query'),
                Filter::make('description', alias: 'query')->loose(),
            ])->booleanMode('or'),
        ],
    );

    expect($refine->get())->toHaveCount(2);
});

it('does not leak options to other filters', function () {
    $options = [];
    $callback = function () use (&$options) {
        $options[] = Refine::getGroupOptions();
    };

    mock_refiner(
        query: array_filter(['filters' => ['query' => 'dummy']]),
        refiners: [
            CallbackFilter::make('query', $callback),
            Group::make()->refiners([
                CallbackFilter::make('query', $callback),
                CallbackFilter::make('query', $callback),
            ])->booleanMode('or'),
            CallbackFilter::make('query', $callback),
        ],
        apply: true,
    );

    expect($options)->toBe([
        null,
        ['boolean' => 'or'],
        ['boolean' => 'or'],
        null,
    ]);
});

it('makes a grouped subquery per group', function () {
    ProductFactory::new()->create(['name' => 'MacBook Pro', 'description' => 'It is slick.', 'price' => 2000]);
    ProductFactory::new()->create(['name' => 'AirPods', 'description' => 'They are very good.', 'price' => 250]);
    ProductFactory::new()->create(['name' => 'Earbuds', 'description' => 'They are not better than the AirPods.', 'price' => 200]);
    ProductFactory::new()->create(['name' => 'Galaxy S23', 'description' => 'Nice photos.', 'price' => 1000]);

    ray()->showQueries();
    $refine = mock_refiner(
        query: array_filter(['filters' => ['query' => 'AirPods']]),
        refiners: [
            Filter::make('price')->operator('>')->default(500),
            Group::make()->refiners([
                Filter::make('name', alias: 'query'),
                Filter::make('description', alias: 'query')->loose(),
            ])->booleanMode('or'),
        ],
    );

    // The default price > 500 condition
    // should exclude the `query` filter.
    expect($refine->get())->toHaveCount(0);
});
