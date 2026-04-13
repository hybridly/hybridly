<?php

namespace App\KitchenSink\DataLoading\InfiniteScroll;

use Carbon\CarbonImmutable;
use Discovery\Routing\Get;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

use function Hybridly\scroll;
use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/data-loading/infinite-scroll', name: 'kitchen-sink.data-loading.infinite-scroll')]
final class InfiniteScrollController
{
    #[Get('/', name: 'index')]
    public function __invoke(Request $request)
    {
        return view('kitchen-sink::data-loading.infinite-scroll.index', [
            'feed' => scroll(fn () => $this->paginateFeed($request)),
            'chat' => scroll(fn () => $this->paginateChat($request)),
        ]);
    }

    private function paginateFeed(Request $request): LengthAwarePaginator
    {
        return $this->paginate(
            items: $this->makeFeedItems(),
            request: $request,
            pageName: 'feedPage',
            perPage: 6,
            defaultPage: 1,
        );
    }

    private function paginateChat(Request $request): LengthAwarePaginator
    {
        return $this->paginate(
            items: $this->makeChatMessages(),
            request: $request,
            pageName: 'chatPage',
            perPage: 8,
            defaultPage: 4,
        );
    }

    private function paginate(Collection $items, Request $request, string $pageName, int $perPage, int $defaultPage): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query($pageName, $defaultPage));
        $slice = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            items: $slice,
            total: $items->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'pageName' => $pageName,
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );
    }

    private function makeFeedItems(): Collection
    {
        $publishedAt = CarbonImmutable::parse('2026-01-01T09:00:00+00:00');

        return collect(range(1, 42))
            ->map(fn (int $id) => [
                'id' => $id,
                'title' => "Dispatch {$id}",
                'author' => collect(['Fern', 'Stark', 'Frieren'])->get(($id - 1) % 3),
                'summary' => match ($id % 4) {
                    0 => 'Queued jobs are flowing again after the deploy.',
                    1 => 'Observers are now tracking the dominant page in the viewport.',
                    2 => 'Merge intents keep prepends and appends predictable across reloads.',
                    default => 'Partial reloads keep the interface responsive while data streams in.',
                },
                'publishedAt' => $publishedAt->addMinutes($id * 17)->toIso8601String(),
            ]);
    }

    private function makeChatMessages(): Collection
    {
        $sentAt = CarbonImmutable::parse('2026-01-02T18:30:00+00:00');

        return collect(range(1, 56))
            ->map(fn (int $offset) => [
                'id' => $offset,
                'author' => ($offset % 2) === 0 ? 'Operator' : 'Hybridly',
                'side' => ($offset % 2) === 0 ? 'right' : 'left',
                'content' => match ($offset % 5) {
                    0 => 'Reverse mode keeps the latest replies anchored at the bottom.',
                    1 => 'The visible page decides which query parameter stays in the URL.',
                    2 => 'Older messages can be prepended without resetting the whole list.',
                    3 => 'Manual mode leaves the request timing entirely to the UI controls.',
                    default => 'This conversation is intentionally paginated in reverse order.',
                },
                'sentAt' => $sentAt->subMinutes($offset * 11)->toIso8601String(),
            ])
            ->reverse()
            ->values();
    }
}
