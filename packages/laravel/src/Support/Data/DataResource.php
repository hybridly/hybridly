<?php

namespace Hybridly\Support\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Support\Wrapping\WrapExecutionType;

abstract class DataResource extends Data implements DataResourceContract
{
    use Concerns\CanAppendAuthorizations;
    use Concerns\HasModel;

    protected static string $collectionClass = DataResourceCollection::class;
    protected static string $paginatedCollectionClass = PaginatedDataResourceCollection::class;
    protected static string $cursorPaginatedCollectionClass = CursorPaginatedDataResourceCollection::class;

    public Lazy|array $authorization;

    /**
     * Authorizations to type for this data resource.
     * Can be:
     * - The method name for the associated policy (eg. `create`)
     * - `'create' => Post::class`
     * - `'create-post' => [Post::class, 'create']`
     */
    protected static array $authorizations = [];

    /**
     * Custom function to find the model class related to this data resouce.
     */
    protected static ?\Closure $modelClassResolver = null;

    /**
     * Gets authorization for this resource.
     */
    public function getAuthorizationArray(): array
    {
        if (!$this->appendsAuthorizations()) {
            return [];
        }

        if (!$model = $this->resolveModel()) {
            return [];
        }

        return collect(static::$authorizations)
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
                            "Could not find authorization for action `%s` on model `%s`. %s",
                            $action,
                            $model::class,
                            $previous->getMessage(),
                        ),
                    );
                }
            })
            ->toArray();
    }

    protected function resolveModel(): Model|string|null
    {
        $this->model ??= static::resolveModelClass();

        if (!$this->model instanceof Model && !class_exists($this->model)) {
            return null;
        }

        return $this->model;
    }

    protected static function resolveModelClass(): ?string
    {
        return static::$model ?? value(static::$modelClassResolver ?? function (): string|null {
            $modelClass = str(class_basename(static::class))
                ->replaceMatches('/Data$/', '')
                ->prepend('Models\\')
                ->prepend(app()->getNamespace());

            return class_exists($modelClass) ? $modelClass : null;
        }, static::class);
    }

    /**
     * Defines the callback that will resolve the model class if the data resource
     * is not instanciated using a model.
     */
    public static function resolveModelClassUsing(\Closure $resolver): void
    {
        static::$modelClassResolver = $resolver;
    }

    /*
    |--------------------------------------------------------------------------
    | Overrides
    |--------------------------------------------------------------------------
    */

    public static function from(mixed ...$payloads): static
    {
        /** @var DataResource */
        $data = parent::from(...$payloads);

        if (\count($payloads) === 1 && $payloads[0] instanceof Model) {
            $data->usingModel($payloads[0]);
        }

        return $data;
    }

    public static function withoutMagicalCreationFrom(mixed ...$payloads): static
    {
        /** @var DataResource */
        $data = parent::withoutMagicalCreationFrom(...$payloads);

        if (\count($payloads) === 1 && $payloads[0] instanceof Model) {
            $data->usingModel($payloads[0]);
        }

        return $data;
    }

    public function transform(bool $transformValues = true, WrapExecutionType $wrapExecutionType = WrapExecutionType::Disabled, bool $mapPropertyNames = true): array
    {
        $this->authorization = Lazy::create(fn () => $this->getAuthorizationArray())->defaultIncluded();

        return parent::transform($transformValues, $wrapExecutionType, $mapPropertyNames);
    }
}
