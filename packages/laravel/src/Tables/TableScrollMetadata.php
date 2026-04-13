<?php

namespace Hybridly\Tables;

use Hybridly\Support\Pagination\PaginatorMetadata;
use Hybridly\Support\Pagination\ScrollMetadata;
use Illuminate\Http\Request;
use LogicException;

final readonly class TableScrollMetadata implements ScrollMetadata
{
    private function __construct(
        private ScrollMetadata $metadata,
    ) {}

    public static function fromTable(Request $request, Table $table): self
    {
        $metadata = PaginatorMetadata::extract($request, $table->getPaginator());

        if ($metadata === null) {
            throw new LogicException('Tables can only expose scroll metadata when backed by a paginator instance.');
        }

        return new self($metadata);
    }

    public function type(): string
    {
        return $this->metadata->type();
    }

    public function queryKey(): string
    {
        return $this->metadata->queryKey();
    }

    public function current(): ?int
    {
        return $this->metadata->current();
    }

    public function previous(): ?int
    {
        return $this->metadata->previous();
    }

    public function next(): ?int
    {
        return $this->metadata->next();
    }

    public function toArray(): array
    {
        return $this->metadata->toArray();
    }
}
