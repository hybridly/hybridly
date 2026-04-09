<?php

namespace Hybridly;

use Closure;
use Hybridly\Support\Header;
use Hybridly\Support\Properties\Deferred;
use Hybridly\Support\Properties\Merge;
use Hybridly\Support\Properties\OnDemand;
use Hybridly\Support\Target;
use Hybridly\View\Factory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

if (! \function_exists('Hybridly\is_hybrid')) {
    /**
     * Checks if the given request is hybrid.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#is-hybrid
     */
    function is_hybrid(?Request $request = null): bool
    {
        $request ??= request();

        return $request->headers->has(Header::HYBRID_REQUEST);
    }
}

if (! \function_exists('Hybridly\is_partial')) {
    /**
     * Checks if the given request is a partial hybrid request.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#is-partial
     */
    function is_partial(?Request $request = null): bool
    {
        $request ??= request();

        if (! is_hybrid($request)) {
            return false;
        }

        return $request->headers->has(Header::PARTIAL_COMPONENT);
    }
}

if (! \function_exists('Hybridly\view')) {
    /**
     * Returns a hybrid view.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#view
     */
    function view(string $component, iterable $properties = []): Factory
    {
        return resolve(Factory::class)->withView($component, $properties);
    }
}

if (! \function_exists('Hybridly\dialog')) {
    /**
     * Returns a dialog with the given properties and base view.
     *
     * Setting `redirectToBase` to `true` will always force a redirect to the base view when rendering the dialog instead of opening it in the current page.
     * Setting `preserveCurrentBase` to `true` will prevent returning an updated base view when rendering the dialog from.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#dialog
     */
    function dialog(string $component, iterable $properties, string $baseUrl, bool $alwaysRedirectToBase = false, bool $preserveBaseOnClose = false): Factory
    {
        return resolve(Factory::class)
            ->withView($component, $properties)
            ->configureDialog($baseUrl, $alwaysRedirectToBase, $preserveBaseOnClose);
    }
}

if (! \function_exists('Hybridly\properties')) {
    /**
     * Updates the properties for an existing view.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#properties
     */
    function properties(iterable $properties): Factory
    {
        return resolve(Factory::class)->withProperties($properties);
    }
}

if (! \function_exists('Hybridly\on_demand')) {
    /**
     * Creates a property that is only evaluated when specified in a partial reload.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#partial
     */
    function on_demand(Closure $callback): OnDemand
    {
        return new OnDemand($callback);
    }
}

if (! \function_exists('Hybridly\merge')) {
    /**
     * Specifies that a property should merge itself with its current instance.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#merge
     */
    function merge(Closure|iterable $value): Merge
    {
        return new Merge($value);
    }
}

if (! \function_exists('Hybridly\deferred')) {
    /**
     * Creates a deferred property that will not be included in an initial load,
     * but will automatically be loaded in a subsequent partial reload.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#deferred
     */
    function deferred(Closure $callback, ?string $group = null): Deferred
    {
        return new Deferred($callback, group: $group);
    }
}

if (! \function_exists('Hybridly\to_external_url')) {
    /**
     * Generates a response for redirecting to an external website, or a non-hybrid view.
     * This can also be used to redirect to a hybrid view when it is not known whether the current request is hybrid or not.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#to-external-url
     */
    function to_external_url(string|RedirectResponse $url, array $headers = [], Target $target = Target::CURRENT): Response
    {
        if ($url instanceof RedirectResponse) {
            $url = $url->getTargetUrl();
        }

        if (is_hybrid()) {
            return new Response(
                status: Response::HTTP_CONFLICT,
                headers: [...$headers, Header::EXTERNAL => $url, Header::EXTERNAL_TARGET => $target->value],
            );
        }

        return new RedirectResponse(
            url: $url,
            headers: $headers,
        );
    }
}
