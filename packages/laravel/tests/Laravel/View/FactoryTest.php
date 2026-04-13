<?php

use Hybridly\Exceptions\MissingViewComponentException;
use Hybridly\HybridResponseFactory;
use Hybridly\SerializesProperties;
use Hybridly\Support\Header;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use function Hybridly\dialog;
use function Hybridly\properties;
use function Hybridly\view;

test('external responses to non-hybridly requests', function () {
    mock_request(hybrid: false, bind: true);

    expect(hybridly()->createExternalRedirect('https://google.fr/'))
        ->toBeInstanceOf(RedirectResponse::class)
        ->getStatusCode()
        ->toBe(Response::HTTP_FOUND)
        ->headers->all()->toMatchArray([
            'location' => ['https://google.fr/'],
        ]);
});

test('external responses to non-hybridly requests with custom headers', function () {
    mock_request(hybrid: false, bind: true);

    expect(hybridly()->createExternalRedirect('https://google.fr/', ['X-Robots-Tag' => 'noindex, nofollow']))
        ->toBeInstanceOf(RedirectResponse::class)
        ->getStatusCode()
        ->toBe(Response::HTTP_FOUND)
        ->headers->all()->toMatchArray([
            'location' => ['https://google.fr/'],
            'x-robots-tag' => ['noindex, nofollow'],
        ]);
});

test('external responses to hybridly requests', function () {
    mock_request(hybrid: true, bind: true);

    expect(hybridly()->createExternalRedirect('https://google.fr/'))
        ->toBeInstanceOf(Response::class)
        ->getStatusCode()
        ->toBe(Response::HTTP_CONFLICT)
        ->headers->all()->toMatchArray([
            Header::EXTERNAL => ['https://google.fr/'],
        ]);
});

test('external responses to hybridly requests with custom headers', function () {
    mock_request(hybrid: true, bind: true);

    expect(hybridly()->createExternalRedirect('https://google.fr/', ['X-Robots-Tag' => 'noindex, nofollow']))
        ->toBeInstanceOf(Response::class)
        ->getStatusCode()
        ->toBe(Response::HTTP_CONFLICT)
        ->headers->all()->toMatchArray([
            Header::EXTERNAL => ['https://google.fr/'],
            'x-robots-tag' => ['noindex, nofollow'],
        ]);
});

test('external responses with redirect responses as input', function () {
    mock_request(hybrid: true, bind: true);

    $redirect = new RedirectResponse('https://google.fr/');

    expect(hybridly()->createExternalRedirect($redirect))
        ->toBeInstanceOf(Response::class)
        ->getStatusCode()
        ->toBe(Response::HTTP_CONFLICT)
        ->headers->all()->toMatchArray([
            Header::EXTERNAL => ['https://google.fr/'],
        ]);
});

test('hybridly responses to non-hybridly requests', function () {
    hybridly()->resolveVersionUsing(fn () => '123');

    $request = mock_request(url: '/users/makise', hybrid: false, bind: true);
    $factory = view('users.edit', ['user' => 'Makise Kurisu']);
    $response = $factory->toResponse($request);
    $payload = $response->getOriginalContent()->getData()['payload'];

    expect($factory)->toBeInstanceOf(HybridResponseFactory::class);
    expect($response)->toBeInstanceOf(Response::class);
    expect($payload)
        ->toMatchArray([
            'dialog' => null,
            'version' => '123',
            'url' => 'http://localhost/users/makise',
            'view' => [
                'component' => 'users.edit',
                'properties' => [
                    'user' => 'Makise Kurisu',
                ],
                'deferred' => [],
                'mergeable' => [],
            ],
        ]);
});

test('`Hybridable` classes are serialized', function () {
    hybridly()->resolveVersionUsing(fn () => '123');

    $request = mock_request(url: '/users/makise', hybrid: true, bind: true);
    $factory = view('users.edit', ['user' => new class() implements SerializesProperties {
        public function toHybridArray(): array
        {
            return ['full_name' => 'Makise Kurisu'];
        }
    }]);

    $response = $factory->toResponse($request);
    $payload = $response->getOriginalContent();

    expect($factory)->toBeInstanceOf(HybridResponseFactory::class);
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($payload)
        ->toMatchArray([
            'dialog' => null,
            'version' => '123',
            'url' => 'http://localhost/users/makise',
            'view' => [
                'component' => 'users.edit',
                'properties' => [
                    'user' => [
                        'full_name' => 'Makise Kurisu',
                    ],
                ],
                'deferred' => [],
                'mergeable' => [],
            ],
        ]);
});

