<?php

use Hybridly\Hybridly;
use Hybridly\View\Factory;
use Illuminate\Http\Request;

if (!function_exists('is_hybrid')) {
    /**
     * Checks if the given response uses hybridly.
     */
    function is_hybrid(Request $request = null): bool
    {
        return hybridly()->isHybridly($request);
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
