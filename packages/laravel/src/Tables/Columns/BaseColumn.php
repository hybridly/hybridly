<?php

namespace Hybridly\Tables\Columns;

use Hybridly\Components\Component;
use Hybridly\Components\Concerns\HasLabel;
use Hybridly\Components\Concerns\HasMetadata;
use Hybridly\Components\Concerns\HasName;
use Hybridly\Components\Concerns\IsHideable;
use Hybridly\Components\Contracts\Hideable;
use Hybridly\Refining\Filters\Concerns\HasType;
use Hybridly\Tables\Columns\Concerns\CanTransformValue;

abstract class BaseColumn extends Component implements Hideable
{
    use CanTransformValue;
    use HasLabel;
    use HasMetadata;
    use HasName;
    use HasType;
    use IsHideable;

    final public function __construct(string $name)
    {
        $this->name($name);
        $this->label(str($name)->afterLast('.')->headline()->lower()->ucfirst());
        $this->type('custom');
        $this->setUp();
    }

    public static function make(string $name): static
    {
        $static = resolve(static::class, ['name' => $name]);

        return $static;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->getName(),
            'type' => $this->getType(),
            'label' => $this->getLabel(),
            'metadata' => $this->getMetadata(),
        ];
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'column' => [$this],
            default => []
        };
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) {
            static::class => [$this],
            default => []
        };
    }
}
