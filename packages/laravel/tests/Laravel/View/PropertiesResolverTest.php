<?php

namespace Hybridly\Tests\Laravel\View;

use Hybridly\Support\CaseConverter;
use Hybridly\Support\Pagination\ScrollMetadata;
use Hybridly\Support\Properties\Deferred;
use Hybridly\Support\Properties\Hybridable;
use Hybridly\Support\Properties\IgnoreFirstLoad;
use Hybridly\Support\Properties\Merge;
use Hybridly\Support\Properties\OnDemand;
use Hybridly\Support\Properties\Persistent;
use Hybridly\Support\Properties\Property;
use Hybridly\View\PropertiesResolver;
use Illuminate\Pagination\LengthAwarePaginator;

use function Hybridly\scroll;
use function Hybridly\Testing\partial_headers;

function get_properties_resolver(bool $partial = false, ?array $only = null, ?array $except = null, ?array $mergeIntent = null, array $query = [])
{
    return new PropertiesResolver(
        request: mock_request(headers: $partial ? partial_headers('foo', $only, $except, mergeIntent: $mergeIntent) : [], query: $query),
        caseConverter: new CaseConverter(),
    );
}

function make_hybridable(array $properties): Hybridable
{
    return new class($properties) implements Hybridable {
        public function __construct(
            private $properties,
        ) {}

        public function toHybridArray(): array
        {
            return $this->properties;
        }
    };
}

it('evaluates `Property` instances', function (array $parameters) {
    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(...$parameters)
        ->resolve('foo', [
            'property' => new class() implements Property {
                public function evaluate(): array
                {
                    return ['foo' => 'bar'];
                }
            },
        ]);

    expect($properties)
        ->toBe([
            'property' => [
                'foo' => 'bar',
            ],
        ]);
})->with([
    [['partial' => false]],
    [['partial' => true, 'only' => ['property']]],
]);

it('evaluates `Hybridable` properties', function (array $parameters) {
    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(...$parameters)
        ->resolve('foo', [
            'hybridable' => make_hybridable(['foo' => 'bar']),
            'normal' => [
                'normal_nested' => true,
                'hybridable_nested' => make_hybridable(['foo' => 'bar']),
            ],
        ]);

    expect($properties)
        ->toBe([
            'hybridable' => [
                'foo' => 'bar',
            ],
            'normal' => [
                'normal_nested' => true,
                'hybridable_nested' => [
                    'foo' => 'bar',
                ],
            ],
        ]);
})->with([
    [['partial' => false]],
    [['partial' => true, 'only' => ['hybridable', 'normal.normal_nested', 'normal.hybridable_nested']]],
]);

it('resolves "only" properties', function (array $parameters, array $expected) {
    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(...$parameters)
        ->resolve('foo', [
            'foo' => 'bar',
            'bar' => 'baz',
            'nested' => [
                'foo' => 'bar',
                'bar' => 'baz',
            ],
        ]);

    expect($properties)->toBe($expected);
})->with([
    [
        ['partial' => false, 'only' => []],
        [
            'foo' => 'bar',
            'bar' => 'baz',
            'nested' => [
                'foo' => 'bar',
                'bar' => 'baz',
            ],
        ],
    ],
    [
        ['partial' => false, 'only' => null],
        [
            'foo' => 'bar',
            'bar' => 'baz',
            'nested' => [
                'foo' => 'bar',
                'bar' => 'baz',
            ],
        ],
    ],
    [
        ['partial' => true, 'only' => ['foo', 'nested.bar']],
        [
            'foo' => 'bar',
            'nested' => [
                'bar' => 'baz',
            ],
        ],
    ],
]);

it('resolves "except" properties', function (array $parameters, array $expected) {
    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(...$parameters)
        ->resolve('foo', [
            'foo' => 'bar',
            'bar' => 'baz',
            'nested' => [
                'foo' => 'bar',
                'bar' => 'baz',
            ],
        ]);

    expect($properties)->toBe($expected);
})->with([
    [
        ['partial' => false],
        [
            'foo' => 'bar',
            'bar' => 'baz',
            'nested' => [
                'foo' => 'bar',
                'bar' => 'baz',
            ],
        ],
    ],
    [
        ['partial' => true, 'only' => null, 'except' => ['foo', 'nested.bar']],
        [
            'bar' => 'baz',
            'nested' => [
                'foo' => 'bar',
            ],
        ],
    ],
]);

