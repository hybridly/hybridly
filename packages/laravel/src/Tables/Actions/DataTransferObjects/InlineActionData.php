<?php

namespace Hybridly\Tables\Actions\DataTransferObjects;

use Illuminate\Http\Request;

final class InlineActionData
{
    public function __construct(
        public readonly string $tableId,
        public readonly int|string $recordId,
        public readonly string $action,
        public readonly string $type,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            tableId: $request->string('tableId'),
            recordId: $request->input('recordId'),
            action: $request->string('action'),
            type: $request->string('type'),
        );
    }
}
