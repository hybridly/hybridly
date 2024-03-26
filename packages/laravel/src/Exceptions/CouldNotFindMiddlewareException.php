<?php

namespace Hybridly\Exceptions;

use Exception;

final class CouldNotFindMiddlewareException extends Exception
{
    public static function create(): self
    {
        return new self('Could not find the Hybridly middleware in the application.');
    }
}
