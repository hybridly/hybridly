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
}
