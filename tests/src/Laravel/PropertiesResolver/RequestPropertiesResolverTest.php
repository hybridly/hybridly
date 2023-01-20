<?php

use Hybridly\Hybridly;
use Hybridly\View\Factory;
use Illuminate\Contracts\Support\Arrayable;

it('resolves functions', function () {
    $payload = resolve(Factory::class)
        ->view('users.edit', [
            'user' => fn () => 'Makise Kurisu',
            'errors' => [
            ],
        ])
        ->toResponse(mockRequest())
        ->getData();

    expect($payload->view->name)->toBe('users.edit');
    expect($payload->view->properties->user)->toBe('Makise Kurisu');
});

it('resolves callables', function () {
    $callable = new class () {
        public function __invoke()
        {
            return ['name' => 'Makise Kurisu'];
        }
    };

    $payload = resolve(Factory::class)
        ->view('users.edit', ['user' => $callable, 'type' => 'app'])
        ->toResponse(mockRequest())
        ->getData();

    expect($payload->view->name)->toBe('users.edit');
    expect($payload->view->properties->type)->toBe('app');
    expect($payload->view->properties->user->name)->toBe('Makise Kurisu');
});

it('resolves arrayable properties', function () {
    $callable = new class () implements Arrayable {
        public function toArray()
        {
            return ['name' => 'Makise Kurisu'];
        }
    };

    $payload = resolve(Factory::class)
        ->view('users.edit', ['user' => $callable])
        ->toResponse(mockRequest())
        ->getData();

    expect($payload->view->name)->toBe('users.edit');
    expect($payload->view->properties->user->name)->toBe('Makise Kurisu');
});

it('does not resolve partials by default', function () {
    $payload = resolve(Factory::class)
        ->view('users.edit', [
            'full_name' => hybridly()->partial(fn () => 'Jon Doe'),
            'email' => 'jon@example.org',
        ])
        ->toResponse(mockRequest())
        ->getData();

    expect($payload->view->name)->toBe('users.edit');
    expect($payload->view->properties)->user->toBeNull();
    expect($payload->view->properties)->email->toBe('jon@example.org');
});

it('does not resolve nested partials by default', function () {
    $payload = resolve(Factory::class)
        ->view('users.edit', [
            'user' => [
                'full_name' => hybridly()->partial(fn () => 'Jon Doe'),
                'email' => 'jon@doe.example',
            ],
        ])
        ->toResponse(mockRequest())
        ->getData();

    expect($payload->view->name)->toBe('users.edit');
    expect($payload->view->properties->user)->full_name->toBeNull();
    expect($payload->view->properties->user)->email->toBe('jon@doe.example');
});

it('resolves partials', function () {
    $payload = resolve(Factory::class)
        ->view('users.edit', [
            'full_name' => hybridly()->partial(fn () => 'Jon Doe'),
            'email' => 'jon@example.org',
        ])
        ->toResponse(mockRequest(headers: [
            Hybridly::PARTIAL_COMPONENT_HEADER => 'users.edit',
            Hybridly::ONLY_DATA_HEADER => json_encode(['full_name', 'email']),
        ]))
        ->getData();

    expect($payload->view->name)->toBe('users.edit');
    expect($payload->view->properties)->full_name->toBe('Jon Doe');
    expect($payload->view->properties)->email->toBe('jon@example.org');
});

it('resolves nested partials', function () {
    $payload = resolve(Factory::class)
        ->view('users.edit', [
            'user' => [
                'full_name' => hybridly()->partial(fn () => 'Jon Doe'),
                'email' => 'jon@example.org',
            ],
        ])
        ->toResponse(mockRequest(headers: [
            Hybridly::PARTIAL_COMPONENT_HEADER => 'users.edit',
            Hybridly::ONLY_DATA_HEADER => json_encode(['user.full_name', 'user.email']),
        ]))
        ->getData();

    expect($payload->view->name)->toBe('users.edit');
    expect($payload->view->properties->user)->full_name->toBe('Jon Doe');
    expect($payload->view->properties->user)->email->toBe('jon@example.org');
});
