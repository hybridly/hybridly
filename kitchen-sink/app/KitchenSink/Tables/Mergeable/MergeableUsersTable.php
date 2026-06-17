<?php

namespace App\KitchenSink\Tables\Mergeable;

use App\Models\User;
use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class MergeableUsersTable extends Table
{
    protected int $recordsPerPage = 5;
    protected string $model = User::class;

    public function __construct(
        private readonly int $generation,
    ) {}

    protected function defineQuery(): Builder
    {
        return User::query()
            ->whereBetween('id', [9001, 9005])
            ->orderBy('id');
    }

    protected function defineColumns(): array
    {
        return [
            TextColumn::make('id')->label('#'),
            TextColumn::make('name')
                ->label('Name')
                ->transformValueUsing(fn (User $user) => "{$user->name} refreshed in batch {$this->generation}")
                ->extra(fn (User $user) => [
                    'generation' => $this->generation,
                    'priority' => ($user->id % 2) === 0 ? 'high' : 'normal',
                ]),
            TextColumn::make('email')->label('Email'),
            TextColumn::make('updated_at')->label('Updated'),
        ];
    }
}
