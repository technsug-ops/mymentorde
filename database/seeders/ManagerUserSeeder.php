<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
class ManagerUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('MANAGER_EMAIL', 'manager@mentorde.local')],
            [
                'name' => env('MANAGER_NAME', 'Manager User'),
                'role' => 'manager',
                'password' => env('MANAGER_PASSWORD', 'ChangeMe123!'),
            ]
        );
    }
}
