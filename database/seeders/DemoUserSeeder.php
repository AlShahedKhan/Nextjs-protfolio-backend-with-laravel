<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@test.com';

    public const ADMIN_NAME = 'Portfolio Admin';

    public const ADMIN_PASSWORD = 'Password123!';

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => self::ADMIN_NAME,
                'email_verified_at' => now(),
                'is_admin' => true,
                'password' => Hash::make(self::ADMIN_PASSWORD),
            ],
        );
    }
}
