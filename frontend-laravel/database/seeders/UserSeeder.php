<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Roles if they do not exist
        $roleOperator = Role::firstOrCreate(['name' => 'operator']);
        $roleKepalaUpt = Role::firstOrCreate(['name' => 'kepala_upt']);
        $roleKepalaDishub = Role::firstOrCreate(['name' => 'kepala_dishub']);

        // 2. Operator UPT Parkir
        $operator = User::updateOrCreate(
            ['username' => 'operator'],
            [
                'password' => Hash::make('password'),
            ]
        );
        $operator->syncRoles([$roleOperator]);

        // 3. Kepala UPT Parkir
        $kepalaUpt = User::updateOrCreate(
            ['username' => 'kepala_upt'],
            [
                'password' => Hash::make('password'),
            ]
        );
        $kepalaUpt->syncRoles([$roleKepalaUpt]);

        // 4. Kepala Dishub
        $kepalaDishub = User::updateOrCreate(
            ['username' => 'kepala_dishub'],
            [
                'password' => Hash::make('password'),
            ]
        );
        $kepalaDishub->syncRoles([$roleKepalaDishub]);
    }
}