test('hybridly responses to hybridly requests', function () {
    hybridly()->resolveVersionUsing(fn () => '123');

    $request = mock_request(url: '/users/makise', hybrid: true, bind: true);
    $factory = view('users.edit', ['user' => 'Makise Kurisu']);
    $response = $factory->toResponse($request);
    $payload = $response->getOriginalContent();

    expect($factory)->toBeInstanceOf(HybridResponseFactory::class);
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($payload)
        ->toMatchArray([
            'dialog' => null,
            'version' => '123',
            'url' => 'http://localhost/users/makise',
            'view' => [
                'component' => 'users.edit',
                'properties' => [
                    'user' => 'Makise Kurisu',
                ],
                'deferred' => [],
                'mergeable' => [],
            ],
        ]);
});

test('properties can be added on-the-fly on the factory instance', function () {
    hybridly()->resolveVersionUsing(fn () => '123');

    $request = mock_request(url: '/users/makise', hybrid: true, bind: true);
    $factory = view('users.edit', ['user' => 'Makise Kurisu'])
        ->withProperty('husband', 'Okabe Rintarou');

    $response = $factory->toResponse($request);
    $payload = $response->getOriginalContent();

    expect($factory)->toBeInstanceOf(HybridResponseFactory::class);
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($payload)
        ->toMatchArray([
            'dialog' => null,
            'version' => '123',
            'url' => 'http://localhost/users/makise',
            'view' => [
                'component' => 'users.edit',
                'properties' => [
                    'user' => 'Makise Kurisu',
                    'husband' => 'Okabe Rintarou',
                ],
                'deferred' => [],
                'mergeable' => [],
            ],
        ]);
});

test('dialogs and their properties can be resolved', function () {
    Route::get('/', fn () => view('index', ['foo' => 'bar']))->name('index');

    hybridly()->share(['shared' => 'data']);

    $request = mock_request(url: '/users/makise', hybrid: true, bind: true, headers: [
        'referer' => 'http://localhost',
    ]);

    $factory = view('users.edit', [
        'user' => 'Makise Kurisu',
        'email' => fn () => 'makise@gadgetlab.jp',
    ])->configureDialog(route('index'));

    $response = $factory->toResponse($request);
    $payload = $response->getOriginalContent();

    expect($factory)->toBeInstanceOf(HybridResponseFactory::class);
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($payload)
        ->toMatchArray([
            'view' => [
                'component' => 'index',
                'properties' => [
                    'foo' => 'bar',
                    'shared' => 'data',
                ],
                'deferred' => [],
                'mergeable' => [],
            ],
            'dialog' => [
                'component' => 'users.edit',
                'properties' => [
                    'user' => 'Makise Kurisu',
                    'email' => 'makise@gadgetlab.jp',
                ],
                'baseUrl' => 'http://localhost',
                'redirectUrl' => 'http://localhost',
                'key' => data_get($payload, 'dialog.key'),
            ],
            'url' => 'http://localhost/users/makise',
            'version' => null,
        ]);
});

test('the url resolver is used when constructing a response', function () {
    hybridly()->setUrlResolver(fn (Request $request) => 'https://customdomain.com' . $request->getRequestUri());

    $request = mock_request(url: '/users/makise', hybrid: true, bind: true);
    $payload = view('foo.bar')
        ->toResponse($request)
        ->getOriginalContent();

    expect($payload)
        ->toMatchArray([
            'url' => 'https://customdomain.com/users/makise',
        ]);
});

test('hybridly responses without a view component', function () {
    hybridly()->resolveVersionUsing(fn () => '123');

    $request = mock_request(url: '/users/makise', hybrid: true, bind: true);
    $factory = properties(['user' => 'Makise Kurisu']);
    $response = $factory->toResponse($request);
    $payload = $response->getOriginalContent();

    expect($factory)->toBeInstanceOf(HybridResponseFactory::class);
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($payload)
        ->toMatchArray([
            'dialog' => null,
            'version' => '123',
            'url' => 'http://localhost/users/makise',
            'view' => [
                'component' => null,
                'properties' => [
                    'user' => 'Makise Kurisu',
                ],
                'deferred' => [],
                'mergeable' => [],
            ],
        ]);
});

