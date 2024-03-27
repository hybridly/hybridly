<?php

namespace Hybridly\Exceptions;

use Exception;

final class MissingViewComponentException extends Exception
{
    public static function make(): static
    {
        return new static('The view component is missing but is required for initial page loads.');
    }
}
