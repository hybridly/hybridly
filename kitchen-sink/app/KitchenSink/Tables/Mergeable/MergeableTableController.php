<?php

namespace App\KitchenSink\Tables\Mergeable;

use App\Models\User;
use Discovery\Routing\Get;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;

use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/tables/mergeable', name: 'kitchen-sink.tables.mergeable')]
final class MergeableTableController
{
    #[Get('/', name: 'index')]
    public function __invoke()
    {
        $generation = (int) session()->get('kitchen_sink_mergeable_table_generation', 0) + 1;

        session()->put('kitchen_sink_mergeable_table_generation', $generation);

        $this->refreshUsers($generation);

        return view('kitchen-sink::tables.mergeable.index', [
            'users' => MergeableUsersTable::make(['generation' => $generation])->merge(),
        ]);
    }

    private function refreshUsers(int $generation): void
    {
        foreach (range(1, 5) as $index) {
            User::query()->updateOrCreate(
                ['id' => 9000 + $index],
                [
                    'name' => "Merge user {$index}.{$generation}",
                    'email' => "mergeable-table-{$index}@example.test",
                    'email_verified_at' => now(),
                    'password' => '$2y$12$NCKboOPbeFznPFadqxsE2.8agOS5FOZI8BlNItEwCJD58v5afflzi',
                ],
            );
        }
    }
}
