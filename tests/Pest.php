<?php

use Hybridly\Hybridly;
use Hybridly\Refining\Refine;
use Hybridly\Tests\Fixtures\Database\Product;
use Hybridly\Tests\TestCase;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\get;

uses(TestCase::class)
    ->beforeEach(fn () => config()->set('hybridly.testing.ensure_pages_exist', false))
    ->in(__DIR__);

function mockRequest(string $url = '/', string $method = 'GET', bool $bind = false, bool $hybridly = true, array $headers = []): Request
{
    $request = Request::create($url, $method);

    if ($headers) {
        $request->headers->add($headers);
    }

    if ($hybridly) {
        $request->headers->add([Hybridly::HYBRIDLY_HEADER => 'true']);
    }

    if ($bind) {
        app()->bind('request', fn () => $request);
    }

    return $request;
}

function makeMockRequest(mixed $response, string $url = '/mock-url'): TestResponse
{
    app('router')->get($url, function () use ($response) {
        return $response;
    });

    return get($url);
}

function makeHybridMockRequest(string $component = 'test', mixed $properties = [], string $url = '/hybrid-mock-url'): TestResponse
{
    return makeMockRequest(
        response: hybridly($component, $properties),
        url: $url,
    );
}

function mock_refiner(array $refiners, array $query = null, \Closure $callback = null, string|Builder $classOrQuery = Product::class, bool $apply = false): Refine
{
    $request = Request::createFromGlobals();

    if ($callback) {
        $callback($request);
    }

    if ($query) {
        $request->query->add($query);
    }

    app()->when(Refine::class)->needs(Request::class)->give(fn () => $request);

    $refine = Refine::query($classOrQuery)->with($refiners);

    if ($apply) {
        $refine->applyRefiners();
    }

    return $refine;
}
