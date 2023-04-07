<?php

namespace Hybridly\Testing;

use Hybridly\Hybridly;

if (!\function_exists('Hybridly\Testing\partial_headers')) {
    /**
     * Generates headers for testing partial requests.
     *
     * @param string $component The name of the component for which the partial request is made.
     * @param array $only The dot-notation-enabled property names that should be included.
     * @param array $except The dot-notation-enabled property names that should be excluded.
     *
     * @see https://hybridly.dev/api/laravel/functions.html#partial-headers
     */
    function partial_headers(string $component, array $only = null, array $except = null): array
    {
        return array_filter([
            Hybridly::PARTIAL_COMPONENT_HEADER => $component,
            Hybridly::ONLY_DATA_HEADER => !\is_null($only) ? json_encode($only) : null,
            Hybridly::EXCEPT_DATA_HEADER => !\is_null($except) ? json_encode($except) : null,
        ]);
    }
}
