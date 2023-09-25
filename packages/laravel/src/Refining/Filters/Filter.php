<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Illuminate\Contracts\Database\Eloquent\Builder;

class Filter extends BaseFilter
{
    use SupportsRelationConstraints;

    public const EXACT = 'exact';
    public const LOOSE = 'loose';
    public const BEGINS_WITH_STRICT = 'begins_with_strict';
    public const ENDS_WITH_STRICT = 'ends_with_strict';

    protected string|\Closure $mode = self::EXACT;
    protected null|string|\Closure $enum = null;
    protected string|\Closure $operator = '=';

    protected function setUp(): void
    {
        $this->type(function () {
            if ($this->getMode() === self::EXACT) {
                return 'exact';
            }

            return "similar:{$this->getMode()}";
        });
    }

    public static function make(string $property, ?string $alias = null): static
    {
        $static = resolve(static::class, [
            'property' => $property,
            'alias' => $alias,
        ]);

        return $static->configure();
    }

    public function apply(Builder $builder, mixed $value, string $property): void
    {
        if (($enumClass = $this->getEnumClass()) && !$value instanceof \BackedEnum) {
            $value = $enumClass::tryFrom($value);
        }

        $this->applyRelationConstraint(
            builder: $builder,
            property: $property,
            callback: function (Builder $builder, string $column) use ($value) {
                if ($this->getMode() === self::EXACT) {
                    return $builder->where(
                        column: $builder->qualifyColumn($column),
                        operator: '=',
                        value: $value,
                    );
                }

                $sql = match ($this->getMode()) {
                    self::LOOSE => "LOWER({$column}) LIKE ?",
                    self::BEGINS_WITH_STRICT => "{$column} LIKE ?",
                    self::ENDS_WITH_STRICT => "{$column} LIKE ?",
                };

                $bindings = match ($this->getMode()) {
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

    public function operator(string|\Closure $operator): static
    {
        $this->operator = $operator;

        return $this;
    }

    public function mode(string|\Closure $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function enum(string $enum): static
    {
        $this->enum = $enum;

        return $this;
    }

    public function exact(): static
    {
        $this->mode = static::EXACT;

        return $this;
    }

    public function loose(): static
    {
        $this->mode = static::LOOSE;

        return $this;
    }

    public function beingsWithStrict(): static
    {
        $this->mode = static::BEGINS_WITH_STRICT;

        return $this;
    }

    public function endsWithStrict(): static
    {
        $this->mode = static::ENDS_WITH_STRICT;

        return $this;
    }

    public function getEnumClass(): ?string
    {
        return $this->evaluate($this->enum);
    }

    public function getOperator(): string
    {
        return $this->evaluate($this->operator);
    }

    public function getMode(): string
    {
        $mode = $this->evaluate($this->mode);

        if (!\in_array($mode, [self::LOOSE, self::BEGINS_WITH_STRICT, self::ENDS_WITH_STRICT, self::EXACT], true)) {
            throw new \InvalidArgumentException("Invalid similarity mode [{$mode}] provided.");
        }

        return $mode;
    }
}
