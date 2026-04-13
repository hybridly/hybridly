<?php

namespace Hybridly;

use Closure;
use Hybridly\Components\Concerns\EvaluatesClosures;
use Hybridly\HybridResponse;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Foundation\ViteException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

final class HybridExceptionHandler
{
    use EvaluatesClosures;

    /**
     * Whether the exception handler has been registered or not.
     */
    private(set) bool $registered = false;

    protected ?Closure $obtainHandledEnvironmentsUsing = null;
    protected ?Closure $obtainHandledStatusCodesUsing = null;
    protected ?Closure $renderExceptionsUsing = null;
    protected ?Closure $handleSessionExpirationUsing = null;

    public function __construct(
        private readonly ExceptionHandlerContract $handler,
        private readonly Application $application,
    ) {}

    /**
     * Registers the exception handler to handle exceptions thrown in the application.
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        if ($this->obtainHandledStatusCodesUsing === null) {
            $this->handleStatusCodes([
                Response::HTTP_INTERNAL_SERVER_ERROR,
                Response::HTTP_SERVICE_UNAVAILABLE,
                Response::HTTP_NOT_FOUND,
                Response::HTTP_FORBIDDEN,
                Response::HTTP_UNAUTHORIZED,
            ]);
        }

        if ($this->obtainHandledEnvironmentsUsing === null) {
            $this->renderExceptionsIn(['production']);
        }

        if ($this->handler instanceof ExceptionHandler) {
            $this->handler->respondUsing($this->respondUsing(...));
            $this->registered = true;
        }
    }

    /**
     * Defines the response that will be rendered when an exception occurs.
     */
    public function renderExceptionsUsing(callable $callback): static
    {
        $this->renderExceptionsUsing = Closure::fromCallable($callback);

        return $this;
    }

    /**
     * Defines the response that will be rendered when the session is expired.
     */
    public function handleSessionExpirationUsing(callable $callback): static
    {
        $this->handleSessionExpirationUsing = Closure::fromCallable($callback);

        return $this;
    }

    /**
     * Only handle the specified status codes.
     */
    public function handleStatusCodes(Closure|string|array $codes): static
    {
        if (! ($codes instanceof Closure)) {
            $codes = fn () => Arr::wrap($codes);
        }

        $this->obtainHandledStatusCodesUsing = $codes;

        return $this;
    }

    /**
     * Specify in which environments the handling should be done.
     */
    public function renderExceptionsIn(Closure|string|array $environments): static
    {
        if (! ($environments instanceof Closure)) {
            $environments = fn () => Arr::wrap($environments);
        }

        $this->obtainHandledEnvironmentsUsing = $environments;

        return $this;
    }

    /**
     * Ensures exceptions are rendered using Hybridly even in development.
     */
    public function renderExceptionsInDevelopment(): static
    {
        $this->renderExceptionsIn(['production', 'local']);

        return $this;
    }

    private function respondUsing(Response $response, \Throwable $e, Request $request): Response
    {
        if ($this->shouldHandleTokenMismatch($response, $request, $e)) {
            return $this->onSessionExpired($response, $request, $e);
        }

        if ($this->shouldRenderHybridResponse($response, $request, $e)) {
            return $this->renderHybridResponse($response, $request, $e)
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
        }

        return $response;
    }

    private function onSessionExpired(Response $response, Request $request, \Throwable $e): mixed
    {
        $callback =
            $this->handleSessionExpirationUsing ??
            fn () => redirect()
                ->back()
                ->with([
                    'error' => 'Your session has expired. Please refresh the page.',
                ]);

        return $this->evaluate(
            $callback,
            [
                'response' => $response,
                'request' => $request,
                'exception' => $e,
            ],
            [
                Response::class => $response,
                Request::class => $request,
                \Throwable::class => $e,
                \Exception::class => $e,
            ],
        );
    }

    private function renderHybridResponse(Response $response, Request $request, \Throwable $e): HybridResponse
    {
        if (\is_null($this->renderExceptionsUsing)) {
            throw new \Exception('The `renderHybridResponse` method is not implemented.');
        }

        return $this->evaluate(
            $this->renderExceptionsUsing,
            [
                'response' => $response,
                'request' => $request,
                'exception' => $e,
                'e' => $e,
            ],
            [
                Response::class => $response,
                Request::class => $request,
                \Throwable::class => $e,
                \Exception::class => $e,
            ],
        );
    }

    private function shouldRenderHybridResponse(Response $response, Request $request, \Throwable $e): bool
    {
        if ($e instanceof ViteException) {
            return false;
        }

        if (! $this->application->environment($this->evaluate($this->obtainHandledEnvironmentsUsing))) {
            return false;
        }

        return \in_array($response->getStatusCode(), $this->evaluate($this->obtainHandledStatusCodesUsing), strict: true);
    }

    private function shouldHandleTokenMismatch(Response $response, Request $request, \Throwable $e): bool
    {
        return $response->getStatusCode() === 419;
    }
}
