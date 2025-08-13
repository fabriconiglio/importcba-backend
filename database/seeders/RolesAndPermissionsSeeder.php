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
        Permission::create(['name' => 'view products']);
        Permission::create(['name' => 'create products']);
        Permission::create(['name' => 'edit products']);
        Permission::create(['name' => 'delete products']);

        // Crear permisos para pedidos
        Permission::create(['name' => 'view orders']);
        Permission::create(['name' => 'create orders']);
        Permission::create(['name' => 'edit orders']);
        Permission::create(['name' => 'delete orders']);

        // Crear permisos para usuarios
        Permission::create(['name' => 'view users']);
        Permission::create(['name' => 'create users']);
        Permission::create(['name' => 'edit users']);
        Permission::create(['name' => 'delete users']);

        // Crear rol de administrador
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Crear rol de cliente
        $customerRole = Role::create(['name' => 'customer']);
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