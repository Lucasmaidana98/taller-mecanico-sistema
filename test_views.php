<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Testing View Rendering ===\n\n";

function testView($viewName, $data = []) {
    try {
        $view = view($viewName, $data);
        $rendered = $view->render();
        
        return [
            'success' => true,
            'length' => strlen($rendered),
            'hasContent' => !empty(trim($rendered))
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Prepare test data
$testData = [
    'ordenes' => App\Models\OrdenTrabajo::with(['cliente', 'vehiculo', 'empleado', 'servicio'])->paginate(15),
    'clientes' => App\Models\Cliente::where('status', true)->orderBy('name')->get(),
    'vehiculos' => App\Models\Vehiculo::where('status', true)->orderBy('brand')->get(),
    'empleados' => App\Models\Empleado::where('status', true)->orderBy('name')->get(),
    'servicios' => App\Models\Servicio::where('status', true)->orderBy('name')->get(),
    'ordenTrabajo' => App\Models\OrdenTrabajo::with(['cliente', 'vehiculo', 'empleado', 'servicio'])->first()
];

$viewTests = [
    ['ordenes.index', ['ordenes' => $testData['ordenes'], 'clientes' => $testData['clientes'], 'vehiculos' => $testData['vehiculos'], 'empleados' => $testData['empleados'], 'servicios' => $testData['servicios']]],
    ['ordenes.create', ['clientes' => $testData['clientes'], 'vehiculos' => $testData['vehiculos'], 'empleados' => $testData['empleados'], 'servicios' => $testData['servicios']]],
    ['ordenes.show', ['ordenTrabajo' => $testData['ordenTrabajo']]],
    ['ordenes.edit', ['ordenTrabajo' => $testData['ordenTrabajo'], 'clientes' => $testData['clientes'], 'vehiculos' => $testData['vehiculos'], 'empleados' => $testData['empleados'], 'servicios' => $testData['servicios']]],
    ['reportes.index', []]
];

echo "Testing Ordenes Views:\n";
foreach ($viewTests as [$viewName, $data]) {
    echo "   $viewName: ";
    $result = testView($viewName, $data);
    
    if ($result['success']) {
        echo "✓ Renders successfully ({$result['length']} bytes)\n";
        if (!$result['hasContent']) {
            echo "      ⚠ Warning: View seems to render empty content\n";
        }
    } else {
        echo "✗ Error: {$result['error']}\n";
    }
}

echo "\n=== Testing View Variables and Content ===\n";

// Test ordenes.index view specifically
echo "Testing ordenes.index view content:\n";
try {
    $view = view('ordenes.index', [
        'ordenes' => $testData['ordenes'],
        'clientes' => $testData['clientes'],
        'vehiculos' => $testData['vehiculos'],
        'empleados' => $testData['empleados'],
        'servicios' => $testData['servicios']
    ]);
    
    $rendered = $view->render();
    
    // Check for expected content
    $checks = [
        'form' => str_contains($rendered, '<form'),
        'table' => str_contains($rendered, '<table') || str_contains($rendered, 'table'),
        'clientes_dropdown' => str_contains($rendered, 'clientes') && str_contains($rendered, 'select'),
        'pagination' => str_contains($rendered, 'pagination') || str_contains($rendered, 'paginate'),
        'ordenes_data' => str_contains($rendered, 'orden') || str_contains($rendered, 'Orden'),
    ];
    
    foreach ($checks as $check => $passed) {
        echo "   $check: " . ($passed ? "✓" : "✗") . "\n";
    }
    
} catch (Exception $e) {
    echo "Error testing ordenes.index content: " . $e->getMessage() . "\n";
}

echo "\n=== Testing PDF View (if exists) ===\n";
$pdfViewPath = 'resources/views/reportes/pdf';
if (is_dir($pdfViewPath)) {
    echo "PDF views directory found. Testing PDF templates:\n";
    
    $pdfViews = glob($pdfViewPath . '/*.blade.php');
    foreach ($pdfViews as $pdfView) {
        $viewName = 'reportes.pdf.' . basename($pdfView, '.blade.php');
        echo "   $viewName: ";
        
        $testReportData = [
            'ordenes' => [
                'ordenes' => App\Models\OrdenTrabajo::with(['cliente', 'vehiculo', 'empleado', 'servicio'])->take(5)->get(),
                'estadisticas' => [
                    'total_ordenes' => 10,
                    'ordenes_pendientes' => 3,
                    'ordenes_completadas' => 5,
                    'ingresos_total' => 1000000
                ]
            ]
        ];
        
        $result = testView($viewName, [
            'data' => $testReportData,
            'tipoReporte' => 'ordenes',
            'fechaInicio' => now()->startOfMonth(),
            'fechaFin' => now()->endOfMonth(),
            'fechaGeneracion' => now()
        ]);
        
        if ($result['success']) {
            echo "✓ Renders successfully ({$result['length']} bytes)\n";
        } else {
            echo "✗ Error: {$result['error']}\n";
        }
    }
} else {
    echo "PDF views directory not found.\n";
}

echo "\n=== Testing Dropdown Data Availability ===\n";

$dropdownTests = [
    'clientes' => $testData['clientes'],
    'vehiculos' => $testData['vehiculos'],
    'empleados' => $testData['empleados'],
    'servicios' => $testData['servicios']
];

foreach ($dropdownTests as $name => $collection) {
    echo "$name: ";
    if ($collection->count() > 0) {
        echo "✓ {$collection->count()} active records\n";
        $first = $collection->first();
        echo "   Sample: {$first->name}" . (isset($first->price) ? " (Price: {$first->price})" : "") . "\n";
    } else {
        echo "✗ No active records found\n";
    }
}

echo "\n=== Testing Relationships ===\n";

if ($testData['ordenTrabajo']) {
    $orden = $testData['ordenTrabajo'];
    echo "Testing OrdenTrabajo relationships:\n";
    echo "   cliente: " . ($orden->cliente ? "✓ {$orden->cliente->name}" : "✗ Not loaded") . "\n";
    echo "   vehiculo: " . ($orden->vehiculo ? "✓ {$orden->vehiculo->license_plate}" : "✗ Not loaded") . "\n";
    echo "   empleado: " . ($orden->empleado ? "✓ {$orden->empleado->name}" : "✗ Not loaded") . "\n";
    echo "   servicio: " . ($orden->servicio ? "✓ {$orden->servicio->name}" : "✗ Not loaded") . "\n";
} else {
    echo "No OrdenTrabajo found for testing relationships\n";
}

echo "\nView Testing Complete!\n";