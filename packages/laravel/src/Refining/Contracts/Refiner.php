<?php

namespace Hybridly\Refining\Contracts;

use Hybridly\Refining\Refine;
use Illuminate\Contracts\Database\Eloquent\Builder;

interface Refiner
{
    public function refine(Refine $refiner, Builder $builder): void;
}
