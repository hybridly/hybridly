<?php

namespace App\KitchenSink\Navigation\PreserveState;

use Carbon\CarbonImmutable;
use Discovery\Routing\Get;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;
use Hybridly\Contracts\HybridResponse;

use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/navigation/preserve-state', name: 'kitchen-sink.navigation.preserve-state')]
final class PreserveStateController
{
    #[Get('/', name: 'index')]
    public function __invoke(): HybridResponse
    {
        return view('kitchen-sink::navigation.preserve-state.index', [
            'time' => CarbonImmutable::now(),
        ]);
    }
}
