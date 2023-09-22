<?php

namespace Hybridly\Tables\Actions;

use Hybridly\Components\Component;
use Hybridly\Components\Concerns;
use Hybridly\Tables\Actions;

abstract class BaseAction extends Component
{
    use Actions\Concerns\HasActionCallback;
    use Concerns\HasLabel;
    use Concerns\HasMetadata;
    use Concerns\HasName;
    use Concerns\IsHideable;

    final public function __construct(string $name)
    {
        $this->name($name);
        $this->label(str($name)->headline()->lower()->ucfirst());
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
            'label' => $this->getLabel(),
            'metadata' => $this->getMetadata(),
        ];
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'action' => [$this],
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
