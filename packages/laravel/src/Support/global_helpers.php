<?php

use Hybridly\Hybridly;

if (! function_exists('hybridly')) {
    /**
     * Gets the hybridly instance.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#hybridly
     */
    function hybridly(): Hybridly
    {
        return resolve(Hybridly::class);
    }
}
