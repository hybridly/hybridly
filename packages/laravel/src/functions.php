<?php

namespace Hybridly;

use Closure;
use Hybridly\Deferred;
use Hybridly\HybridResponseFactory;
use Hybridly\Merge;
use Hybridly\OnDemand;
use Hybridly\Support\Header;
use Hybridly\Support\Target;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

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

/**
 * Returns a hybrid view.
 *
 * @see https://hybridly.dev/api/laravel/functions.html#view
 */
function view(string $component, iterable $properties = []): HybridResponseFactory
{
    return resolve(HybridResponseFactory::class)->withView($component, $properties);
}

/**
 * Returns a dialog with the given properties and base view.
 *
 * Setting `redirectToBase` to `true` will always force a redirect to the base view when rendering the dialog instead of opening it in the current page.
 * Setting `preserveCurrentBase` to `true` will prevent returning an updated base view when rendering the dialog from.
 *
 * @see https://hybridly.dev/api/laravel/functions.html#dialog
 */
function dialog(string $component, iterable $properties, string $baseUrl, bool $alwaysRedirectToBase = false, bool $preserveBaseOnClose = false): HybridResponseFactory
{
    return resolve(HybridResponseFactory::class)
        ->withView($component, $properties)
        ->configureDialog($baseUrl, $alwaysRedirectToBase, $preserveBaseOnClose);
}

/**
 * Updates the properties for an existing view.
 *
 * @see https://hybridly.dev/api/laravel/functions.html#properties
 */
function properties(iterable $properties): HybridResponseFactory
{
    return resolve(HybridResponseFactory::class)->withProperties($properties);
}

/**
 * Creates a property that is only evaluated when specified in a partial reload.
 *
 * @see https://hybridly.dev/api/laravel/functions.html#partial
 */
function on_demand(Closure $callback): OnDemand
{
    return new OnDemand($callback);
}

/**
 * Specifies that a property should merge itself with its current instance.
 *
 * @see https://hybridly.dev/api/laravel/functions.html#merge
 */
function merge(Closure|iterable $value, bool $prepend = false, ?string $uniqueBy = null, string|array $path = []): Merge
{
    return new Merge($value, $prepend, $uniqueBy, $path);
}

/**
 * Creates a deferred property that will not be included in an initial load,
 * but will automatically be loaded in a subsequent partial reload.
 *
 * @see https://hybridly.dev/api/laravel/functions.html#deferred
 */
function deferred(Closure $callback, ?string $group = null): Deferred
{
    return new Deferred(
        callback: $callback,
        group: $group,
    );
}

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
