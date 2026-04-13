<?php

namespace App\KitchenSink\Navigation\Lifecycle;

use Carbon\CarbonImmutable;
use Discovery\Routing\Get;
use Discovery\Routing\Post;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;
use Hybridly\Hybridly;
use Hybridly\HybridResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Exceptions;
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

    #[Get('/http500', name: 'http500')]
    public function http500(Request $request, Hybridly $hybridly): never
    {
        Exceptions::fake();

        if (! $request->boolean('modal', default: true)) {
            $hybridly->renderExceptionsInDevelopment();
        }

        throw new RuntimeException('This is an exception thrown from the server.');
    }
}
