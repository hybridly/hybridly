<?php

namespace Hybridly\Http;

use Illuminate\Http\Request;
use Hybridly\View\Factory;

class Controller
{
    public function __invoke(Request $request): Factory
    {
        return hybridlyly(
            $request->route()->defaults['component'],
            $request->route()->defaults['props'],
        );
    }
}
