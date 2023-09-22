<?php

namespace Hybridly\Tables\Concerns;

use Hybridly\Tables\Exceptions\TableModelNotFoundException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasModel
{
    protected static null|\Closure $getModelClassesUsing = null;

    public static function getModelClassesUsing(\Closure $callback): void
    {
        static::$getModelClassesUsing = $callback;
    }

    public function getModel(): Model
    {
        $model = $this->getModelClass();

        if (!class_exists($model)) {
            throw TableModelNotFoundException::invalid(static::class, $model);
        }

        if (!is_a($model, class: Model::class, allow_string: true)) {
            throw TableModelNotFoundException::notModel(static::class, $model);
        }

        return new $model();
    }

    /** @return class-string<Model> */
    public function getModelClass(): string
    {
        if (isset($this->model)) {
            return $this->model;
        }

        if (isset(self::$getModelClassesUsing)) {
            return self::$getModelClassesUsing->call($this, static::class);
        }

        return str(static::class)
            ->classBasename()
            ->beforeLast('Table')
            ->singular()
            ->prepend('\\App\\Models\\')
            ->toString();
    }

    protected function defineQuery(): Builder
    {
        return $this->getModel()->query();
    }

    public function getKeyName(): string
    {
        return $this->getModel()->getKeyName();
    }
}
