<?php

use Hybridly\HybridExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Hybridly\view;
use function Pest\Laravel\get;

function register_handler(callable $configure): void
{
    $handler = resolve(HybridExceptionHandler::class);
    $configure($handler);
    $handler->register();
}

test('exceptions are rendered using the specified callback', function () {
    register_handler(fn (HybridExceptionHandler $handler) => $handler
        ->renderExceptionsIn('testing')
        ->handleStatusCodes([404])
        ->renderExceptionsUsing(fn (Response $response) => view('error', [
            'status' => $response->getStatusCode(),
        ])));

    Route::get('/404', fn () => throw new NotFoundHttpException());

    get('/404')
        ->assertHybridView('error')
        ->assertHybridProperties([
            'status' => 404,
        ]);
});

test('session expiration is handled using the specified callback', function () {
    register_handler(fn (HybridExceptionHandler $handler) => $handler
        ->renderExceptionsIn('testing')
        ->handleSessionExpirationUsing(fn () => redirect('/previous')));

    Route::get('/419', fn () => throw new TokenMismatchException('CSRF token mismatch.'));

    get('/419')->assertRedirect('/previous');
});

test('exceptions are not rendered when outside of the specified environments', function () {
    register_handler(fn (HybridExceptionHandler $handler) => $handler
        ->renderExceptionsIn('production')
        ->handleStatusCodes([404])
        ->renderExceptionsUsing(fn (Response $response) => view('error', [
            'status' => $response->getStatusCode(),
        ])));

    Route::get('/404', fn () => throw new NotFoundHttpException());

    get('/404')
        ->assertNotHybrid()
        ->assertNotFound();
});

test('only the specified status codes are handled', function () {
    Exceptions::fake();

    register_handler(fn (HybridExceptionHandler $handler) => $handler
        ->renderExceptionsIn('testing')
        ->handleStatusCodes([404])
        ->renderExceptionsUsing(fn (Response $response) => view('error', [
            'status' => $response->getStatusCode(),
        ])));

    Route::get('/500', fn () => throw new Exception('Server error'));

    get('/500')
        ->assertNotHybrid()
        ->assertStatus(500);

    Exceptions::assertReported(Exception::class);
});
