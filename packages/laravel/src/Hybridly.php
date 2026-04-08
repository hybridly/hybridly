<?php

namespace Hybridly;

use Closure;
use Hybridly\HybridExceptionHandler;
use Hybridly\Support\Properties\Deferred;
use Hybridly\Support\Properties\OnDemand;
use Hybridly\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Conditionable;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use function Hybridly\deferred;
use function Hybridly\is_hybrid;
use function Hybridly\is_partial;
use function Hybridly\properties;
use function Hybridly\to_external_url;
use function Hybridly\view;

final class Hybridly
{
    use Concerns\HasPersistentProperties;
    use Concerns\HasSharedProperties;
    use Concerns\HasVersion;
    use Concerns\ResolvesUrls;
    use Conditionable;

    public function __construct(
        private readonly HybridExceptionHandler $handler,
    ) {}

    /**
     * Ensures exceptions are rendered using Hybridly even in development.
     */
    public function renderExceptionsInDevelopment(): self
    {
        $this->handler->register();
        $this->handler->renderExceptionsInDevelopment();

        return $this;
    }

    /**
     * Defines the response that will be rendered when an exception occurs.
     */
    public function renderExceptionsUsing(Closure $closure): self
    {
        $this->handler->register();
        $this->handler->renderExceptionsUsing($closure);

        return $this;
    }

    /**
     * Defines the response that will be rendered when the session is expired.
     */
    public function handleSessionExpirationUsing(Closure $closure): self
    {
        $this->handler->register();
        $this->handler->handleSessionExpirationUsing($closure);

        return $this;
    }

    /**
     * Returns a hybrid view.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#view
     */
    public function view(?string $component = null, iterable $properties = []): Factory
    {
        return view($component, $properties);
    }

    /**
     * Returns updated properties for the current view.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#properties
     */
    public function properties(iterable $properties): Factory
    {
        return properties($properties);
    }

    /**
     * Generates a response for redirecting to an external website, or a non-hybrid view.
     * This can also be used to redirect to a hybrid view when it is not known whether the current request is hybrid or not.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#external
     * @deprecated Use `createExternalRedirectc`
     */
    public function external(string|RedirectResponse $url, array $headers = []): Response
    {
        return to_external_url($url, $headers);
    }

    /**
     * Generates a response for redirecting to an external website, or a non-hybrid view.
     * This can also be used to redirect to a hybrid view when it is not known whether the current request is hybrid or not.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#external
     */
    public function createExternalRedirect(string|RedirectResponse $url, array $headers = []): Response
    {
        return to_external_url($url, $headers);
    }

    /**
     * Creates a property that will only get evaluated and included when specifically requested through a partial reload.
     * Partial properties are not included during the first load.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#partial
     */
    public function onDemand(Closure $callback): OnDemand
    {
        return on_demand($callback);
    }

    /**
     * Creates a deferred property that will not be included in an initial load,
     * but will automatically be loaded in a subsequent partial reload.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#deferred
     */
    public function deferred(Closure $callback): Deferred
    {
        return deferred($callback);
    }

    /**
     * Checks if the given request is hybrid.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#is-hybrid
     */
    public function isHybrid(?Request $request = null): bool
    {
        return is_hybrid($request);
    }

    /**
     * Checks if the given request is a partial hybrid request.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#is-partial
     */
    public function isPartial(?Request $request = null): bool
    {
        return is_partial($request);
    }
}
