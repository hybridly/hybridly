<?php

namespace Monolikit\Concerns;

use Illuminate\Http\Request;

trait SharesValidationErrors
{
    /**
     * Whether to share validation errors.
     */
    protected bool $shareValidationErrors = true;

    /**
     * Shares validation errors to all requests.
     */
    protected function shareValidationErrors(Request $request): array
    {
        return [
            'errors' => function () use ($request) {
                return $this->resolveValidationErrors($request);
            },
        ];
    }
}
