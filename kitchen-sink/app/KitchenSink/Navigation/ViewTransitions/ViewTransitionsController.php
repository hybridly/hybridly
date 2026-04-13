<?php

namespace App\KitchenSink\Navigation\ViewTransitions;

use Discovery\Routing\Get;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;
use Hybridly\HybridResponse;

use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/navigation/view-transitions', name: 'kitchen-sink.navigation.view-transitions')]
final class ViewTransitionsController
{
    #[Get('/', name: 'index')]
    public function index(): HybridResponse
    {
        return view('kitchen-sink::navigation.view-transitions.index');
    }

    #[Get('/left', name: 'left')]
    public function left(): HybridResponse
    {
        return view('kitchen-sink::navigation.view-transitions.left');
    }

    #[Get('/right', name: 'right')]
    public function right(): HybridResponse
    {
        return view('kitchen-sink::navigation.view-transitions.right');
    }
}
