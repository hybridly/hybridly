<?php

use Hybridly\Deferred;
use Hybridly\HybridResponseFactory;
use Hybridly\OnDemand;
use Hybridly\PropertiesResolver;
use Hybridly\Support\CaseConverter;
use Illuminate\Contracts\Support\Arrayable;

use function Hybridly\merge;
use function Hybridly\Testing\partial_headers;

it('finds deferred properties', function () {
    /** @var PropertiesResolver */
    $resolver = resolve(PropertiesResolver::class, [
        'request' => mock_request(headers: partial_headers(
            component: 'users.edit',
            only: ['user.full_name', 'user.email'],
        )),
        'caseConverter' => resolve(CaseConverter::class),
    ]);

    [$properties, $deferred] = $resolver->resolve('foo.bar', [
        'normal' => 'yes',
        'partial' => new OnDemand(fn () => 'partial'),
        'deferred' => new Deferred(fn () => 'deferred'),
        'nested' => [
            'foo' => 'bar',
            'deferred' => new Deferred(fn () => 'nested deferred'),
        ],
    ]);

    expect($properties)->toBe(['normal' => 'yes', 'nested' => ['foo' => 'bar']]);
    expect($deferred)->toBe(['default' => ['deferred', 'nested.deferred']]);
});

it('resolves functions', function () {
    $payload = resolve(HybridResponseFactory::class)
        ->withView('users.edit', [
            'user' => fn () => 'Makise Kurisu',
            'errors' => [],
        ])
        ->toResponse(mock_request())
        ->getData();

    expect($payload->view->component)->toBe('users.edit');
    expect($payload->view->properties->user)->toBe('Makise Kurisu');
});

it('resolves callables', function () {
    $callable = new class() {
        public function __invoke()
        {
            return ['name' => 'Makise Kurisu'];
        }
    };

    $payload = resolve(HybridResponseFactory::class)
        ->withView('users.edit', ['user' => $callable, 'type' => 'app'])
        ->toResponse(mock_request())
        ->getData();

    expect($payload->view->component)->toBe('users.edit');
    expect($payload->view->properties->type)->toBe('app');
    expect($payload->view->properties->user->name)->toBe('Makise Kurisu');
});

it('resolves arrayable properties', function () {
    $callable = new class() implements Arrayable {
        public function toArray()
        {
            return ['name' => 'Makise Kurisu'];
        }
    };

    $payload = resolve(HybridResponseFactory::class)
        ->withView('users.edit', ['user' => $callable])
        ->toResponse(mock_request())
        ->getData();

    expect($payload->view->component)->toBe('users.edit');
    expect($payload->view->properties->user->name)->toBe('Makise Kurisu');
});

it('does not evaluate lazy properties when they are excluded', function () {
    $evaluated = false;
    $payload = resolve(HybridResponseFactory::class)
        ->withView('users.edit', [
            'full_name' => function () use (&$evaluated) {
                $evaluated = true;

                return 'Jon Doe';
            },
            'email' => 'jon@example.org',
        ])
        ->toResponse(mock_request(headers: partial_headers(
            component: 'users.edit',
            only: ['email'],
        )))
        ->getData();

    expect($evaluated)->toBeFalse();
    expect($payload->view->component)->toBe('users.edit');
    expect($payload->view->properties)->full_name->toBeNull();
    expect($payload->view->properties)->email->toBe('jon@example.org');
});

it('does not resolve partials by default', function () {
    $payload = resolve(HybridResponseFactory::class)
        ->withView('users.edit', [
            'full_name' => new OnDemand(fn () => 'Jon Doe'),
            'email' => 'jon@example.org',
        ])
        ->toResponse(mock_request())
        ->getData();

    expect($payload->view->component)->toBe('users.edit');
    expect($payload->view->properties)->user->toBeNull();
    expect($payload->view->properties)->email->toBe('jon@example.org');
});

it('does not resolve nested partials by default', function () {
    $payload = resolve(HybridResponseFactory::class)
        ->withView('users.edit', [
            'user' => [
                'full_name' => new OnDemand(fn () => 'Jon Doe'),
                'email' => 'jon@doe.example',
            ],
        ])
        ->toResponse(mock_request())
        ->getData();

    expect($payload->view->component)->toBe('users.edit');
    expect($payload->view->properties->user)->full_name->toBeNull();
    expect($payload->view->properties->user)->email->toBe('jon@doe.example');
});

it('resolves partials', function () {
    $payload = resolve(HybridResponseFactory::class)
        ->withView('users.edit', [
            'full_name' => new OnDemand(fn () => 'Jon Doe'),
            'email' => 'jon@example.org',
        ])
        ->toResponse(mock_request(headers: partial_headers(
            component: 'users.edit',
            only: ['full_name', 'email'],
        )))
        ->getData();

    expect($payload->view->component)->toBe('users.edit');
    expect($payload->view->properties)->full_name->toBe('Jon Doe');
    expect($payload->view->properties)->email->toBe('jon@example.org');
});

