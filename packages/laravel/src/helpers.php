<?php

use Illuminate\Http\Request;
use Monolikit\Monolikit;
use Monolikit\View\Factory;

if (!function_exists('is_monolikit')) {
    /**
     * Checks if the given response is monolikit.
     */
    function is_monolikit(Request $request): bool
    {
        return !!$request->header(Monolikit::MONOLIKIT_HEADER);
    }
}

if (!function_exists('monolikit')) {
    /**
     * Gets the monolikit instance or returns a view.
     */
    function monolikit(string $component = null, array $properties = []): Monolikit|Factory
    {
        /** @var Monolikit */
        $monolikit = resolve(Monolikit::class);

        if (!is_null($component)) {
            return $monolikit->view($component, $properties);
        }

        return $monolikit;
    }
}

if (!function_exists('monolikitly')) {
    /**
     * Creates a monolikit view.
     */
    function monolikitly(string $component = null, array $properties = []): Factory
    {
        return monolikit()->view($component, $properties);
    }
}
