<?php

namespace App\Http\Middleware;

use App\Data\SharedData;
use App\Data\UserData;
use Hybridly\Http\Middleware;

final class HandleHybridRequests extends Middleware
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
