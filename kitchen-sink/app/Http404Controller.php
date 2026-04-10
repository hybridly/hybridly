<?php

namespace App;

use Discovery\Routing\Get;
use Discovery\Routing\Web;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class Http404Controller
{
    #[Web, Get('/400')]
    public function __invoke(): never
    {
        throw new NotFoundHttpException();
    }
}
