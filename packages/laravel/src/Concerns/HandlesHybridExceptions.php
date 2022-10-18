<?php

namespace Hybridly\Concerns;

use Exception;
use Hybridly\Contracts\HybridResponse;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** @mixin Handler */
trait HandlesHybridExceptions
{
    /**
     * List of HTTP status codes that hybridly will handle.
     */
    protected $handleHybridly = [
        Response::HTTP_INTERNAL_SERVER_ERROR,
        Response::HTTP_SERVICE_UNAVAILABLE,
        Response::HTTP_NOT_FOUND,
        Response::HTTP_FORBIDDEN,
        Response::HTTP_UNAUTHORIZED,
    ];

    public function render($request, \Throwable $e)
    {
        /** @var Response $response */
        $response = parent::render($request, $e);

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

    /**
     * Returns a response when the session has expired.
     */
    protected function onSessionExpired(Response $response, Request $request, \Throwable $e): mixed
    {
        return redirect()->back()->with([
            'error' => 'Your session has expired. Please refresh the page.',
        ]);
    }

    /**
     * Returns a hybrid page that renders the exception.
     */
    protected function renderHybridResponse(Response $response, Request $request, \Throwable $e): HybridResponse
    {
        throw new Exception('The `renderHybridResponse` method is not implemented.');
    }

    /**
     * Determines whether the exception should be handled using an hybrid response.
     */
    protected function shouldRenderHybridResponse(Response $response, Request $request, \Throwable $e): bool
    {
        if (str_contains($e->getMessage(), 'Vite manifest not found')) {
            return false;
        }

        if (app()->environment(['local', 'testing'])) {
            return false;
        }

        return \in_array($response->status(), $this->handleHybridly, strict: true);
    }

    /**
     * Determines whether a token mismatch exception should be handled.
     */
    protected function shouldHandleTokenMismatch(Response $response, Request $request, \Throwable $e): bool
    {
        return $response->status() === 419;
    }
}
