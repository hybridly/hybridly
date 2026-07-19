<?php

use Carbon\CarbonImmutable;
use Hybridly\Tests\Fixtures\Data\NestedTransformedDateData;
use Hybridly\Tests\Fixtures\Data\TransformedDateData;
use Spatie\LaravelData\DataCollection;

use function Hybridly\Support\except_dot;
use function Hybridly\Support\filter_recursive;
use function Hybridly\Support\only_dot;
use function Hybridly\Support\resolve_arrayable_properties;

it('applies property transformers to Laravel Data properties', function () {
    expect(resolve_arrayable_properties([
        'data' => new TransformedDateData(
            date: CarbonImmutable::parse('2026-07-18'),
        ),
    ]))->toBe([
        'data' => [
            'date' => '2026-07-18',
        ],
    ]);
});

it('applies property transformers to nested Laravel Data properties', function () {
    expect(resolve_arrayable_properties([
        'data' => new NestedTransformedDateData(
            child: new TransformedDateData(
                date: CarbonImmutable::parse('2026-07-18'),
            ),
        ),
    ]))->toBe([
        'data' => [
            'child' => [
                'date' => '2026-07-18',
            ],
        ],
    ]);
});

it('applies property transformers to Laravel Data collections', function () {
    expect(resolve_arrayable_properties([
        'data' => new DataCollection(TransformedDateData::class, [
            new TransformedDateData(
                date: CarbonImmutable::parse('2026-07-18'),
            ),
        ]),
    ]))->toBe([
        'data' => [[
            'date' => '2026-07-18',
        ]],
    ]);
});

it('filters arrays recursively', function ($array, $filter, $expected) {
    expect(filter_recursive($array, $filter))->toBe($expected);
})->with([
    [
        [
            'foo' => true,
            'bar' => collect([
                'foo' => true,
                'bar' => false,
            ]),
        ],
        null,
        [
            'foo' => true,
            'bar' => ['foo' => true],
        ],
    ],
    [
        [
            'foo' => true,
            'bar' => true,
        ],
        null,
        [
            'foo' => true,
            'bar' => true,
        ],
    ],
    [
        [
            'foo' => true,
            'bar' => ['baz' => true, 'owo' => false],
        ],
        null,
        [
            'foo' => true,
            'bar' => ['baz' => true],
        ],
    ],
]);

it('gets only subsets of an array using dot notation', function ($array, $only, $expected) {
    expect(only_dot($array, $only))->toBe($expected);
})->with([
    [
        [
            'security' => ['user' => ['name' => 'foo']],
            'foo' => 'bar',
        ],
        ['security.user'],
        ['security' => ['user' => ['name' => 'foo']]],
    ],
    [
        ['key1' => 'value1'],
        ['key1'],
        ['key1' => 'value1'],
    ],
    [
        [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => ['nested-key1' => 'value3', 'nested-key2' => 'value4'],
        ],
        ['key1', 'key3.nested-key4'],
        ['key1' => 'value1'],
    ],
    [
        [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => ['nested-key1' => 'value3', 'nested-key2' => 'value4'],
        ],
        ['key1', 'key3'],
        ['key1' => 'value1', 'key3' => ['nested-key1' => 'value3', 'nested-key2' => 'value4']],
    ],
    [
        [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => ['nested-key1' => 'value3', 'nested-key2' => 'value4'],
        ],
        ['key1', 'key3.nested-key1'],
        ['key1' => 'value1', 'key3' => ['nested-key1' => 'value3']],
    ],
]);

it('gets all of an array except the given subsets using dot notation', function ($array, $except, $expected) {
    expect(except_dot($array, $except))->toBe($expected);
})->with([
    [
        ['key1' => 'value1'],
        ['key1'],
        [],
    ],
    [
        [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => ['nested-key1' => 'value3', 'nested-key2' => 'value4'],
        ],
        ['key1', 'key3.nested-key4'],
        [
            'key2' => 'value2',
            'key3' => ['nested-key1' => 'value3', 'nested-key2' => 'value4'],
        ],
    ],
    [
        [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => ['nested-key1' => 'value3', 'nested-key2' => 'value4'],
            'key4' => ['nested-key3' => 'value5', 'nested-key4' => 'value6'],
        ],
        ['key1', 'key3', 'key4.nested-key3'],
        ['key2' => 'value2', 'key4' => ['nested-key4' => 'value6']],
    ],
    [
        [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => ['nested-key1' => 'value3', 'nested-key2' => 'value4'],
        ],
        ['key1', 'key3.nested-key1'],
        ['key2' => 'value2', 'key3' => ['nested-key2' => 'value4']],
    ],
]);
