<?php

use Illuminate\Http\JsonResponse;
use Monolikit\Monolikit;
use Monolikit\View\Factory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

test('external responses to non-monolikit requests', function () {
    mockRequest(monolikit: false, bind: true);

    expect(monolikit()->external('https://google.fr/'))
        ->toBeInstanceOf(RedirectResponse::class)
        ->getStatusCode()->toBe(Response::HTTP_FOUND)
        ->headers->all()->toMatchArray([
            'location' => ['https://google.fr/'],
        ]);
});

test('external responses to monolikit requests', function () {
    mockRequest(monolikit: true, bind: true);

    expect(monolikit()->external('https://google.fr/'))
        ->toBeInstanceOf(Response::class)
        ->getStatusCode()->toBe(Response::HTTP_CONFLICT)
        ->headers->all()->toMatchArray([
            Monolikit::EXTERNAL_HEADER => ['https://google.fr/'],
        ]);
});

test('external responses with redirect responses as input', function () {
    mockRequest(monolikit: true, bind: true);
    
    $redirect = new RedirectResponse('https://google.fr/');

    expect(monolikit()->external($redirect))
        ->toBeInstanceOf(Response::class)
        ->getStatusCode()->toBe(Response::HTTP_CONFLICT)
        ->headers->all()->toMatchArray([
            Monolikit::EXTERNAL_HEADER => ['https://google.fr/'],
        ]);
});

test('monolikit responses to non-monolikit requests', function () {
    monolikit()->setRootView('root');
    monolikit()->setVersion('123');
    
    $request = mockRequest(url: '/users/makise', monolikit: false, bind: true);
    $factory = monolikit('users.edit', ['user' => 'Makise Kurisu']);
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

test('monolikit responses to monolikit requests', function () {
    monolikit()->setRootView('root');
    monolikit()->setVersion('123');
    
    $request = mockRequest(url: '/users/makise', monolikit: true, bind: true);
    $factory = monolikit('users.edit', ['user' => 'Makise Kurisu']);
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
    monolikit()->setRootView('root');
    monolikit()->setVersion('123');
    
    $request = mockRequest(url: '/users/makise', monolikit: true, bind: true);
    $factory = monolikit('users.edit', ['user' => 'Makise Kurisu'])
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
