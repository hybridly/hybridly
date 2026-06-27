<?php

namespace Database\Seeders;

use App\Models\UserFactory;
use Illuminate\Database\Seeder;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        UserFactory::new()->times(100)->create();
    }
}