test('hybridly responses without a view component on initial load', function () {
    hybridly()->resolveVersionUsing(fn () => '123');

    $request = mock_request(url: '/users/makise', hybrid: false, bind: true);
    $factory = properties(['user' => 'Makise Kurisu']);
    $factory->toResponse($request);
})->throws(MissingViewComponentException::class);

test('base view may be omitted on dialog responses coming from hybrid requests', function () {
    Route::get('/', fn () => view('index', ['foo' => 'bar']))->name('index');

    $request = mock_request(url: '/users/makise', hybrid: true, bind: true);
    $factory = view('users.edit', [
        'user' => 'Makise Kurisu',
        'email' => fn () => 'makise@gadgetlab.jp',
    ])->configureDialog(route('index'), preserveBaseOnClose: true);

    $response = $factory->toResponse($request);
    $payload = $response->getOriginalContent();

    expect($factory)->toBeInstanceOf(HybridResponseFactory::class);
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($payload)
        ->toMatchArray([
            'view' => null,
            'dialog' => [
                'component' => 'users.edit',
                'properties' => [
                    'user' => 'Makise Kurisu',
                    'email' => 'makise@gadgetlab.jp',
                ],
                'baseUrl' => 'http://localhost',
                'redirectUrl' => 'http://localhost',
                'key' => data_get($payload, 'dialog.key'),
            ],
            'url' => 'http://localhost/users/makise',
            'version' => null,
        ]);
});

test('base view may not be omitted on dialog responses coming from non-hybrid requests', function () {
    Route::get('/', fn () => view('index', ['foo' => 'bar']))->name('index');

    $request = mock_request(url: '/users/makise', hybrid: false, bind: true);
    $factory = view('users.edit', [
        'user' => 'Makise Kurisu',
        'email' => fn () => 'makise@gadgetlab.jp',
    ])->configureDialog(route('index'), preserveBaseOnClose: true);

    $response = $factory->toResponse($request);
    $payload = $response->getOriginalContent()->getData();

    expect($factory)->toBeInstanceOf(HybridResponseFactory::class);
    expect($response)->not->toBeInstanceOf(JsonResponse::class);
    expect($payload['payload'])
        ->toMatchArray([
            'view' => [
                'component' => 'index',
                'properties' => [
                    'foo' => 'bar',
                ],
                'deferred' => [],
                'mergeable' => [],
            ],
            'dialog' => [
                'component' => 'users.edit',
                'properties' => [
                    'user' => 'Makise Kurisu',
                    'email' => 'makise@gadgetlab.jp',
                ],
                'baseUrl' => 'http://localhost',
                'redirectUrl' => 'http://localhost',
                'key' => data_get($payload['payload'], 'dialog.key'),
            ],
            'url' => 'http://localhost/users/makise',
            'version' => null,
        ]);
});

test('a redirect to the base view may be forced', function () {
    Route::get('/', fn () => view('index', ['foo' => 'bar']))->name('index');
    Route::get('/foo', fn () => view('foo'))->name('foo');

    $request = mock_request(url: '/users/makise', hybrid: true, bind: true, headers: [
        'referer' => 'http://localhost/foo',
    ]);

    $factory = dialog(
        component: 'users.edit',
        baseUrl: route('index'),
        properties: [
            'user' => 'Makise Kurisu',
            'email' => fn () => 'makise@gadgetlab.jp',
        ],
        alwaysRedirectToBase: true,
    );

    $response = $factory->toResponse($request);
    $payload = $response->getOriginalContent();

    expect($factory)->toBeInstanceOf(HybridResponseFactory::class);
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($payload)
        ->toMatchArray([
            'view' => [
                'component' => 'index',
                'properties' => [
                    'foo' => 'bar',
                ],
                'deferred' => [],
                'mergeable' => [],
            ],
            'dialog' => [
                'component' => 'users.edit',
                'properties' => [
                    'user' => 'Makise Kurisu',
                    'email' => 'makise@gadgetlab.jp',
                ],
                'baseUrl' => 'http://localhost',
                'redirectUrl' => 'http://localhost',
                'key' => data_get($payload, 'dialog.key'),
            ],
            'url' => 'http://localhost/users/makise',
            'version' => null,
        ]);
});
