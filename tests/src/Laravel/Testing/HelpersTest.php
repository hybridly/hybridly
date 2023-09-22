<?php

use Hybridly\Support\Header;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

use function Hybridly\partial;
use function Hybridly\Testing\partial_headers;
use function Pest\Laravel\get;

test('the `partial_headers` helper generates headers for partial requests', function () {
    expect(partial_headers('foo.component', only: [
        'user.name',
    ]))->toBe([
        Header::PARTIAL_COMPONENT => 'foo.component',
        Header::PARTIAL_ONLY => json_encode(['user.name']),
    ]);

    expect(partial_headers('foo.component', except: [
        'user.name',
    ]))->toBe([
        Header::PARTIAL_COMPONENT => 'foo.component',
        Header::PARTIAL_EXCEPT => json_encode(['user.name']),
    ]);

    expect(partial_headers('foo.component'))->toBe([
        Header::PARTIAL_COMPONENT => 'foo.component',
    ]);

    expect(partial_headers('foo.component', except: ['foo'], only: ['bar']))->toBe([
        Header::PARTIAL_COMPONENT => 'foo.component',
        Header::PARTIAL_ONLY => json_encode(['bar']),
        Header::PARTIAL_EXCEPT => json_encode(['foo']),
    ]);
});

test('the `partial_headers` works for actual requests', function () {
    Route::middleware(StartSession::class, \Hybridly\Http\Middleware::class)
        ->get('/', fn () => hybridly('foo.component', [
            'partial' => partial(fn () => 'Partial property'),
            'normal' => 'Normal property',
        ]));

    get('/')
        ->assertMissingHybridProperty('partial', 'Partial property')
        ->assertHybridProperty('normal', 'Normal property');

    get('/', partial_headers('foo.component', only: ['partial']))
        ->assertHybridProperty('partial', 'Partial property')
        ->assertMissingHybridProperty('normal');

    get('/', partial_headers('foo.component', except: ['partial']))
        ->assertMissingHybridProperty('partial', 'Partial property')
        ->assertHybridProperty('normal', 'Normal property');

    get('/', partial_headers('foo.component', except: ['normal']))
        ->assertHybridProperty('partial', 'Partial property')
        ->assertMissingHybridProperty('normal');
});
