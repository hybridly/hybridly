<?php

namespace Hybridly\Exceptions;

use Exception;

final class MustBeHybridException extends Exception
{
    public static function make(): static
    {
        return new static('Frame requests must be hybrid.');
    }
}
