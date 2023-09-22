<?php

namespace Hybridly\Tables\Exceptions;

use Exception;

final class TableModelNotFoundException extends Exception
{
    public static function invalid(string $table, string $class): self
    {
        return new self("Table [{$table}] has an invalid model class [{$class}]. Make sure to define the `\$model` property on the table class.");
    }

    public static function notModel(string $table, string $model): self
    {
        return new self("Table [{$table}] has a model class [{$model}] that is not a valid Eloquent model. Make sure to define the `\$model` property on the table class.");
    }
}
