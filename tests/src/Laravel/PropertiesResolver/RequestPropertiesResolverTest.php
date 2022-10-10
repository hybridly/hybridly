<?php

use Illuminate\Contracts\Support\Arrayable;
use Hybridly\View\Factory;

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
