<?php

namespace Hybridly\Exceptions;

use Exception;

final class MissingViewComponentException extends Exception implements HybridlyException
{
    public static function make(): static
    {
        return new static('The view component is missing from the response but is required for initial page loads.');
    }
}
