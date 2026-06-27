<?php

namespace App\KitchenSink\Tables\OptimisticMerge;

use Discovery\Routing\Get;
use Discovery\Routing\Post;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;
use Hybridly\HybridResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Store;

use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/tables/optimistic-merge', name: 'kitchen-sink.tables.optimistic-merge')]
final class OptimisticMergeTableController
{
    private const DELETED_IDS_KEY = 'kitchen_sink_optimistic_merge_deleted_ids';

    public function __construct(
        private readonly Store $session,
    ) {}

    #[Get('/', name: 'index')]
    public function __invoke(): HybridResponse
    {
        return view('kitchen-sink::tables.optimistic-merge.index', [
            'users' => UsersTable::make([
                'deletedIds' => $this->deletedIds(),
            ])->merge(),
        ]);
    }

    #[Post('/delete', name: 'delete')]
    public function delete(Request $request): RedirectResponse
    {
        $deletedIds = [
            ...$this->deletedIds(),
            $request->integer('id'),
        ];

        $this->session->put(self::DELETED_IDS_KEY, array_values(array_unique($deletedIds)));

        return back();
    }

    #[Post('/reset', name: 'reset')]
    public function reset(): RedirectResponse
    {
        $this->session->forget(self::DELETED_IDS_KEY);

        return back();
    }

    private function deletedIds(): array
    {
        return $this->session->get(self::DELETED_IDS_KEY, []);
    }
}
