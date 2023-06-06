<?php

namespace Hybridly\ServerSideRendering;

use Illuminate\Config\Repository;
use Illuminate\Http\Client\Factory;

final class Gateway
{
    public function __construct(
        private readonly Repository $config,
        private readonly Factory $http,
    ) {
    }

    public function dispatch(array $payload): ?string
    {
        ray($payload);
        // if (!$this->config->get('hybridly.ssr.enabled', false)) {
        //     return null;
        // }

        $url = $this->config->get('hybridly.ssr.url', 'http://127.0.0.1:13714') . '/render';

        try {
            $response = $this->http->post($url, $payload)->throw()->json();
        } catch (\Throwable $e) {
            return null;
        }

        return $response;
    }
}
