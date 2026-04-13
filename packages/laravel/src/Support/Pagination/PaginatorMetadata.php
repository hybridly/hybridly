<?php

namespace Hybridly\Support\Pagination;

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

final readonly class PaginatorMetadata implements ScrollMetadata
{
    public function __construct(
        private string $type,
        private string $query_key,
        private ?int $current,
        private ?int $previous,
        private ?int $next,
    ) {}

    public function type(): string
    {
        return $this->type;
    }

    public function queryKey(): string
    {
        return $this->query_key;
    }

    public function current(): ?int
    {
        return $this->current;
    }

    public function previous(): ?int
    {
        return $this->previous;
    }

    public function next(): ?int
    {
        return $this->next;
    }

    public static function inferWrapper(mixed $value): ?string
    {
        return match (true) {
            $value instanceof LengthAwarePaginator, $value instanceof CursorPaginator, $value instanceof Paginator => 'data',
            default => null,
        };
    }

    public static function extract(Request $request, mixed $value): ?self
    {
        return match (true) {
            $value instanceof LengthAwarePaginator => new self(
                type: 'length-aware',
                query_key: $value->getPageName(),
                current: $request->query($value->getPageName()) ?? ($value->currentPage() > 1 ? $value->currentPage() : null),
                previous: $value->currentPage() > 1 ? ($value->currentPage() - 1) : null,
                next: $value->hasMorePages() ? ($value->currentPage() + 1) : null,
            ),
            $value instanceof CursorPaginator => new self(
                type: 'cursor',
                query_key: $value->getCursorName(),
                current: $request->query($value->getCursorName()),
                previous: $value->previousCursor()?->encode(),
                next: $value->nextCursor()?->encode(),
            ),
            $value instanceof Paginator => new self(
                type: 'simple',
                query_key: $value->getPageName(),
                current: $request->query($value->getPageName()) ?? ($value->currentPage() > 1 ? $value->currentPage() : null),
                previous: $value->currentPage() > 1 ? ($value->currentPage() - 1) : null,
                next: $value->hasMorePages() ? ($value->currentPage() + 1) : null,
            ),
            default => null,
        };
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'queryKey' => $this->queryKey(),
            'current' => $this->current(),
            'previous' => $this->previous(),
            'next' => $this->next(),
        ];
    }
}
