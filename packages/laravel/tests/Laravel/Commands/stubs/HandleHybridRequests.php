<?php

namespace App\Http\Middleware;

use App\Data\SharedData;
use App\Data\UserData;
use Hybridly\HandleHybridRequests;

final class HandleHybridRequests extends HandleHybridRequests
{
    /**
     * Defines the properties that are shared to all requests.
     */
    public function share(): SharedData
    {
        return SharedData::from([
            'user' => UserData::optional(auth()->user()),
        ]);
    }
}
