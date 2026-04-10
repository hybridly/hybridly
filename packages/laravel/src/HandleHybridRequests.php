<?php

namespace Hybridly;

use Closure;
use Hybridly\Hybridly;
use Hybridly\Support\Header;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class HandleHybridRequests
{
    public function __construct(
        private readonly Hybridly $hybridly,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // TODO: migrate to its own protocol-level property
        $this->shareValidationErrors($request);

        $response = $next($request);

        // Browsers need the Vary header in order to properly cache the response
        // based on its content type. This is specifically important for the
        // hybridly protocol because an endpoint can send JSON and HTML.
        $response->headers->set('Vary', Header::HYBRID_REQUEST);

        if (! $request->header(Header::HYBRID_REQUEST)) {
            return $response;
        }

        // When handling GET requests, we need to check the version header received from
        // the client to determine if they match. If not, the asset version has changed,
        // and as such we need to force a client-side hard reload for assets to update.
        if ($request->method() === 'GET' && $request->header(Header::VERSION) !== $this->hybridly->version) {
            if ($request->hasSession()) {
                $request->session()->reflash();
            }

            $response = $this->hybridly->createExternalRedirect($request->fullUrl());
        }

        if ($response->getStatusCode() === Response::HTTP_FOUND && \in_array($request->method(), ['PUT', 'PATCH', 'DELETE'], true)) {
            $response->setStatusCode(Response::HTTP_SEE_OTHER);
        }

        return $response;
    }

    /**
     * Shares validation errors to all requests.
     *
     * @deprecated
     */
    private function shareValidationErrors(Request $request): void
    {
        $this->hybridly->persist('errors');

        $this->hybridly->share([
            'errors' => function () use ($request) {
                return $this->resolveValidationErrors($request);
            },
        ]);
    }

    /**
     * Resolves and prepares validation errors in such
     * a way that they are easier to use client-side.
     *
     * @deprecated
     */
    public function resolveValidationErrors(Request $request): object
    {
        if (! $request->hasSession()) {
            return (object) [];
        }

        if (! ($errors = $request->session()->get('errors'))) {
            return (object) [];
        }

        return (object) collect($errors->getBags())
            ->map(function ($bag) {
                return (object) collect($bag->messages())
                    ->map(fn ($errors) => $errors[0])
                    ->toArray();
            })
            ->pipe(function ($bags) use ($request) {
                if ($bags->has('default') && $request->header(Header::ERROR_BAG)) {
                    return [$request->header(Header::ERROR_BAG) => $bags->get('default')];
                }

                if ($bags->has('default')) {
                    return $bags->get('default');
                }

                return $bags->toArray();
            });
    }
}
