<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('funnel.admin_password');
        if (! is_string($password) || strlen($password) < 12) {
            throw new \RuntimeException('Set ADMIN_PASSWORD to at least 12 characters before seeding.');
        }
        // Re-running migrations/seeds must not silently reset an existing admin password.
        if (! User::where('email', config('funnel.admin_email'))->exists()) {
            $user = new User;
            $user->name = 'SellerBoost Admin';
            $user->email = config('funnel.admin_email');
            $user->password = Hash::make($password);
            $user->is_admin = true;
            $user->save();
        }
    }
}
