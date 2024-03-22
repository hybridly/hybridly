<?php

namespace Hybridly\Support\Data;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataPipeline;
use Spatie\LaravelData\Lazy;

abstract class DataResource extends Data implements DataResourceContract
{
    public Lazy|array $authorization;

    public static function pipeline(): DataPipeline
    {
        return parent::pipeline()->firstThrough(ResolveAuthorizationsPipe::class);
    }

    public static function getAuthorizations(): array
    {
        return static::$authorizations ?? [];
    }

    public function withoutAuthorizations(): static
    {
        return $this->excludePermanently('authorization');
    }

    protected static function resolveAuthorizationArray(Model $model): array
    {
        return resolve(AuthorizationArrayResolver::class)->resolve($model, static::class);
    }
}
