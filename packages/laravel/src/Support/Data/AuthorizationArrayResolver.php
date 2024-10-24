<?php

namespace Hybridly\Support\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

final class AuthorizationArrayResolver
{
    public function resolve(Model $model, string $dataClass): array
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
                        message: \sprintf(
                            'Could not allow action `%s` on model `%s`. %s',
                            $action,
                            \is_string($model) ? $model : $model::class,
                            $previous->getMessage(),
                        ),
                        previous: $previous,
                    );
                }
            })
            ->toArray();
    }
}
