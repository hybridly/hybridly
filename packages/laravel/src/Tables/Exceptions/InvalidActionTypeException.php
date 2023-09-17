<?php

namespace Hybridly\Tables\Exceptions;

use Exception;

class InvalidActionTypeException extends Exception
{
    public static function with(string $type): self
    {
        return new self("Invalid action type [{$type}].");
    }
}
