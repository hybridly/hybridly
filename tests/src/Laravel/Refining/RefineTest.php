<?php

use Hybridly\Refining\Sorts\FieldSort;
use Hybridly\Refining\Sorts\Sort;

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
