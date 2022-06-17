<?php

use Illuminate\Http\JsonResponse;
use Sleightful\Sleightful;
use Sleightful\View\Factory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

test('external responses to non-sleightful requests', function () {
    mockRequest(sleightful: false, bind: true);

    expect(sleightful()->external('https://google.fr/'))
        ->toBeInstanceOf(RedirectResponse::class)
        ->getStatusCode()->toBe(Response::HTTP_FOUND)
        ->headers->all()->toMatchArray([
            'location' => ['https://google.fr/'],
        ]);
});

test('external responses to sleightful requests', function () {
    mockRequest(sleightful: true, bind: true);

    expect(sleightful()->external('https://google.fr/'))
        ->toBeInstanceOf(Response::class)
        ->getStatusCode()->toBe(Response::HTTP_CONFLICT)
        ->headers->all()->toMatchArray([
            Sleightful::EXTERNAL_HEADER => ['https://google.fr/'],
        ]);
});

test('external responses with redirect responses as input', function () {
    mockRequest(sleightful: true, bind: true);
    
    $redirect = new RedirectResponse('https://google.fr/');

    expect(sleightful()->external($redirect))
        ->toBeInstanceOf(Response::class)
        ->getStatusCode()->toBe(Response::HTTP_CONFLICT)
        ->headers->all()->toMatchArray([
            Sleightful::EXTERNAL_HEADER => ['https://google.fr/'],
        ]);
});

test('sleightful responses to non-sleightful requests', function () {
    sleightful()->setRootView('root');
    sleightful()->setVersion('123');
    
    $request = mockRequest(url: '/users/makise', sleightful: false, bind: true);
    $factory = sleightful('users.edit', ['user' => 'Makise Kurisu']);
    $response = $factory->toResponse($request);
    $payload = $response->getOriginalContent()->getData()['payload'];

    expect($factory)->toBeInstanceOf(Factory::class);
    expect($response)->toBeInstanceOf(Response::class);
    expect($payload)->toMatchArray([
        'dialog' => null,
        'version' => '123',
        'url' => 'http://localhost/users/makise',
        'view' => [
            'name' => 'users.edit',
            'properties' => [
                'user' => 'Makise Kurisu',
            ],
        ],
    ]);
});

test('sleightful responses to sleightful requests', function () {
    sleightful()->setRootView('root');
    sleightful()->setVersion('123');
    
    $request = mockRequest(url: '/users/makise', sleightful: true, bind: true);
    $factory = sleightful('users.edit', ['user' => 'Makise Kurisu']);
    $response = $factory->toResponse($request);
    $payload = $response->getOriginalContent();

    expect($factory)->toBeInstanceOf(Factory::class);
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($payload)->toMatchArray([
        'dialog' => null,
        'version' => '123',
        'url' => 'http://localhost/users/makise',
        'view' => [
            'name' => 'users.edit',
            'properties' => [
                'user' => 'Makise Kurisu',
            ],
        ],
    ]);
});

test('properties can be added on-the-fly on the factory instance', function () {
    sleightful()->setRootView('root');
    sleightful()->setVersion('123');
    
    $request = mockRequest(url: '/users/makise', sleightful: true, bind: true);
    $factory = sleightful('users.edit', ['user' => 'Makise Kurisu'])
        ->with('husband', 'Okabe Rintarou');

    $response = $factory->toResponse($request);
    $payload = $response->getOriginalContent();

    expect($factory)->toBeInstanceOf(Factory::class);
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($payload)->toMatchArray([
        'dialog' => null,
        'version' => '123',
        'url' => 'http://localhost/users/makise',
        'view' => [
            'name' => 'users.edit',
            'properties' => [
                'user' => 'Makise Kurisu',
                'husband' => 'Okabe Rintarou',
            ],
        ],
    ]);
});
