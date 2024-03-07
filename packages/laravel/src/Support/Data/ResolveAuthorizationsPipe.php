<?php

namespace Hybridly\Support\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\DataPipes\DataPipe;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataClass;

final class ResolveAuthorizationsPipe implements DataPipe
{
    public function handle(mixed $payload, DataClass $class, array $properties, CreationContext $creationContext): array
    {
        if (!$payload instanceof Model) {
            return $properties;
        }

        if (!is_subclass_of($dataClass = $creationContext->dataClass, DataResourceContract::class)) {
            return $properties;
        }

        return [
            ...$properties,
            'authorization' => Lazy::create(fn () => $this->getAuthorizationArray($payload, $dataClass))->defaultIncluded(),
        ];
    }

    private function getAuthorizationArray(Model $model, string $dataClass): array
    {
        return collect($dataClass::getAuthorizations())
            ->mapWithKeys(function (array|string $value, int|string $key) use ($model) {
                $action = \is_int($key) ? $value : $key;
                $policy = \is_array($value) ? $value : [$value, $action];

                try {
                    if (!\is_int($key)) {
                        return [$action => Gate::allows($policy[1], [$policy[0], $model])];
                    }

                    return [$action => Gate::allows($action, $model)];
                } catch (\ArgumentCountError $previous) {
                    throw new \ArgumentCountError(
                        previous: $previous,
                        message: sprintf(
                            'Could not allow action `%s` on model `%s`. %s',
                            $action,
                            \is_string($model) ? $model : $model::class,
                            $previous->getMessage(),
                        ),
                    );
                }
            })
            ->toArray();
    }
}
