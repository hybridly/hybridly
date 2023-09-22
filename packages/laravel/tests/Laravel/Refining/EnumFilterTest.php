<?php

use Hybridly\Refining\Filters\EnumFilter;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Hybridly\Tests\Fixtures\Vendor;

it('filters out using the enum', function () {
    ProductFactory::new()->create([
        'vendor' => Vendor::Apple,
    ]);

    ProductFactory::new()->create([
        'vendor' => Vendor::Microsoft,
    ]);

    $filters = mock_refiner(
        query: ['filters' => ['vendor' => Vendor::Microsoft->value]],
        refiners: [
            EnumFilter::make('vendor', Vendor::class),
        ],
        apply: true,
    );

    expect($filters)
        ->first()->vendor->toBe(Vendor::Microsoft)
        ->count()->toBe(1);
});
