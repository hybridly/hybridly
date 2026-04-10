<?php

namespace App\KitchenSink\DataLoading\Mergeable;

use Carbon\CarbonImmutable;
use Discovery\Routing\Get;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;
use Illuminate\Support\Str;

use function Hybridly\merge;
use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/data-loading/mergeable', name: 'kitchen-sink.data-loading.mergeable')]
final class MergeablePropertiesController
{
    #[Get('/', name: 'index')]
    public function __invoke()
    {
        return view('kitchen-sink::data-loading.mergeable.index', [
            'append' => merge([
                new Message(
                    id: Str::random(5),
                    content: fake()->sentence(),
                    side: fake()->randomElement(['left', 'right']),
                    sent_at: CarbonImmutable::now(),
                ),
            ]),
            'prepend' => merge([
                new LogEntry(
                    content: fake()->sentence(),
                    sent_at: CarbonImmutable::now(),
                ),
            ], prepend: true),
            'unique' => merge([
                new Message(
                    id: fake()->randomElement([1, 2, 3, 4, 5]),
                    content: fake()->sentence(),
                    side: fake()->randomElement(['left', 'right']),
                    sent_at: CarbonImmutable::now(),
                ),
            ], uniqueBy: 'id'),
        ]);
    }
}
