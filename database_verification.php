<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Models\Servicio;
use App\Models\Empleado;
use App\Models\OrdenTrabajo;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DATABASE VERIFICATION ===\n\n";

echo "Current Database Records:\n";
echo "- Clientes: " . Cliente::count() . "\n";
echo "- Vehiculos: " . Vehiculo::count() . "\n";
echo "- Servicios: " . Servicio::count() . "\n";
echo "- Empleados: " . Empleado::count() . "\n";
echo "- Ordenes de Trabajo: " . OrdenTrabajo::count() . "\n";
echo "- Users: " . User::count() . "\n";
echo "- Roles: " . Role::count() . "\n";
echo "- Permissions: " . Permission::count() . "\n\n";

echo "Sample Data Check:\n";
echo "- First Cliente: " . (Cliente::first() ? Cliente::first()->name : 'None') . "\n";
echo "- First Vehiculo: " . (Vehiculo::first() ? Vehiculo::first()->brand . ' ' . Vehiculo::first()->model : 'None') . "\n";
echo "- First Servicio: " . (Servicio::first() ? Servicio::first()->name : 'None') . "\n";
echo "- First Empleado: " . (Empleado::first() ? Empleado::first()->name : 'None') . "\n";
echo "- First Orden: " . (OrdenTrabajo::first() ? 'Order #' . OrdenTrabajo::first()->id : 'None') . "\n\n";

echo "Users and Roles:\n";
$users = User::with('roles')->get();
foreach ($users as $user) {
    echo "- {$user->name} ({$user->email}): " . $user->roles->pluck('name')->implode(', ') . "\n";
}

echo "\nRelationship Samples:\n";
$clienteWithVehiculos = Cliente::with('vehiculos')->first();
if ($clienteWithVehiculos) {
    echo "- Cliente '{$clienteWithVehiculos->name}' has " . $clienteWithVehiculos->vehiculos->count() . " vehiculos\n";
}

$vehiculoWithCliente = Vehiculo::with('cliente')->first();
if ($vehiculoWithCliente) {
    echo "- Vehiculo '{$vehiculoWithCliente->brand} {$vehiculoWithCliente->model}' belongs to {$vehiculoWithCliente->cliente->name}\n";
}

$ordenWithAllRelations = OrdenTrabajo::with(['cliente', 'vehiculo', 'servicio', 'empleado'])->first();
if ($ordenWithAllRelations) {
    echo "- Orden #{$ordenWithAllRelations->id}: {$ordenWithAllRelations->cliente->name} - {$ordenWithAllRelations->vehiculo->brand} {$ordenWithAllRelations->vehiculo->model} - {$ordenWithAllRelations->servicio->name} - {$ordenWithAllRelations->empleado->name}\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";