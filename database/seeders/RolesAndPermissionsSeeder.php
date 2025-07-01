<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Clientes
            'ver-clientes', 'crear-clientes', 'editar-clientes', 'eliminar-clientes',
            // Vehiculos
            'ver-vehiculos', 'crear-vehiculos', 'editar-vehiculos', 'eliminar-vehiculos',
            // Servicios
            'ver-servicios', 'crear-servicios', 'editar-servicios', 'eliminar-servicios',
            // Empleados
            'ver-empleados', 'crear-empleados', 'editar-empleados', 'eliminar-empleados',
            // Ordenes de Trabajo
            'ver-ordenes', 'crear-ordenes', 'editar-ordenes', 'eliminar-ordenes',
            // Reportes
            'ver-reportes', 'generar-reportes', 'exportar-reportes',
            // Dashboard
            'ver-dashboard'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::create(['name' => 'Administrador']);
        $mecanicoRole = Role::create(['name' => 'Mecánico']);
        $recepcionistaRole = Role::create(['name' => 'Recepcionista']);

        // Assign permissions to roles
        $adminRole->givePermissionTo(Permission::all());

        $mecanicoRole->givePermissionTo([
            'ver-dashboard', 'ver-ordenes', 'editar-ordenes', 'ver-vehiculos', 'editar-vehiculos',
            'ver-servicios', 'ver-clientes'
        ]);

        $recepcionistaRole->givePermissionTo([
            'ver-dashboard', 'ver-clientes', 'crear-clientes', 'editar-clientes',
            'ver-vehiculos', 'crear-vehiculos', 'editar-vehiculos',
            'ver-ordenes', 'crear-ordenes', 'editar-ordenes',
            'ver-servicios', 'ver-reportes'
        ]);

        // Create default admin user
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@taller.com',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now()
        ]);

        $admin->assignRole('Administrador');

        // Create sample mechanic user
        $mecanico = User::create([
            'name' => 'Juan Pérez',
            'email' => 'mecanico@taller.com',
            'password' => Hash::make('mecanico123'),
            'email_verified_at' => now()
        ]);

        $mecanico->assignRole('Mecánico');

        // Create sample receptionist user
        $recepcionista = User::create([
            'name' => 'María González', 
            'email' => 'recepcion@taller.com',
            'password' => Hash::make('recepcion123'),
            'email_verified_at' => now()
        ]);

        $recepcionista->assignRole('Recepcionista');
    }
}
