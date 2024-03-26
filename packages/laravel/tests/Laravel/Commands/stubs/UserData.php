<?php

namespace App\Data\UserData;

use Spatie\LaravelData\Data;

final class UserData extends Data
{
    public function __construct(
        public readonly string $username,
    ) {
    }
}
