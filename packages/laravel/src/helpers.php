<?php

namespace Hybridly;

use Hybridly\Support\Partial;
use Illuminate\Http\Request;

if (!\function_exists('Hybridly\is_hybrid')) {
    /**
     * Checks if the given request is hybrid.
     */
    function is_hybrid(Request $request = null): bool
    {
        return hybridly()->isHybrid($request);
    }
}

if (!\function_exists('Hybridly\is_partial')) {
    /**
     * Checks if the given request is a partial hybrid request.
     */
    function is_partial(Request $request = null): bool
    {
        return hybridly()->isPartial($request);
    }
}
