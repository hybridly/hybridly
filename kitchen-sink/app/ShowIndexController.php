<?php

namespace App;

use Discovery\Routing\Get;
use Discovery\Routing\Web;
use Hybridly\HybridResponse;

use function Hybridly\view;

#[Web]
final class ShowIndexController
{
    #[Get('/', name: 'index')]
    public function __invoke(): HybridResponse
    {
        return view('index');
    }
}
