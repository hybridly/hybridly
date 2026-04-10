<?php

namespace App\KitchenSink\DataLoading\Deferred;

use Carbon\CarbonImmutable;
use Discovery\Routing\Get;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;

use function Hybridly\deferred;
use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/data-loading/deferred', name: 'kitchen-sink.data-loading.deferred')]
final class DeferredPropertiesController
{
    #[Get('/', name: 'index')]
    public function __invoke()
    {
        $requested_at = CarbonImmutable::now();

        return view('kitchen-sink::data-loading.deferred.index', [
            'instant' => [
                'This was loaded with the page',
                sprintf('Requested at %s', $requested_at->toTimeString()),
                sprintf('Sent at %s', CarbonImmutable::now()->toTimeString()),
            ],
            'deferred' => deferred(function () use ($requested_at) {
                sleep(2);

                return [
                    'This property is artificially delayed by 2 seconds.',
                    sprintf('Requested at %s', $requested_at->toTimeString()),
                    sprintf('Sent at %s', CarbonImmutable::now()->toTimeString()),
                ];
            }),
            'grouped' => deferred(function () use ($requested_at) {
                sleep(4);

                return [
                    'This property is artificially delayed by 4 seconds.',
                    sprintf('Requested at %s', $requested_at->toTimeString()),
                    sprintf('Sent at %s', CarbonImmutable::now()->toTimeString()),
                ];
            }, group: 'named'),
        ]);
    }
}
