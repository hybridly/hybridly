<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Hybridly\Refining\Contracts\Filter as FilterContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

class SimilarityFilter implements FilterContract
{
    use SupportsRelationConstraints;

    public const LOOSE = 'loose';
    public const BEGINS_WITH_STRICT = 'begins_with_strict';
    public const ENDS_WITH_STRICT = 'ends_with_strict';

    private function __construct(protected string $mode)
    {
        if (!\in_array($mode, [self::LOOSE, self::BEGINS_WITH_STRICT, self::ENDS_WITH_STRICT], true)) {
            throw new \InvalidArgumentException("Invalid similarity mode [{$mode}] provided.");
        }
    }

    public function __invoke(Builder $builder, mixed $value, string $property): void
    {
        $this->applyRelationConstraint(
            builder: $builder,
            property: $property,
            callback: function (Builder $builder, string $column) use ($value) {
                $sql = match ($this->mode) {
                    self::LOOSE => "LOWER({$column}) LIKE ?",
                    self::BEGINS_WITH_STRICT => "{$column} LIKE ?",
                    self::ENDS_WITH_STRICT => "{$column} LIKE ?",
                };

                $bindings = match ($this->mode) {
                    self::LOOSE => ['%' . mb_strtolower($value, 'UTF8') . '%'],
                    self::BEGINS_WITH_STRICT => ["{$value}%"],
                    self::ENDS_WITH_STRICT => ["%{$value}"],
                };

                $builder->whereRaw(
                    sql: $sql,
                    bindings: $bindings,
                );
            },
        );
    }

    public function getType(): string
    {
        return "similar:{$this->mode}";
    }

    /**
     * Creates a filter that uses an exact match to filter records.
     */
    public static function make(string $property, ?string $alias = null, string $mode = self::LOOSE): Filter
    {
        return new Filter(
            filter: new static($mode),
            property: $property,
            alias: $alias,
        );
    }
}
