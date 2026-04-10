<?php

namespace App\KitchenSink\Navigation\Lifecycle;

use Carbon\CarbonImmutable;
use Discovery\Routing\Get;
use Discovery\Routing\Post;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;
use Hybridly\Contracts\HybridResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/navigation/lifecycle', name: 'kitchen-sink.navigation.lifecycle')]
final class LifecycleController
{
    #[Get('/', name: 'index')]
    public function __invoke(): HybridResponse
    {
        return view('kitchen-sink::navigation.lifecycle.index', [
            'time' => CarbonImmutable::now(),
        ]);
    }

    #[Get('/success', name: 'success')]
    #[Post('/success', name: 'success')]
    public function success(): RedirectResponse
    {
        return back();
    }

    #[Get('/exception', name: 'exception')]
    public function exception(): never
    {
        throw new RuntimeException('This is an exception thrown from the server.');
    }
}
