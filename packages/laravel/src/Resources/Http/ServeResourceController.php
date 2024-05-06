<?php

namespace Hybridly\Resources\Http;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ServeResourceController
{
    /**
     * @throws Exception
     */
    public function __invoke(Request $request)
    {
        $payload = $request->json();
        $resources = [];

        $keys = $payload->all('keys');

        foreach ($keys as $key) {
            $resources[$key] = hybridly()->getResource($key);
        }

        if ($route = $payload->get('route')) {
            Cache::set('hybridly.resources.routes.' . $route, $keys, 3600);
        }

        return json_encode($resources);
    }
}
