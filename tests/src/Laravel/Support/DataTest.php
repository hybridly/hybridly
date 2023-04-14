<?php

use Hybridly\PropertiesResolver\RequestPropertiesResolver;

use function Hybridly\Testing\partial_headers;

use Hybridly\Tests\Fixtures\Data\DataObjectWithLazyProperty;
use Illuminate\Http\Request;
use Spatie\LaravelData\Lazy;

function resolve_properties(array $properties, array $headers = [], array $persisted = [], bool $partial = false, array $only = null, array $except = null): array
{
    $component = 'test-component';
    $request = Request::createFrom(request());
    $request->headers->add([
        ...$headers,
        ...($partial ? partial_headers($component, $only, $except) : []),
    ]);

    /** @var RequestPropertiesResolver */
    $resolver = resolve(RequestPropertiesResolver::class, [
        'request' => $request,
    ]);

    return $resolver->resolve($component, $properties, $persisted);
}

test('partial properties in data objects are ignored like normal partial properties', function () {
    $properties = resolve_properties([
        'data' => DataObjectWithLazyProperty::from([
            'foo' => true,
            'bar' => Lazy::partial(fn () => 'baz'),
        ]),
    ], partial: false);

    expect($properties)->toBe([
        'data' => [
            'foo' => true,
        ],
    ]);
});

test('partial properties in data objects are resolved when using partial reloads', function () {
    $properties = resolve_properties([
        'data' => DataObjectWithLazyProperty::from([
            'foo' => true,
            'bar' => Lazy::partial(fn () => 'baz'),
        ]),
    ], partial: true);

    expect($properties)->toBe([
        'data' => [
            'foo' => true,
            'bar' => 'baz',
        ],
    ]);
});

test('partial properties in data objects are ignored when excluded in partial reloads', function () {
    $properties = resolve_properties([
        'data' => DataObjectWithLazyProperty::from([
            'foo' => true,
            'bar' => Lazy::partial(fn () => 'baz'),
        ]),
    ], except: ['data'], partial: true);

    expect($properties)->toBeEmpty();
});

test('partial properties in data objects are ignored when excluded with dot notation in partial reloads', function () {
    $properties = resolve_properties([
        'data' => DataObjectWithLazyProperty::from([
            'foo' => true,
            'bar' => Lazy::partial(fn () => 'baz'),
        ]),
    ], except: ['data.bar'], partial: true);

    expect($properties)->toBe([
        'data' => [
            'foo' => true,
        ],
    ]);

    $properties = resolve_properties([
        'data' => DataObjectWithLazyProperty::from([
            'foo' => true,
            'bar' => Lazy::partial(fn () => 'baz'),
        ]),
    ], except: ['data.foo'], partial: true);

    expect($properties)->toBe([
        'data' => [
            'bar' => 'baz',
        ],
    ]);
});

test('other properties are ignored when only some partial properties are specified in data objects during partial reloads', function () {
    $properties = resolve_properties([
        'data' => DataObjectWithLazyProperty::from([
            'foo' => true,
            'bar' => Lazy::partial(fn () => 'baz'),
        ]),
    ], only: ['data.bar'], partial: true);

    expect($properties)->toBe([
        'data' => [
            'bar' => 'baz',
        ],
    ]);
});

test('properties using lazy closures are resolved', function () {
    $properties = resolve_properties([
        'data' => DataObjectWithLazyProperty::from([
            'foo' => true,
            'bar' => Lazy::closure(fn () => 'baz'),
        ]),
    ], partial: false);

    expect($properties)->toBe([
        'data' => [
            'foo' => true,
            'bar' => 'baz',
        ],
    ]);
});

test('properties using lazy closures are resolved during partial reloads', function () {
    $properties = resolve_properties([
        'data' => DataObjectWithLazyProperty::from([
            'foo' => true,
            'bar' => Lazy::closure(fn () => 'baz'),
        ]),
    ], partial: true);

    expect($properties)->toBe([
        'data' => [
            'foo' => true,
            'bar' => 'baz',
        ],
    ]);
});

test('properties using lazy closures are not evaluated when exluded during partial reloads', function () {
    $evaluated = false;
    $properties = resolve_properties([
        'data' => DataObjectWithLazyProperty::from([
            'foo' => true,
            'bar' => Lazy::closure(function () use (&$evaluated) {
                $evaluated = true;

                return 'baz';
            }),
        ]),
    ], except: ['data.bar'], partial: true);

    expect($evaluated)->toBeFalse();
    expect($properties)->toBe([
        'data' => [
            'foo' => true,
        ],
    ]);
});

test('properties using lazy partials are not evaluated when exluded during partial reloads', function () {
    $evaluated = false;
    $properties = resolve_properties([
        'data' => DataObjectWithLazyProperty::from([
            'foo' => true,
            'bar' => Lazy::partial(function () use (&$evaluated) {
                $evaluated = true;

                return 'baz';
            }),
        ]),
    ], except: ['data.bar'], partial: true);

    expect($evaluated)->toBeFalse();
    expect($properties)->toBe([
        'data' => [
            'foo' => true,
        ],
    ]);
});
