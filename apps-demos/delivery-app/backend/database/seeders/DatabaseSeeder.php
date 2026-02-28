<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
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
        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'demo-delivery'],
            [
                'name' => 'Demo Delivery',
                'plan_key' => 'full',
                'billing_status' => 'paid',
                'monthly_fee_ars' => 160000,
                'next_billing_at' => now()->addDays(15),
                'is_active' => true,
            ]
        );

        $roles = collect([
            ['name' => 'superadmin', 'label' => 'Super Administrador'],
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
            ['email' => 'superadmin@dunamis.local'],
            [
                'name' => 'Super Admin Dunamis',
                'role' => 'superadmin',
                'role_id' => $roles['superadmin']->id,
                'tenant_id' => null,
                'is_active' => true,
                'password' => Hash::make('demo1234'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@delivery.local'],
            [
                'name' => 'Admin Delivery',
                'role' => 'admin',
                'role_id' => $roles['admin']->id,
                'tenant_id' => $tenant->id,
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
                'tenant_id' => $tenant->id,
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
                'tenant_id' => $tenant->id,
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
                'tenant_id' => $tenant->id,
                'is_active' => true,
                'password' => Hash::make('demo1234'),
            ]
        );
    }
}
