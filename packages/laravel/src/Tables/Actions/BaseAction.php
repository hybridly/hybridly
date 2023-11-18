<?php

namespace Hybridly\Tables\Actions;

use Hybridly\Components\Component;
use Hybridly\Components\Concerns\HasLabel;
use Hybridly\Components\Concerns\HasMetadata;
use Hybridly\Components\Concerns\HasName;
use Hybridly\Components\Concerns\IsHideable;
use Hybridly\Components\Contracts\Hideable;
use Hybridly\Tables\Actions\Concerns\HasActionCallback;

abstract class BaseAction extends Component implements Hideable
{
    use HasActionCallback;
    use HasLabel;
    use HasMetadata;
    use HasName;
    use IsHideable;

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
