<?php

namespace Hybridly\Resources\Http;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ServeResourcesMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $routeName = $request->route()->getName();

        $resourceKeys = Cache::get('hybridly.resources.routes.' . $routeName);
        if($resourceKeys) {
            $hybridly = hybridly();
            $resources = [];

            foreach ($resourceKeys as $resourceKey) {
                if(!$hybridly->hasResource($resourceKey)) {
                    continue;
                }

                $resources[$resourceKey] = $hybridly->getResource($resourceKey);
            }

            $hybridly->share('resources', $resources);
        }

        return $next($request);
    }
}
