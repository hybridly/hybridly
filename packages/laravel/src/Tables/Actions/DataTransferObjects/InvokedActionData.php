<?php

namespace Hybridly\Tables\Actions\DataTransferObjects;

use Illuminate\Http\Request;

final class InvokedActionData
{
    public function __construct(
        public readonly string $type,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            type: $request->string('type'),
        );
    }
}
