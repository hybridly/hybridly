<?php

use Illuminate\Http\Request;
use Hybridly\Hybridly;
use Hybridly\View\Factory;

if (!function_exists('is_hybridly')) {
    /**
     * Checks if the given response is hybridly.
     */
    function is_hybridly(Request $request): bool
    {
        return !!$request->header(Hybridly::HYBRIDLY_HEADER);
    }
}

if (!function_exists('hybridly')) {
    /**
     * Gets the hybridly instance or returns a view.
     */
    function hybridly(string $component = null, array $properties = []): Hybridly|Factory
    {
        /** @var Hybridly */
        $hybridly = resolve(Hybridly::class);

        if (!is_null($component)) {
            return $hybridly->view($component, $properties);
        }

        return $hybridly;
    }
}

if (!function_exists('hybridlyly')) {
    /**
     * Creates a hybridly view.
     */
    function hybridlyly(string $component = null, array $properties = []): Factory
    {
        return hybridly()->view($component, $properties);
    }
}