it('resolves combinations of "except" and "only" properties', function (array $parameters, array $expected) {
    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(...$parameters)
        ->resolve('foo', [
            'foo' => 'bar',
            'bar' => 'baz',
            'nested' => [
                'foo' => 'bar',
                'bar' => 'baz',
            ],
        ]);

    expect($properties)->toBe($expected);
})->with([
    [
        ['partial' => false],
        [
            'foo' => 'bar',
            'bar' => 'baz',
            'nested' => [
                'foo' => 'bar',
                'bar' => 'baz',
            ],
        ],
    ],
    [
        ['partial' => true, 'only' => ['foo', 'nested.bar'], 'except' => ['nested.bar']],
        [
            'foo' => 'bar',
            'nested' => [],
        ],
    ],
]);

it('resolves `Persistent` properties', function (array $parameters) {
    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(...$parameters)
        ->resolve('foo', [
            'persistent' => new Persistent(fn () => ['foo' => 'bar']),
            'normal' => [
                'normal_nested' => true,
                'persistent_nested' => new Persistent(fn () => ['foo' => 'bar']),
            ],
        ]);

    expect($properties)
        ->toBe([
            'persistent' => [
                'foo' => 'bar',
            ],
            'normal' => [
                'normal_nested' => true,
                'persistent_nested' => [
                    'foo' => 'bar',
                ],
            ],
        ]);
})->with([
    [['partial' => false]],
    [['partial' => true, 'only' => ['normal.normal_nested']]], // persistent properties should *always* be included
    [['partial' => true, 'only' => ['persistent', 'normal.normal_nested', 'normal.persistent_nested']]],
]);

it('resolves persistent properties defined outside of properties', function (array $parameters) {
    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(...$parameters)
        ->resolve(
            'foo',
            [
                'normal' => 'foo',
                'persistent' => 'bar',
            ],
            [
                'persistent',
            ],
        );

    expect($properties)
        ->toBe([
            'normal' => 'foo',
            'persistent' => 'bar',
        ]);
})->with([
    [['partial' => false]],
    [['partial' => true, 'only' => ['normal']]],
]);

it('excludes `IgnoreFirstLoad` instances from the first load', function (array $parameters, array $expected) {
    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(...$parameters)
        ->resolve('foo', [
            'ignore_first_load' => new class() implements Property, IgnoreFirstLoad {
                public function evaluate(): array
                {
                    return ['foo' => 'bar'];
                }
            },
        ]);

    expect($properties)->toBe($expected);
})->with([
    [['partial' => false], []],
    [['partial' => true, 'only' => null], ['ignore_first_load' => ['foo' => 'bar']]],
    [['partial' => true, 'only' => ['ignore_first_load']], ['ignore_first_load' => ['foo' => 'bar']]],
    [['partial' => true, 'except' => ['ignore_first_load']], []],
]);

it('resolves `Deferred` properties', function (array $parameters, array $expectedDeferred, array $expectedProperties) {
    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(...$parameters)
        ->resolve('foo', [
            'normal' => true,
            'deferred' => new Deferred(fn () => 'foo'),
            'nested' => [
                'normal' => true,
                'deferred' => new Deferred(fn () => 'bar'),
            ],
        ]);

    expect($properties)->toBe($expectedProperties);
    expect($deferred)->toBe($expectedDeferred);
})->with([
    [['partial' => false], ['default' => ['deferred', 'nested.deferred']], ['normal' => true, 'nested' => ['normal' => true]]],
    [['partial' => true], [], ['normal' => true, 'deferred' => 'foo', 'nested' => ['normal' => true, 'deferred' => 'bar']]],
    [['partial' => true, 'only' => ['deferred', 'nested.deferred']], [], ['deferred' => 'foo', 'nested' => ['deferred' => 'bar']]],
]);

