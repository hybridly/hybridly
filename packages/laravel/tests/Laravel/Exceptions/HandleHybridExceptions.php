<?php

use Hybridly\Exceptions\HandleHybridExceptions;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Hybridly\view;
use function Pest\Laravel\get;

function register_handler(callable $using)
{
    app()->singleton(
        \Illuminate\Contracts\Debug\ExceptionHandler::class,
        \Illuminate\Foundation\Exceptions\Handler::class,
    );

    app()->afterResolving(
        \Illuminate\Foundation\Exceptions\Handler::class,
        fn ($handler) => $using(new Exceptions($handler)),
    );
}

test('exceptions are rendered using the specified callback', function () {
    register_handler(
        HandleHybridExceptions::register()
            ->inEnvironments('testing')
            ->renderUsing(fn (Response $response) => view('error', [
                'status' => $response->getStatusCode(),
            ])),
    );

    Route::get('/404', fn () => throw new NotFoundHttpException());

    get('404')
        ->assertHybridView('error')
        ->assertHybridProperties([
            'status' => 404,
        ]);
});

test('session expiration are rendered using the specified callback', function () {
    register_handler(
        HandleHybridExceptions::register()
            ->inEnvironments('testing')
            ->renderUsing(fn (\Throwable $e) => view('error', ['message' => $e->getMessage()]))
            ->expireSessionUsing(fn () => back()->with([
                'error' => 'Your session has expired. Please refresh the page.',
            ])),
    );

    Route::get('/419', fn () => throw new TokenMismatchException('CSRF token mismatch.'));

    get('419', ['referer' => '/previous'])
        ->assertRedirect('/previous')
        ->assertSessionHas('error');
});

test('exceptions are not rendered when outside of the specified environments', function () {
    register_handler(
        HandleHybridExceptions::register()
            ->inEnvironments('production')
            ->renderUsing(fn (Response $response) => view('error', [
                'status' => $response->getStatusCode(),
            ])),
    );

    Route::get('/404', fn () => throw new NotFoundHttpException());

    get('404')
        ->assertNotHybrid()
        ->assertNotFound();
});

test('only the specified status codes are handled', function () {
    register_handler(
        HandleHybridExceptions::register()
            ->inEnvironments('testing')
            ->handleStatusCodes(404)
            ->renderUsing(fn (Response $response) => view('error', [
                'status' => $response->getStatusCode(),
            ])),
    );

    Route::get('/500', fn () => throw new \Exception());

    get('500')
        ->assertNotHybrid()
        ->assertStatus(500);
});
