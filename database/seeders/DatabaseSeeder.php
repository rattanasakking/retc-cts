<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $accounts = [
            ['name' => 'Admin', 'email' => 'admin@retc-cts.test', 'role' => UserRole::Admin],
            ['name' => 'Teacher', 'email' => 'teacher@retc-cts.test', 'role' => UserRole::Teacher],
            ['name' => 'Executive', 'email' => 'executive@retc-cts.test', 'role' => UserRole::Executive],
            ['name' => 'Department Head', 'email' => 'depthead@retc-cts.test', 'role' => UserRole::DepartmentHead],
        ];

        foreach ($accounts as $account) {
            User::factory()->create($account);
        }

        $this->call(CareerTrackingSeeder::class);
    }
}
