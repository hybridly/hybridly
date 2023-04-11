<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Contracts\Filter as FilterContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class SimilarityFilter implements FilterContract
{
    public const LOOSE = 'loose';
    public const BEGINS_WITH_STRICT = 'begins_with_strict';
    public const ENDS_WITH_STRICT = 'ends_with_strict';

    public function getType(): string
    {
        return "similar:{$this->mode}";
    }

    private function __construct(protected string $mode)
    {
        if (!\in_array($mode, [self::LOOSE, self::BEGINS_WITH_STRICT, self::ENDS_WITH_STRICT], true)) {
            throw new \InvalidArgumentException("Invalid similarity mode [{$mode}] provided.");
        }
    }

    public function __invoke(Builder $builder, mixed $value, string $property): void
    {
        $sql = match ($this->mode) {
            self::LOOSE => "LOWER({$property}) LIKE ?",
            self::BEGINS_WITH_STRICT => "{$property} LIKE ?",
            self::ENDS_WITH_STRICT => "{$property} LIKE ?",
        };

        $bindings = match ($this->mode) {
            self::LOOSE => ["%" . mb_strtolower($value, 'UTF8') . "%"],
            self::BEGINS_WITH_STRICT => ["{$value}%"],
            self::ENDS_WITH_STRICT => ["%{$value}"],
        };

        // TODO: support dot-notated relationships
        $builder->whereRaw(
            sql: $sql,
            bindings: $bindings,
        );
    }

    /**
     * Creates a filter that uses an exact match to filter records.
     */
    public static function make(string $property, array $aliases = [], string $mode = self::LOOSE): Filter
    {
        return new Filter(
            filter: new static($mode),
            property: $property,
            aliases: $aliases,
        );
    }
}
