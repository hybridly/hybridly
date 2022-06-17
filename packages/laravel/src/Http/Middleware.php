<?php

namespace Sleightful\Http;

use Closure;
use Illuminate\Http\Request;
use Sleightful\Concerns;
use Sleightful\Sleightful;
use Symfony\Component\HttpFoundation\Response;

class Middleware
{
    use Concerns\HasRootView;
    use Concerns\HasVersion;
    use Concerns\HasSharedProperties;
    use Concerns\SharesValidationErrors;
    use Concerns\SharesFlashNotifications;

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        sleightful()->setRootView(fn () => $this->rootView($request));
        sleightful()->setVersion(fn () => $this->version($request));
        sleightful()->share($this->share($request));

        if ($this->shareValidationErrors) {
            sleightful()->share($this->shareValidationErrors($request));
        }

        if ($this->shareFlashNotifications) {
            sleightful()->share($this->shareFlashNotifications($request));
        }

        $response = $next($request);

        // Browsers need the Vary header in order to properly cache the response
        // based on its content type. This is specifically important for the
        // sleightful protocol because an endpoint can send JSON and HTML.
        $response->headers->set('Vary', Sleightful::SLEIGHTFUL_HEADER);
        
        if (!$request->header(Sleightful::SLEIGHTFUL_HEADER)) {
            return $next($request);
        }

        // When handling GET requests, we need to check the version header received
        // from the client to determine if they match. If not, we trigger the version change
        // event.
        if ($request->method() === 'GET' && $request->header(Sleightful::VERSION_HEADER) !== sleightful()->getVersion()) {
            $response = $this->onVersionChange($request, $response);
        }

        // If the response has no content, eg. the developer did not return anything from
        // a controller, we can transform the response to have a default behavior.
        if ($response->isOk() && empty($response->getContent())) {
            $response = $this->onEmptyResponse($request, $response);
        }

        if ($response->getStatusCode() === Response::HTTP_FOUND && \in_array($request->method(), ['PUT', 'PATCH', 'DELETE'])) {
            $response->setStatusCode(Response::HTTP_SEE_OTHER);
        }

        return $response;
    }

    /**
     * Determines what to do when the asset version has changed.
     * By default, we'll initiate a client-side external visit to force an update.
     */
    public function onVersionChange(Request $request, Response $response): Response
    {
        if ($request->hasSession()) {
            $request->session()->reflash();
        }

        return sleightful()->external($request->fullUrl());
    }

    /**
     * Determines what to do when an action returned with no response.
     */
    public function onEmptyResponse(Request $request, Response $response): Response
    {
        return $response;
    }

    /**
     * Defines the properties that are shared to all requests.
     */
    public function share(Request $request): array
    {
        return [];
    }

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): \Closure|string|false|null
    {
        if (class_exists($vite = Innocenzi\Vite\Vite::class)) {
            return resolve($vite)->getHash();
        }

        if (config('app.asset_url')) {
            return md5(config('app.asset_url'));
        }

        if (file_exists($manifest = public_path('mix-manifest.json'))) {
            return md5_file($manifest);
        }

        return null;
    }

    /**
     * Sets the root template that's loaded on the first page visit.
     */
    public function rootView(Request $request): \Closure|string
    {
        return $this->rootView;
    }

    /**
     * Resolves and prepares validation errors in such
     * a way that they are easier to use client-side.
     */
    public function resolveValidationErrors(Request $request): object
    {
        if (!$errors = $request->session()->get('errors')) {
            return (object) [];
        }

        return (object) collect($errors->getBags())
            ->map(function ($bag) {
                return (object) collect($bag->messages())
                    ->map(fn ($errors) => $errors[0])
                    ->toArray();
            })
            ->pipe(function ($bags) use ($request) {
                if ($bags->has('default') && $request->header(Sleightful::ERROR_BAG_HEADER)) {
                    return [$request->header(Sleightful::ERROR_BAG_HEADER) => $bags->get('default')];
                }

                if ($bags->has('default')) {
                    return $bags->get('default');
                }

                return $bags->toArray();
            });
    }
}
