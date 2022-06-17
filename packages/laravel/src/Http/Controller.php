<?php

namespace Sleightful\Http;

use Illuminate\Http\Request;
use Sleightful\View\Factory;

class Controller
{
    public function __invoke(Request $request): Factory
    {
        return sleightfully(
            $request->route()->defaults['component'],
            $request->route()->defaults['props'],
        );
    }
}
