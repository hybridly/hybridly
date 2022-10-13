<?php

use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;

test('the middleware applies session errors', function () {
    Route::middleware(StartSession::class, \Hybridly\Http\Middleware::class)
        ->get('/', fn () => hybridly('users.edit', ['user' => 'Makise Kurisu']));

    $response = get('/');
    $response->assertOk();
    $response->assertViewIs('root');
    $payload = $response->getOriginalContent()->getData()['payload'];

    expect(data_get($payload, 'view.properties.errors'))->toBeObject();
    expect(data_get($payload, 'view.properties.user'))->toBe('Makise Kurisu');
});
