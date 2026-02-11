<?php

namespace Hybridly\Tables\Actions\DataTransferObjects;

use Illuminate\Http\Request;

final class BulkSelection
{
    public function __construct(
        public readonly bool $all,
        public readonly array $except,
        public readonly array $only,
    ) {}

    public static function fromBulkActionData(BulkActionData $action): static
    {
        return new static(
            all: $action->all,
            except: $action->except,
            only: $action->only,
        );
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            all: $request->boolean('all'),
            except: $request->input('except', []),
            only: $request->input('only', []),
        );
    }
}
