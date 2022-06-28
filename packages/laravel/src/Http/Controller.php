<?php

namespace Monolikit\Http;

use Illuminate\Http\Request;
use Monolikit\View\Factory;

class Controller
{
    public function __invoke(Request $request): Factory
    {
        return monolikitly(
            $request->route()->defaults['component'],
            $request->route()->defaults['props'],
        );
    }
}
