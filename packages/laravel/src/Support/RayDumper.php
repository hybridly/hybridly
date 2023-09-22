<?php

namespace Hybridly\Support;

use Illuminate\Contracts\Events\Dispatcher;
use Spatie\LaravelRay\Ray;

final class RayDumper
{
    private bool $showHybridRequests = false;

    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly Ray $ray,
    ) {
        $this->registerListener();
    }

    public function showHybridRequests(bool $show = true): void
    {
        $this->showHybridRequests = $show;
    }

    public function stopShowingHybridRequests(): void
    {
        $this->showHybridRequests = false;
    }

    private function registerListener(): void
    {
        $this->dispatcher->listen('hybridly.response', function (array $response) {
            if (!$this->showHybridRequests) {
                return;
            }

            $this->ray->table([
                'Payload' => $response['payload'],
                'Request' => $response['request'],
                'Version' => $response['version'],
                'Root view' => $response['root_view'],
            ], 'Hybrid response');
        });
    }
}
