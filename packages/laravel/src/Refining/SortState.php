<?php

declare(strict_types=1);

namespace Hybridly\Refining;

use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;

/** Represents one ordered sort within a refinement state. */
final readonly class SortState implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $name,
        public string $direction,
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('Sort state names cannot be empty.');
        }

        if (! \in_array($direction, ['asc', 'desc'], strict: true)) {
            throw new InvalidArgumentException("Invalid sort direction [{$direction}].");
        }
    }

    public static function fromArray(array $state): self
    {
        $name = data_get(target: $state, key: 'name');
        $direction = data_get(target: $state, key: 'direction');

        if (! \is_string($name) || ! \is_string($direction)) {
            throw new InvalidArgumentException('Sort state names and directions must be strings.');
        }

        return new self(
            name: $name,
            direction: $direction,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'direction' => $this->direction,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
