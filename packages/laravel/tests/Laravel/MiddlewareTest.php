<?php

use Hybridly\Support\Configuration\Architecture;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

use function Hybridly\view;
use function Pest\Laravel\get;

test('the middleware keeps validation outside view properties', function () {
    Route::middleware(StartSession::class, \Hybridly\HandleHybridRequests::class)
        ->get('/', fn () => view('users.edit', ['user' => 'Makise Kurisu']));

    $response = get('/');
    $response->assertOk();
    $response->assertViewIs(Architecture::ROOT_VIEW);
    $payload = $response->getOriginalContent()->getData()['payload'];

    expect(data_get($payload, 'validation'))->toBeObject();
    expect(data_get($payload, 'view.properties.errors'))->toBeNull();
    expect(data_get($payload, 'view.properties.user'))->toBe('Makise Kurisu');
});
