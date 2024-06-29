<?php

namespace Hybridly\Tables;

class AnonymousTable extends Table
{
    protected array $columns = [];
    protected array $refiners = [];
    protected ?string $data = null;

    public static function create(string $model, array $columns = [], array $refiners = [], string $data = null): static
    {
        $table = resolve(static::class);
        $table->getModelClassesUsing(fn () => $model);
        $table->columns = $columns;
        $table->refiners = $refiners;
        $table->data = $data;

        return $table;
    }

    protected function defineColumns(): array
    {
        return $this->columns;
    }

    protected function defineRefiners(): array
    {
        return $this->refiners;
    }
}
