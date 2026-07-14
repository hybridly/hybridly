<?php

declare(strict_types=1);

namespace Hybridly\Refining;

use Hybridly\Refining\Filters\Operator;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;

/** Represents a serializable filter value within a refinement state. */
final readonly class FilterState implements Arrayable, JsonSerializable
{
    public int|float|string|bool|array|null $value;
    public ?Operator $operator;

    public function __construct(
        mixed $value,
        Operator|string|null $operator = null,
        public array $options = [],
        public ?string $suggestionKey = null,
    ) {
        $this->value = self::validateValue($value);
        $this->operator = self::resolveOperator($operator);
    }

    public static function fromArray(array $state): self
    {
        $operator = data_get(target: $state, key: 'operator');
        $options = data_get(target: $state, key: 'options', default: []);
        $suggestionKey = data_get(target: $state, key: 'suggestion_key');

        if (! \is_string($operator) && $operator !== null) {
            throw new InvalidArgumentException('Filter state operators must be strings or null.');
        }

        if (! \is_array($options)) {
            throw new InvalidArgumentException('Filter state options must be arrays.');
        }

        if (! \is_string($suggestionKey) && $suggestionKey !== null) {
            throw new InvalidArgumentException('Filter state suggestion keys must be strings or null.');
        }

        return new self(
            value: data_get(target: $state, key: 'value'),
            operator: $operator,
            options: $options,
            suggestionKey: $suggestionKey,
        );
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'operator' => $this->operator?->value,
            'options' => $this->options,
            'suggestion_key' => $this->suggestionKey,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function resolveOperator(Operator|string|null $operator): ?Operator
    {
        if ($operator === null || $operator instanceof Operator) {
            return $operator;
        }

        return Operator::tryFrom($operator) ?? throw new InvalidArgumentException("Invalid filter operator [{$operator}].");
    }

    private static function validateValue(mixed $value): int|float|string|bool|array|null
    {
        if (\is_float($value) && ! is_finite($value)) {
            throw new InvalidArgumentException('Filter state values must be JSON-safe.');
        }

        if ($value === null || \is_scalar($value)) {
            return $value;
        }

        if (! \is_array($value)) {
            throw new InvalidArgumentException('Filter state values must be JSON-safe.');
        }

        foreach ($value as $nestedValue) {
            self::validateValue($nestedValue);
        }

        return $value;
    }
}
