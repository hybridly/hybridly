<?php

namespace App\KitchenSink\Tables\OptimisticMerge;

use App\Models\User;
use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

final class UsersTable extends Table
{
    protected int $recordsPerPage = 5;
    protected string $model = User::class;
    protected string $paginatorType = Paginator::class;

    public function __construct(
        private readonly array $deletedIds,
    ) {}

    protected function defineQuery(): Builder
    {
        return User::query()
            ->whereNotIn('id', $this->deletedIds)
            ->orderBy('id');
    }

    protected function defineColumns(): array
    {
        return [
            TextColumn::make('id')->label('#'),
            TextColumn::make('name')->label('Name'),
            TextColumn::make('email')->label('Email'),
            TextColumn::make('updated_at')->label('Updated'),
        ];
    }
}
