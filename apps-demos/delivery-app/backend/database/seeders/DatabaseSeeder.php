<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = collect([
            ['name' => 'admin', 'label' => 'Administrador'],
            ['name' => 'employee', 'label' => 'Empleado'],
            ['name' => 'driver', 'label' => 'Repartidor'],
            ['name' => 'client', 'label' => 'Cliente'],
        ])->mapWithKeys(function (array $role) {
            $record = Role::query()->updateOrCreate(
                ['name' => $role['name']],
                ['label' => $role['label']]
            );

            return [$role['name'] => $record];
        });

        User::query()->updateOrCreate(
            ['email' => 'admin@delivery.local'],
            [
                'name' => 'Admin Delivery',
                'role' => 'admin',
                'role_id' => $roles['admin']->id,
                'is_active' => true,
                'password' => Hash::make('demo1234'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'empleado@delivery.local'],
            [
                'name' => 'Empleado Delivery',
                'role' => 'employee',
                'role_id' => $roles['employee']->id,
                'is_active' => true,
                'password' => Hash::make('demo1234'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'repartidor@delivery.local'],
            [
                'name' => 'Repartidor Delivery',
                'role' => 'driver',
                'role_id' => $roles['driver']->id,
                'is_active' => true,
                'password' => Hash::make('demo1234'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'cliente@delivery.local'],
            [
                'name' => 'Cliente Delivery',
                'role' => 'client',
                'role_id' => $roles['client']->id,
                'is_active' => true,
                'password' => Hash::make('demo1234'),
            ]
        );
    }
}
