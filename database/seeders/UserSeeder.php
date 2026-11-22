<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Retire the old demo accounts when this seeder is re-run.
        User::whereIn('email', ['admin@bytestock.test', 'test@example.com'])->delete();

        User::updateOrCreate(
            ['email' => 'winbert@gmail.com'],
            [
                'name' => 'Winbert',
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'password' => Hash::make('winbert906'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'staff@bytestock.test'],
            [
                'name' => 'Staff User',
                'role' => User::ROLE_STAFF,
                'status' => User::STATUS_ACTIVE,
                'password' => Hash::make('staff123'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'manager@bytestock.test'],
            [
                'name' => 'Manager User',
                'role' => User::ROLE_MANAGER,
                'status' => User::STATUS_ACTIVE,
                'password' => Hash::make('manager123'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'warehouse@bytestock.test'],
            [
                'name' => 'Warehouse User',
                'role' => User::ROLE_WAREHOUSE,
                'status' => User::STATUS_ACTIVE,
                'password' => Hash::make('warehouse123'),
            ],
        );
    }
}
