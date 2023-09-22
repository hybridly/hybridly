<?php

use Hybridly\Hybridly;
use Hybridly\View\Factory;
use Illuminate\Contracts\Support\Arrayable;
use Spatie\LaravelData\Contracts\DataObject;

use function Hybridly\view;

if (!function_exists('hybridly')) {
    /**
     * Gets the hybridly instance or returns a view.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#hybridly
     *
     * @phpstan-return ($component is string ? \Hybridly\View\Factory : \Hybridly\Hybridly)
     */
    function hybridly(string $component = null, array|Arrayable|DataObject $properties = []): Hybridly|Factory
    {
        if (!is_null($component) || !empty($properties)) {
            return view($component, $properties);
        }

        return resolve(Hybridly::class);
    }
}
