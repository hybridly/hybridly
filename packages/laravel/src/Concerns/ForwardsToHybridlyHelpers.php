<?php

namespace Hybridly\Concerns;

use Hybridly\Support\Deferred;
use Hybridly\Support\Partial;
use Hybridly\View\Factory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Spatie\LaravelData\Contracts\DataObject;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use function Hybridly\deferred;
use function Hybridly\is_hybrid;
use function Hybridly\is_partial;
use function Hybridly\partial;
use function Hybridly\properties;
use function Hybridly\to_external_url;
use function Hybridly\view;

trait ForwardsToHybridlyHelpers
{
    /**
     * Returns a hybrid view.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#view
     */
    public function view(string $component = null, array|Arrayable|DataObject $properties = []): Factory
    {
        return view($component, $properties);
    }

    /**
     * Returns updated properties for the current view.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#properties
     */
    public function properties(array|Arrayable|DataObject $properties): Factory
    {
        return properties($properties);
    }

    /**
     * Generates a response for redirecting to an external website, or a non-hybrid page.
     * This can also be used to redirect to a hybrid page when it is not known whether the current request is hybrid or not.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#external
     */
    public function external(string|RedirectResponse $url): Response
    {
        return to_external_url($url);
    }

    /**
     * Creates a property that will only get evaluated and included when specifically requested through a partial reload.
     * Partial properties are not included during the first load.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#partial
     */
    public function partial(\Closure $callback): Partial
    {
        return partial($callback);
    }

    /**
     * Creates a deferred property that will not be included in an initial load,
     * but will automatically be loaded in a subsequent partial reload.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#deferred
     */
    public function deferred(\Closure $callback): Deferred
    {
        return deferred($callback);
    }

    /**
     * Checks if the given request is hybrid.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#is-hybrid
     */
    public function isHybrid(Request $request = null): bool
    {
        return is_hybrid($request);
    }

    /**
     * Checks if the given request is a partial hybrid request.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#is-partial
     */
    public function isPartial(Request $request = null): bool
    {
        return is_partial($request);
    }
}
