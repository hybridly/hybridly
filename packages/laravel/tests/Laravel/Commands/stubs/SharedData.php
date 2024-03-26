<?php

namespace App\Data\SharedData;

use App\Data\UserData;
use Spatie\LaravelData\Data;

final class SharedData extends Data
{
    public function __construct(
        public readonly ?UserData $user,
    ) {
    }
}
