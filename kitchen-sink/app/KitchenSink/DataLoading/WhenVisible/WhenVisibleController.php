<?php

namespace App\KitchenSink\DataLoading\WhenVisible;

use Carbon\CarbonImmutable;
use Discovery\Routing\Get;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;

use function Hybridly\on_demand;
use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/data-loading/when-visible', name: 'kitchen-sink.data-loading.when-visible')]
final class WhenVisibleController
{
    #[Get('/', name: 'index')]
    public function __invoke()
    {
        return view('kitchen-sink::data-loading.when-visible.index', [
            'receivedAt' => on_demand(function () {
                sleep(2);

                return CarbonImmutable::now()->toTimeString();
            }),
        ]);
    }
}
