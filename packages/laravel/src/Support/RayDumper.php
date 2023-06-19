<?php

namespace Hybridly\Support;

use Illuminate\Contracts\Events\Dispatcher;

final class RayDumper
{
    private bool $showHybridRequests = false;

    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {
        $this->registerListener();
    }

    private function registerListener(): void
    {
        $this->dispatcher->listen('hybridly.response', function (array $response) {
            if (!$this->showHybridRequests) {
                return;
            }

            ray()->table([
                'Payload' => $response['payload'],
                'Request' => $response['request'],
                'Version' => $response['version'],
                'Root view' => $response['root_view'],
            ], 'Hybrid response');
        });
    }

    public function showHybridRequests(bool $show = true): void
    {
        $this->showHybridRequests = $show;
    }

    public function stopShowingHybridRequests(): void
    {
        $this->showHybridRequests = false;
    }
}
