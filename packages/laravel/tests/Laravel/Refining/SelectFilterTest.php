<?php

use Hybridly\Refining\Filters\SelectFilter;
use Hybridly\Tests\Fixtures\Vendor;

test('the `where` statement uses the value from the given key', function (?string $phone, ?string $os) {
    $filters = mock_refiner(
        query: ['filters' => ['phone' => $phone]],
        refiners: [
            SelectFilter::make('os', alias: 'phone', options: [
                'iphone' => 'ios',
                'ipad' => 'ipados',
                'samsung' => 'android',
            ]),
        ],
    );

    expect($filters->toRawSql())->toBe(match (true) {
        is_null($os) => 'select * from "products" where "products"."deleted_at" is null',
        default => "select * from \"products\" where \"products\".\"os\" = '{$os}' and \"products\".\"deleted_at\" is null"
    });
})->with([
    ['iphone', 'ios'],
    ['samsung', 'android'],
    [null, null],
]);

test('the `where` statement uses the key when the options is a list', function (?string $os) {
    $filters = mock_refiner(
        query: ['filters' => ['os' => $os]],
        refiners: [
            SelectFilter::make('os', options: [
                'ios',
                'ipados',
                'android',
            ]),
        ],
    );

    expect($filters->toRawSql())->toBe(match (true) {
        is_null($os) => 'select * from "products" where "products"."deleted_at" is null',
        default => "select * from \"products\" where \"products\".\"os\" = '{$os}' and \"products\".\"deleted_at\" is null"
    });
})->with([
    ['ios'],
    ['android'],
    [null],
]);

test('the operator can be specified', function () {
    $filters = mock_refiner(
        query: ['filters' => ['!os' => 'ios']],
        refiners: [
            SelectFilter::make('os', alias: '!os')
                ->operator('!=')
                ->options([
                    'ios',
                    'ipados',
                    'android',
                ]),
        ],
    );

    expect($filters->toRawSql())->toBe('select * from "products" where "products"."os" != \'ios\' and "products"."deleted_at" is null');
});

it('supports checking against multiple values', function () {
    $filters = mock_refiner(
        query: ['filters' => ['phone' => 'iphone,ipad']],
        refiners: [
            SelectFilter::make('os', alias: 'phone')
                ->multiple()
                ->options([
                    'iphone' => 'ios',
                    'ipad' => 'ipados',
                    'samsung' => 'android',
                ]),
        ],
    );

    expect($filters->toRawSql())->toBe('select * from "products" where "products"."os" in (\'ios\', \'ipados\') and "products"."deleted_at" is null');
});

it('supports checking against multiple values when options are a list', function () {
    $filters = mock_refiner(
        query: ['filters' => ['os' => 'ios,ipados']],
        refiners: [
            SelectFilter::make('os')
                ->multiple()
                ->options([
                    'ios',
                    'ipados',
                    'android',
                ]),
        ],
    );

    expect($filters->toRawSql())->toBe('select * from "products" where "products"."os" in (\'ios\', \'ipados\') and "products"."deleted_at" is null');
});

it('supports using an enum as options', function () {
    $filters = mock_refiner(
        query: ['filters' => ['vendor' => 'microsoft']],
        refiners: [
            SelectFilter::make('vendor', options: Vendor::class),
        ],
    );

    expect($filters->toRawSql())->toBe('select * from "products" where "products"."vendor" = \'microsoft\' and "products"."deleted_at" is null');
});

it('supports checking against multiple values when options is an enum', function () {
    $filters = mock_refiner(
        query: ['filters' => ['vendor' => 'microsoft,apple']],
        refiners: [
            SelectFilter::make('vendor')
                ->multiple()
                ->options(Vendor::class),
        ],
    );

    expect($filters->toRawSql())->toBe('select * from "products" where "products"."vendor" in (\'microsoft\', \'apple\') and "products"."deleted_at" is null');
});
