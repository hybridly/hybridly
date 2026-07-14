<?php

namespace Hybridly\Refining\Filters;

enum Operator: string
{
    // General comparison
    case EQUALS = 'equals';
    case NOT_EQUALS = 'not_equals';
    case IN = 'in';
    case NOT_IN = 'not_in';
    case IS_NULL = 'is_null';
    case IS_NOT_NULL = 'is_not_null';

    // String operations
    case CONTAINS = 'contains';
    case NOT_CONTAINS = 'not_contains';
    case BEGINS_WITH = 'begins_with';
    case ENDS_WITH = 'ends_with';
    case IS_EMPTY = 'is_empty';
    case IS_NOT_EMPTY = 'is_not_empty';

    // Numeric operations
    case GREATER_THAN = 'greater_than';
    case GREATER_THAN_OR_EQUAL = 'greater_than_or_equal';
    case LESS_THAN = 'less_than';
    case LESS_THAN_OR_EQUAL = 'less_than_or_equal';
    case BETWEEN = 'between';
    case NOT_BETWEEN = 'not_between';

    // Date operations
    case AFTER = 'after';
    case BEFORE = 'before';
    case IN_THE_LAST = 'in_the_last';
    case NOT_IN_THE_LAST = 'not_in_the_last';

    public function isNullary(): bool
    {
        return match ($this) {
            self::IS_EMPTY, self::IS_NOT_EMPTY, self::IS_NULL, self::IS_NOT_NULL => true,
            default => false,
        };
    }
}