it('resolves deferred', function () {
    $payload = resolve(HybridResponseFactory::class)
        ->withView('users.edit', [
            'full_name' => new Deferred(fn () => 'Jon Doe'),
            'email' => 'jon@example.org',
        ])
        ->toResponse(mock_request(headers: partial_headers(
            component: 'users.edit',
            only: ['full_name', 'email'],
        )))
        ->getData();

    expect($payload->view->component)->toBe('users.edit');
    expect($payload->view->properties)->full_name->toBe('Jon Doe');
    expect($payload->view->properties)->email->toBe('jon@example.org');
});

it('resolves nested partials', function () {
    $payload = resolve(HybridResponseFactory::class)
        ->withView('users.edit', [
            'user' => [
                'full_name' => new OnDemand(fn () => 'Jon Doe'),
                'email' => 'jon@example.org',
            ],
        ])
        ->toResponse(mock_request(headers: partial_headers(
            component: 'users.edit',
            only: ['user.full_name', 'user.email'],
        )))
        ->getData();

    expect($payload->view->component)->toBe('users.edit');
    expect($payload->view->properties->user)->full_name->toBe('Jon Doe');
    expect($payload->view->properties->user)->email->toBe('jon@example.org');
});

it('resolves nested deferred', function () {
    $payload = resolve(HybridResponseFactory::class)
        ->withView('users.edit', [
            'user' => [
                'full_name' => new Deferred(fn () => 'Jon Doe'),
                'email' => 'jon@example.org',
            ],
        ])
        ->toResponse(mock_request(headers: partial_headers(
            component: 'users.edit',
            only: ['user.full_name', 'user.email'],
        )))
        ->getData();

    expect($payload->view->component)->toBe('users.edit');
    expect($payload->view->properties->user)->full_name->toBe('Jon Doe');
    expect($payload->view->properties->user)->email->toBe('jon@example.org');
});

it('does not include deferred mergeable properties in mergeable config on initial loads', function () {
    $payload = resolve(HybridResponseFactory::class)
        ->withView('users.edit', [
            'feed' => new Deferred(fn () => [
                ['id' => 1, 'label' => 'First'],
            ]),
        ])
        ->toResponse(mock_request())
        ->getData();

    expect($payload->view->deferred)
        ->toMatchArray([
            'default' => ['feed'],
        ]);

    expect($payload->view->mergeable)->toBe([]);
});

it('includes deferred mergeable properties in mergeable config on partial loads', function () {
    $payload = resolve(HybridResponseFactory::class)
        ->withView('users.edit', [
            'feed' => new Deferred(
                fn () => [
                    ['id' => 1, 'label' => 'First'],
                ],
            )->merge(uniqueBy: 'id', prepend: true),
            'nested' => [
                'items' => new Deferred(fn () => [
                    ['id' => 2, 'label' => 'Second'],
                ]),
            ],
        ])
        ->toResponse(mock_request(headers: partial_headers(
            component: 'users.edit',
            only: ['feed', 'nested.items'],
        )))
        ->getData();

    expect($payload->view->mergeable)
        ->toContain(['feed', true, 'id', []])
        ->not->toContain(['nested.items', false, null, []]);
});

it('includes mergeable properties configuration in the payload', function () {
    $payload = resolve(HybridResponseFactory::class)
        ->withView('users.edit', [
            'users' => merge([['id' => 1]], uniqueBy: 'id'),
            'priority_users' => merge([['id' => 2]], prepend: true, uniqueBy: 'id'),
            'messages' => merge(['hello']),
            'nested' => [
                'items' => merge([['meta' => ['id' => 3]]], uniqueBy: 'meta.id'),
            ],
        ])
        ->toResponse(mock_request())
        ->getData();

    expect($payload->view->mergeable)
        ->toHaveCount(4)
        ->toContain(['users', false, 'id', []])
        ->toContain(['priority_users', true, 'id', []])
        ->toContain(['messages', false, null, []])
        ->toContain(['nested.items', false, 'meta.id', []]);
});

it('includes mergeable properties configuration in non-hybrid payload responses', function () {
    $response = resolve(HybridResponseFactory::class)
        ->withView('users.edit', [
            'users' => merge([['id' => 1]], prepend: true, uniqueBy: 'id'),
        ])
        ->toResponse(mock_request(hybrid: false));

    $payload = $response->getOriginalContent()->getData()['payload'];

    expect($payload['view']['mergeable'])
        ->toHaveCount(1)
        ->toContain(['users', true, 'id', []]);
});
