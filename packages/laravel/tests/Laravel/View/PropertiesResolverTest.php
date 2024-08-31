<?php

namespace Hybridly\Tests\Laravel\View;

use Hybridly\Support\CaseConverter;
use Hybridly\Support\Properties\Deferred;
use Hybridly\Support\Properties\Hybridable;
use Hybridly\Support\Properties\IgnoreFirstLoad;
use Hybridly\Support\Properties\Lazy;
use Hybridly\Support\Properties\Merge;
use Hybridly\Support\Properties\Optional;
use Hybridly\Support\Properties\Partial;
use Hybridly\Support\Properties\Persistent;
use Hybridly\Support\Properties\Property;
use Hybridly\View\PropertiesResolver;

use function Hybridly\Testing\partial_headers;

function get_properties_resolver(bool $partial = false, ?array $only = [], ?array $except = [])
{
    return new PropertiesResolver(
        request: mock_request(headers: $partial ? partial_headers('foo', $only, $except) : []),
        caseConverter: new CaseConverter(),
    );
}

function make_hybridable(array $properties): Hybridable
{
    return new class ($properties) implements Hybridable
    {
        public function __construct(
            private $properties,
        ) {
        }

        public function toHybridArray(): array
        {
            return $this->properties;
        }
    };
}

it('evaluates `Property` instances', function (array $parameters) {
    [$properties, $deferred, $mergeable] = get_properties_resolver(...$parameters)->resolve('foo', [
        'property' => new class () implements Property
        {
            public function __invoke(): array
            {
                return ['foo' => 'bar'];
            }
        },
    ]);

    expect($properties)->toBe([
        'property' => [
            'foo' => 'bar',
        ],
    ]);
})->with([
    [['partial' => false]],
    [['partial' => true, 'only' => ['property']]],
]);

