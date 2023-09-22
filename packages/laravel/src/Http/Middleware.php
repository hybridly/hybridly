<?php

namespace Hybridly\Http;

use Closure;
use Hybridly\Concerns;
use Hybridly\Hybridly;
use Hybridly\Support\Header;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class Middleware
{
    use Concerns\SharesFlashNotifications;
    use Concerns\SharesValidationErrors;

    /**
     * Marks the given properties as persisted, which means they will
     * always be present, even in partial responses.
     */
    protected array $persistent = [];

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (method_exists($this, 'beforeHandle')) {
            app()->call([$this, 'beforeHandle']);
        }

        hybridly()->setRootView(fn () => $this->rootView($request));
        hybridly()->setVersion(fn () => $this->version($request));
        hybridly()->persist($this->persistent);

        if (method_exists($this, 'share')) {
            hybridly()->share(app()->call([$this, 'share']));
        }

        if ($this->shareValidationErrors) {
            hybridly()->share($this->shareValidationErrors($request));
        }

        if ($this->shareFlashNotifications) {
            hybridly()->share($this->shareFlashNotifications($request));
        }

        $response = $next($request);

        // Browsers need the Vary header in order to properly cache the response
        // based on its content type. This is specifically important for the
        // hybridly protocol because an endpoint can send JSON and HTML.
        $response->headers->set('Vary', Header::HYBRID_REQUEST);

        if (!$request->header(Header::HYBRID_REQUEST)) {
            return $response;
        }

        // When handling GET requests, we need to check the version header received
        // from the client to determine if they match. If not, we trigger the version change
        // event.
        if ($request->method() === 'GET' && $request->header(Header::VERSION) !== hybridly()->getVersion()) {
            $response = $this->onVersionChange($request, $response);
        }

        // If the response has no content, eg. the developer did not return anything from
        // a controller, we can transform the response to have a default behavior.
        if ($response->isOk() && empty($response->getContent())) {
            $response = $this->onEmptyResponse($request, $response);
        }

        if ($response->getStatusCode() === Response::HTTP_FOUND && \in_array($request->method(), ['PUT', 'PATCH', 'DELETE'], true)) {
            $response->setStatusCode(Response::HTTP_SEE_OTHER);
        }

        return $response;
    }

    /**
     * Determines what to do when the asset version has changed.
     * By default, we'll initiate a client-side hard reload to force an update.
     */
    public function onVersionChange(Request $request, Response $response): Response
    {
        if ($request->hasSession()) {
            $request->session()->reflash();
        }

        return hybridly()->external($request->fullUrl());
    }

    /**
     * Determines what to do when an action returned with no response.
     */
    public function onEmptyResponse(Request $request, Response $response): Response
    {
        return $response;
    }

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): \Closure|string|false|null
    {
        if (class_exists($vite = Innocenzi\Vite\Vite::class)) {
            return resolve($vite)->getHash();
        }

        if (class_exists(Illuminate\Foundation\Vite::class)) {
            return Vite::manifestHash();
        }

        if (config('app.asset_url')) {
            return md5(config('app.asset_url'));
        }

        if (file_exists($manifest = public_path('build/manifest.json'))) {
            return md5_file($manifest);
        }

        if (file_exists($manifest = public_path('mix-manifest.json'))) {
            return md5_file($manifest);
        }

        return null;
    }

    /**
     * Sets the root template that's loaded on the initial page load.
     */
    public function rootView(Request $request): \Closure|string
    {
        return config('hybridly.root_view');
    }

    /**
     * Resolves and prepares validation errors in such
     * a way that they are easier to use client-side.
     */
    public function resolveValidationErrors(Request $request): object
    {
        if (!$request->hasSession()) {
            return (object) [];
        }

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