it('resolves grouped `Deferred` properties', function (array $parameters, array $expectedDeferred) {
    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(...$parameters)
        ->resolve('foo', [
            'deferred' => new Deferred(fn () => 'foo'),
            'slow_deferred' => new Deferred(fn () => 'foo', group: 'slower'),
        ]);

    expect($deferred)->toBe($expectedDeferred);
})->with([
    [['partial' => false], ['default' => ['deferred'], 'slower' => ['slow_deferred']]],
    [['partial' => true], []],
    [['partial' => true, 'only' => ['deferred', 'slow_deferred']], []],
]);

it('resolves `Mergeable` properties', function (array $parameters, array $expectedMergeable) {
    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(...$parameters)
        ->resolve('foo', [
            'mergeable' => new Merge(['foo', 'bar']),
            'nested' => [
                'normal' => true,
                'mergeable' => new Merge(['foo', 'bar'], prepend: true, uniqueBy: 'id', mergePaths: ['data']),
            ],
        ]);

    expect($mergeable)->toBe($expectedMergeable);
})->with([
    [['partial' => false], [['mergeable', false, null, null], ['nested.mergeable', true, 'id', ['data']]]],
    [['partial' => true], [['mergeable', false, null, null], ['nested.mergeable', true, 'id', ['data']]]],
]);

it('resolves merge intent overrides and paginator metadata', function () {
    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(
        partial: true,
        mergeIntent: ['feed' => 'prepend'],
        query: ['feedPage' => 3],
    )->resolve('foo', [
        'feed' => scroll(fn () => new LengthAwarePaginator(
            items: [['id' => 5], ['id' => 6]],
            total: 12,
            perPage: 2,
            currentPage: 3,
            options: [
                'pageName' => 'feedPage',
                'path' => '/feed',
            ],
        )),
    ]);

    expect($mergeable)
        ->toBe([
            ['feed', true, null, ['data']],
        ]);

    expect($paginators['feed'])
        ->toBeInstanceOf(ScrollMetadata::class);

    expect($paginators['feed']->toArray())
        ->toBe([
            'type' => 'length-aware',
            'queryKey' => 'feedPage',
            'current' => 3,
            'previous' => 2,
            'next' => 4,
        ]);

    expect($properties['feed'])
        ->toMatchArray([
            'data' => [
                ['id' => 5],
                ['id' => 6],
            ],
        ]);
});

it('resolves custom scroll metadata providers', function () {
    $metadata = new class() implements ScrollMetadata {
        public function type(): string
        {
            return 'cursor';
        }

        public function queryKey(): string
        {
            return 'cursor';
        }

        public function current(): ?int
        {
            return 'current-token';
        }

        public function previous(): ?int
        {
            return 'previous-token';
        }

        public function next(): ?int
        {
            return 'next-token';
        }

        public function toArray(): array
        {
            return [
                'type' => $this->type(),
                'queryKey' => $this->queryKey(),
                'current' => $this->current(),
                'previous' => $this->previous(),
                'next' => $this->next(),
            ];
        }
    };

    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(partial: true)
        ->resolve('foo', [
            'feed' => scroll(
                value: fn () => ['items' => [['id' => 9]]],
                wrapper: 'items',
                metadata: fn () => $metadata,
            ),
        ]);

    expect($mergeable)
        ->toBe([
            ['feed', false, null, ['items']],
        ]);

    expect($paginators['feed'])
        ->toBe($metadata);

    expect($properties['feed'])
        ->toBe([
            'items' => [
                ['id' => 9],
            ],
        ]);
});

it('resolves `Partial` properties', function (string $class, array $parameters, array $expectedProperties) {
    [$properties, $deferred, $mergeable, $paginators] = get_properties_resolver(...$parameters)
        ->resolve('foo', [
            'normal' => 'foo',
            'partial' => new $class(fn () => 'bar'),
        ]);

    expect($properties)->toBe($expectedProperties);
})->with([
    [OnDemand::class, ['partial' => false], ['normal' => 'foo']],
    [OnDemand::class, ['partial' => true, 'only' => ['partial']], ['partial' => 'bar']],
    [OnDemand::class, ['partial' => true, 'only' => ['normal', 'partial']], ['normal' => 'foo', 'partial' => 'bar']],
]);
