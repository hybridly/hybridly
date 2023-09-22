<?php

namespace Hybridly\Tables\Columns;

use Hybridly\Components\Component;
use Hybridly\Components\Concerns;
use Hybridly\Tables\Columns;

abstract class BaseColumn extends Component
{
    use Columns\Concerns\CanTransformValue;
    use Concerns\HasLabel;
    use Concerns\HasMetadata;
    use Concerns\HasName;
    use Concerns\HasType;
    use Concerns\IsHideable;

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
