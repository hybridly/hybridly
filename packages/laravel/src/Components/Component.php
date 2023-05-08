<?php

namespace Hybridly\Components;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;

abstract class Component implements \JsonSerializable
{
    use Concerns\Configurable;
    use Concerns\EvaluatesClosures;
    use Conditionable;
    use Macroable;
    use Tappable;
}