it('evaluates `Hybridable` properties', function (array $parameters) {
    [$properties, $deferred, $mergeable] = get_properties_resolver(...$parameters)->resolve('foo', [
        'hybridable' => make_hybridable(['foo' => 'bar']),
        'normal' => [
            'normal_nested' => true,
            'hybridable_nested' => make_hybridable(['foo' => 'bar']),
        ],
    ]);

    expect($properties)->toBe([
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
    [$properties, $deferred, $mergeable] = get_properties_resolver(...$parameters)->resolve('foo', [
        'foo' => 'bar',
        'bar' => 'baz',
        'nested' => [
            'foo' => 'bar',
            'bar' => 'baz',
        ],
    ]);

    expect($properties)->toBe($expected);
})->with([
    [['partial' => false, 'only' => []], [
        'foo' => 'bar',
        'bar' => 'baz',
        'nested' => [
            'foo' => 'bar',
            'bar' => 'baz',
        ],
    ]],
    [['partial' => false, 'only' => null], [
        'foo' => 'bar',
        'bar' => 'baz',
        'nested' => [
            'foo' => 'bar',
            'bar' => 'baz',
        ],
    ]],
    [['partial' => true, 'only' => ['foo', 'nested.bar']], [
        'foo' => 'bar',
        'nested' => [
            'bar' => 'baz',
        ],
    ]],
]);

it('resolves "except" properties', function (array $parameters, array $expected) {
    [$properties, $deferred, $mergeable] = get_properties_resolver(...$parameters)->resolve('foo', [
        'foo' => 'bar',
        'bar' => 'baz',
        'nested' => [
            'foo' => 'bar',
            'bar' => 'baz',
        ],
    ]);

    expect($properties)->toBe($expected);
})->with([
    [['partial' => false], [
        'foo' => 'bar',
        'bar' => 'baz',
        'nested' => [
            'foo' => 'bar',
            'bar' => 'baz',
        ],
    ]],
    [['partial' => true, 'only' => null, 'except' => ['foo', 'nested.bar']], [
        'bar' => 'baz',
        'nested' => [
            'foo' => 'bar',
        ],
    ]],
]);

it('resolves combinations of "except" and "only" properties', function (array $parameters, array $expected) {
    [$properties, $deferred, $mergeable] = get_properties_resolver(...$parameters)->resolve('foo', [
        'foo' => 'bar',
        'bar' => 'baz',
        'nested' => [
            'foo' => 'bar',
            'bar' => 'baz',
        ],
    ]);

    expect($properties)->toBe($expected);
})->with([
    [['partial' => false], [
        'foo' => 'bar',
        'bar' => 'baz',
        'nested' => [
            'foo' => 'bar',
            'bar' => 'baz',
        ],
    ]],
    [['partial' => true, 'only' => ['foo', 'nested.bar'], 'except' => ['nested.bar']], [
        'foo' => 'bar',
        'nested' => [],
    ]],
]);

it('resolves `Persistent` properties', function (array $parameters) {
    [$properties, $deferred, $mergeable] = get_properties_resolver(...$parameters)->resolve('foo', [
        'persistent' => new Persistent(fn () => ['foo' => 'bar']),
        'normal' => [
            'normal_nested' => true,
            'persistent_nested' => new Persistent(fn () => ['foo' => 'bar']),
        ],
    ]);

    expect($properties)->toBe([
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
    [$properties, $deferred, $mergeable] = get_properties_resolver(...$parameters)->resolve('foo', [
        'normal' => 'foo',
        'persistent' => 'bar',
    ], [
        'persistent',
    ]);

    expect($properties)->toBe([
        'normal' => 'foo',
        'persistent' => 'bar',
    ]);
})->with([
    [['partial' => false]],
    [['partial' => true, 'only' => ['normal']]],
]);

it('excludes `IgnoreFirstLoad` instances from the first load', function (array $parameters, array $expected) {
    [$properties, $deferred, $mergeable] = get_properties_resolver(...$parameters)->resolve('foo', [
        'ignore_first_load' => new class () implements Property, IgnoreFirstLoad
        {
            public function __invoke(): array
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
    [$properties, $deferred, $mergeable] = get_properties_resolver(...$parameters)->resolve('foo', [
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
    [['partial' => false], ['deferred', 'nested.deferred'], ['normal' => true, 'nested' => ['normal' => true]]],
    [['partial' => true], [], []],
    [['partial' => true, 'only' => ['deferred', 'nested.deferred']], [], ['deferred' => 'foo', 'nested' => ['deferred' => 'bar']]],
]);

it('resolves `Mergeable` properties', function (array $parameters, array $expectedMergeable) {
    [$properties, $deferred, $mergeable] = get_properties_resolver(...$parameters)->resolve('foo', [
        'mergeable' => new Merge(['foo', 'bar'], unique: false),
        'nested' => [
            'normal' => true,
            'mergeable' => new Merge(['foo', 'bar'], unique: true),
        ],
    ]);

    expect($mergeable)->toBe($expectedMergeable);
})->with([
    [['partial' => false], [['mergeable', false], ['nested.mergeable', true]]],
    [['partial' => true], [['mergeable', false], ['nested.mergeable', true]]],
]);

it('resolves `Partial` properties', function (string $class, array $parameters, array $expectedProperties) {
    [$properties, $deferred, $mergeable] = get_properties_resolver(...$parameters)->resolve('foo', [
        'normal' => 'foo',
        'partial' => new $class(fn () => 'bar'),
    ]);

    expect($properties)->toBe($expectedProperties);
})->with([
    [Partial::class, ['partial' => false], ['normal' => 'foo']],
    [Partial::class, ['partial' => true, 'only' => ['partial']], ['partial' => 'bar']],
    [Partial::class, ['partial' => true, 'only' => ['normal', 'partial']], ['normal' => 'foo', 'partial' => 'bar']],
    [Optional::class, ['partial' => false], ['normal' => 'foo']],
    [Optional::class, ['partial' => true, 'only' => ['partial']], ['partial' => 'bar']],
    [Optional::class, ['partial' => true, 'only' => ['normal', 'partial']], ['normal' => 'foo', 'partial' => 'bar']],
    [Lazy::class, ['partial' => false], ['normal' => 'foo']],
    [Lazy::class, ['partial' => true, 'only' => ['partial']], ['partial' => 'bar']],
    [Lazy::class, ['partial' => true, 'only' => ['normal', 'partial']], ['normal' => 'foo', 'partial' => 'bar']],
]);
