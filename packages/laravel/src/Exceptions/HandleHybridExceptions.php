<?php

namespace Hybridly\Exceptions;

use Exception;
use Hybridly\Components\Concerns\EvaluatesClosures;
use Hybridly\Contracts\HybridResponse;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\ViteManifestNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class HandleHybridExceptions
{
    use EvaluatesClosures;

    /**
     * List of HTTP status codes that Hybridly will handle.
     */
    protected array $statusCodes = [
        Response::HTTP_INTERNAL_SERVER_ERROR,
        Response::HTTP_SERVICE_UNAVAILABLE,
        Response::HTTP_NOT_FOUND,
        Response::HTTP_FORBIDDEN,
        Response::HTTP_UNAUTHORIZED,
    ];

    /**
     * Environments in which the exceptions are handled.
     */
    protected array $environments = ['production'];

    protected array $withExceptionsCallbacks = [];
    protected null|\Closure $renderUsingCallback = null;
    protected null|\Closure $expireSessionUsingCallback = null;

    public function __invoke(Exceptions $exceptions): void
    {
        foreach ($this->withExceptionsCallbacks as $callback) {
            $callback($exceptions);
        }

        $exceptions->respond($this->respondUsing(...));
    }

    /**
     * Registers the exception handler.
     */
    public static function register(): static
    {
        return new static();
    }

    /**
     * Defines the response that will be rendered when an exception occurs.
     */
    public function renderUsing(callable $callback): static
    {
        $this->renderUsingCallback = $callback;

        return $this;
    }

    /**
     * Defines the response that will be rendered when the session is expired.
     */
    public function expireSessionUsing(callable $callback): static
    {
        $this->expireSessionUsingCallback = $callback;

        return $this;
    }

    /**
     * Hooks into the underlying exception handler.
     */
    public function withExceptions(callable $callback): static
    {
        $this->withExceptionsCallbacks[] = $callback;

        return $this;
    }

    /**
     * Only handle the specified status codes.
     */
    public function handleStatusCodes(string|array $codes): static
    {
        $this->statusCodes = Arr::wrap($codes);

        return $this;
    }

    /**
     * Specify in which environments the handling should be done.
     */
    public function inEnvironments(null|string|array $environments = null): static
    {
        if (!\is_null($environments)) {
            $this->environments = Arr::wrap($environments);
        }

        return $this;
    }

    protected function respondUsing(Response $response, \Throwable $e, Request $request): Response
    {
        if ($this->shouldHandleTokenMismatch($response, $request, $e)) {
            return $this->onSessionExpired($response, $request, $e);
        }

        if ($this->shouldRenderHybridResponse($response, $request, $e)) {
            return $this->renderHybridResponse($response, $request, $e)
                ->toResponse($request)
                ->setStatusCode($response->status());
        }

        return $response;
    }

    protected function onSessionExpired(Response $response, Request $request, \Throwable $e): mixed
    {
        $callback = $this->expireSessionUsingCallback ?? fn () => redirect()->back()->with([
            'error' => 'Your session has expired. Please refresh the page.',
        ]);

        return $this->evaluate($callback, [
            'response' => $response,
            'request' => $request,
            'exception' => $e,
        ], [
            Response::class => $response,
            Request::class => $request,
            \Throwable::class => $e,
            \Exception::class => $e,
        ]);
    }

    protected function renderHybridResponse(Response $response, Request $request, \Throwable $e): HybridResponse
    {
        if (\is_null($this->renderUsingCallback)) {
            throw new Exception('The `renderHybridResponse` method is not implemented.');
        }

        return $this->evaluate($this->renderUsingCallback, [
            'response' => $response,
            'request' => $request,
            'exception' => $e,
            'e' => $e,
        ], [
            Response::class => $response,
            Request::class => $request,
            \Throwable::class => $e,
            \Exception::class => $e,
        ]);
    }

    protected function shouldRenderHybridResponse(Response $response, Request $request, \Throwable $e): bool
    {
        if ($e instanceof ViteManifestNotFoundException) {
            return false;
        }

        if (!app()->environment($this->environments)) {
            return false;
        }

        return \in_array($response->status(), $this->statusCodes, strict: true);
    }

    protected function shouldHandleTokenMismatch(Response $response, Request $request, \Throwable $e): bool
    {
        return $response->status() === 419;
    }
}
