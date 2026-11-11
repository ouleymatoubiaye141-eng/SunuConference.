<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sunuconference.test'],
            [
                'name' => 'Administrateur SunuConference',
                'affiliation' => 'SunuConference',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make('password'),
                'must_change_password' => false,
            ],
        );
    }
}
