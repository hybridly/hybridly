<?php

namespace Hybridly\Testing;

use Hybridly\Support\Header;

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
    function partial_headers(string $component, ?array $only = null, ?array $except = null, bool $hybrid = true): array
    {
        return array_filter([
            Header::HYBRID_REQUEST => $hybrid,
            Header::PARTIAL_COMPONENT => $component,
            Header::PARTIAL_ONLY => !\is_null($only) ? json_encode($only) : null,
            Header::PARTIAL_EXCEPT => !\is_null($except) ? json_encode($except) : null,
        ]);
    }
}
