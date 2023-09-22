<?php

namespace Hybridly\Tables\Actions\DataTransferObjects;

use Illuminate\Http\Request;

final class BulkActionData
{
    public function __construct(
        public readonly string $tableId,
        public readonly string $action,
        public readonly bool $all,
        public readonly array $except,
        public readonly array $only,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            tableId: $request->string('tableId'),
            action: $request->string('action'),
            all: $request->boolean('all'),
            except: $request->input('except', []),
            only: $request->input('only', []),
        );
    }
}
