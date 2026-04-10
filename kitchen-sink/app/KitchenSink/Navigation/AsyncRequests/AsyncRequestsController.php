<?php

namespace App\KitchenSink\Navigation\AsyncRequests;

use Carbon\CarbonImmutable;
use Discovery\Routing\Get;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;
use Hybridly\Contracts\HybridResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/navigation/async-requests', name: 'kitchen-sink.navigation.async-requests')]
final class AsyncRequestsController
{
    #[Get('/', name: 'index')]
    public function __invoke(): HybridResponse
    {
        return view('kitchen-sink::navigation.async-requests.index', [
            'time' => CarbonImmutable::now(),
        ]);
    }

    #[Get('/delay', name: 'delay')]
    public function delay(Request $request): RedirectResponse
    {
        sleep($request->integer('delay', default: 2));

        return back();
    }
}
