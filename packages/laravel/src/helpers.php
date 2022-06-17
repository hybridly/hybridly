<?php

use Illuminate\Http\Request;
use Sleightful\Sleightful;
use Sleightful\View\Factory;

if (!function_exists('is_sleightful')) {
    /**
     * Checks if the given response is sleightful.
     */
    function is_sleightful(Request $request): bool
    {
        return !!$request->header(Sleightful::SLEIGHTFUL_HEADER);
    }
}

if (!function_exists('sleightful')) {
    /**
     * Gets the sleightful instance or returns a view.
     */
    function sleightful(string $component = null, array $properties = []): Sleightful|Factory
    {
        /** @var Sleightful */
        $sleightful = resolve(Sleightful::class);

        if (!is_null($component)) {
            return $sleightful->view($component, $properties);
        }

        return $sleightful;
    }
}

if (!function_exists('sleightfully')) {
    /**
     * Creates a sleightful view.
     */
    function sleightfully(string $component = null, array $properties = []): Factory
    {
        return sleightful()->view($component, $properties);
    }
}
