<?php

namespace Hybridly\Tables\Exceptions;

use Exception;
use Hybridly\Tables\Table;

class InvalidTableException extends Exception
{
    public static function with(string $table): self
    {
        return new self(sprintf("Table [{$table}] must extend the [%s] class.", Table::class));
    }

    public static function cannotBeAnonymous(): self
    {
        return new self('Anonymous tables do not support actions.');
    }
}
