<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Test database connection
echo "=== Testing Database Connection ===\n";
try {
    $clientes = DB::table('clientes')->count();
    $vehiculos = DB::table('vehiculos')->count();
    $empleados = DB::table('empleados')->count();
    $servicios = DB::table('servicios')->count();
    $ordenes = DB::table('orden_trabajos')->count();
    $users = DB::table('users')->count();
    
    echo "Database connected successfully!\n";
    echo "Clientes: $clientes\n";
    echo "Vehiculos: $vehiculos\n";
    echo "Empleados: $empleados\n";
    echo "Servicios: $servicios\n";
    echo "Ordenes: $ordenes\n";
    echo "Users: $users\n";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Testing OrdenTrabajo Model and Relationships ===\n";
try {
    $orden = App\Models\OrdenTrabajo::with(['cliente', 'vehiculo', 'empleado', 'servicio'])->first();
    if ($orden) {
        echo "First order found:\n";
        echo "ID: " . $orden->id . "\n";
        echo "Description: " . $orden->description . "\n";
        echo "Status: " . $orden->status . "\n";
        echo "Cliente: " . ($orden->cliente ? $orden->cliente->name : 'NULL') . "\n";
        echo "Vehiculo: " . ($orden->vehiculo ? $orden->vehiculo->license_plate : 'NULL') . "\n";
        echo "Empleado: " . ($orden->empleado ? $orden->empleado->name : 'NULL') . "\n";
        echo "Servicio: " . ($orden->servicio ? $orden->servicio->name : 'NULL') . "\n";
    } else {
        echo "No orders found in database\n";
    }
} catch (Exception $e) {
    echo "Error testing OrdenTrabajo model: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Dropdown Data ===\n";
try {
    $clientes = App\Models\Cliente::where('status', true)->orderBy('name')->get(['id', 'name']);
    $vehiculos = App\Models\Vehiculo::where('status', true)->orderBy('brand')->get(['id', 'license_plate', 'brand', 'model']);
    $empleados = App\Models\Empleado::where('status', true)->orderBy('name')->get(['id', 'name']);
    $servicios = App\Models\Servicio::where('status', true)->orderBy('name')->get(['id', 'name', 'price']);
    
    echo "Active Clientes: " . $clientes->count() . "\n";
    echo "Active Vehiculos: " . $vehiculos->count() . "\n";
    echo "Active Empleados: " . $empleados->count() . "\n";
    echo "Active Servicios: " . $servicios->count() . "\n";
    
    if ($clientes->count() > 0) {
        echo "Sample Cliente: " . $clientes->first()->name . "\n";
    }
    if ($servicios->count() > 0) {
        echo "Sample Servicio: " . $servicios->first()->name . " (Price: " . $servicios->first()->price . ")\n";
    }
} catch (Exception $e) {
    echo "Error testing dropdown data: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Report Data Generation ===\n";
try {
    $fechaInicio = now()->startOfMonth();
    $fechaFin = now()->endOfMonth();
    
    $ordenes = App\Models\OrdenTrabajo::with(['cliente', 'vehiculo', 'empleado', 'servicio'])
        ->whereBetween('created_at', [$fechaInicio, $fechaFin])
        ->get();
    
    echo "Orders in current month: " . $ordenes->count() . "\n";
    
    $estadisticas = [
        'total_ordenes' => $ordenes->count(),
        'ordenes_pendientes' => $ordenes->where('status', 'pending')->count(),
        'ordenes_en_proceso' => $ordenes->where('status', 'in_progress')->count(),
        'ordenes_completadas' => $ordenes->where('status', 'completed')->count(),
        'ordenes_canceladas' => $ordenes->where('status', 'cancelled')->count(),
        'ingresos_total' => $ordenes->where('status', 'completed')->sum('total_amount'),
    ];
    
    foreach ($estadisticas as $key => $value) {
        echo "$key: $value\n";
    }
    
} catch (Exception $e) {
    echo "Error testing report data: " . $e->getMessage() . "\n";
}

echo "\n=== Testing View Files ===\n";
$viewPaths = [
    'resources/views/ordenes/index.blade.php',
    'resources/views/ordenes/create.blade.php',
    'resources/views/ordenes/show.blade.php',
    'resources/views/ordenes/edit.blade.php',
    'resources/views/reportes/index.blade.php',
    'resources/views/reportes/pdf.blade.php',
];

foreach ($viewPaths as $path) {
    if (file_exists($path)) {
        echo "✓ $path exists\n";
    } else {
        echo "✗ $path missing\n";
    }
}

echo "\nTest completed!\n";