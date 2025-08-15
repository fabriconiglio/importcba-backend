<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Resetear roles y permisos en caché
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos para productos
        Permission::create(['name' => 'view products', 'guard_name' => 'web']);
        Permission::create(['name' => 'create products', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit products', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete products', 'guard_name' => 'web']);

        // Crear permisos para pedidos
        Permission::create(['name' => 'view orders', 'guard_name' => 'web']);
        Permission::create(['name' => 'create orders', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit orders', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete orders', 'guard_name' => 'web']);

        // Crear permisos para usuarios
        Permission::create(['name' => 'view users', 'guard_name' => 'web']);
        Permission::create(['name' => 'create users', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit users', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete users', 'guard_name' => 'web']);

        // Crear rol de administrador
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        // Crear rol de cliente
        $customerRole = Role::create(['name' => 'customer', 'guard_name' => 'web']);
        $customerRole->givePermissionTo([
            'view products',
            'create orders',
            'view orders',
        ]);

        // Crear usuario administrador por defecto
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Crear usuario cliente de prueba
        $customer = User::create([
            'name' => 'Cliente Demo',
            'email' => 'cliente@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $customer->assignRole('customer');
    }
}