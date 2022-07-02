<?php

namespace Monolikit\Concerns;

use Illuminate\Http\Request;

trait SharesFlashNotifications
{
    /**
     * Whether to share flash notifications.
     */
    protected bool $shareFlashNotifications = true;

    /**
     * Shares flash notifications to all requests.
     */
    public function shareFlashNotifications(Request $request): array
    {
        monolikit()->persist('flash');

        return [
            'flash' => function () use ($request) {
                return [
                    'success' => $request->session()->get('success'),
                    'warning' => $request->session()->get('warning'),
                    'info' => $request->session()->get('info'),
                    'error' => $request->session()->get('error'),
                ];
            },
        ];
    }
}
