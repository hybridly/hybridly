<?php

namespace App\KitchenSink\Navigation\PreserveScroll;

use Carbon\CarbonImmutable;
use Discovery\Routing\Get;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;
use Hybridly\Contracts\HybridResponse;

use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/navigation/preserve-scroll', name: 'kitchen-sink.navigation.preserve-scroll')]
final class PreserveScrollController
{
    #[Get('/', name: 'index')]
    public function __invoke(): HybridResponse
    {
        return view('kitchen-sink::navigation.preserve-scroll.index', [
            'time' => CarbonImmutable::now(),
        ]);
    }
}
