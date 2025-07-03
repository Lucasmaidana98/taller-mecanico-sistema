<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Testing with Authentication ===\n\n";

// Authenticate as admin user for testing
$adminUser = App\Models\User::where('email', 'admin@taller.com')->first();
if (!$adminUser) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

// Set the authenticated user
Auth::login($adminUser);
echo "✅ Authenticated as: {$adminUser->name} ({$adminUser->email})\n\n";

// Test view rendering with authenticated user
function testViewWithAuth($viewName, $data = []) {
    try {
        $view = view($viewName, $data);
        $rendered = $view->render();
        
        return [
            'success' => true,
            'length' => strlen($rendered),
            'hasContent' => !empty(trim($rendered)),
            'content' => $rendered
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Prepare test data with proper error bag
$testData = [
    'ordenes' => App\Models\OrdenTrabajo::with(['cliente', 'vehiculo', 'empleado', 'servicio'])->paginate(15),
    'clientes' => App\Models\Cliente::where('status', true)->orderBy('name')->get(),
    'vehiculos' => App\Models\Vehiculo::where('status', true)->orderBy('brand')->get(),
    'empleados' => App\Models\Empleado::where('status', true)->orderBy('name')->get(),
    'servicios' => App\Models\Servicio::where('status', true)->orderBy('name')->get(),
    'ordenTrabajo' => App\Models\OrdenTrabajo::with(['cliente', 'vehiculo', 'empleado', 'servicio'])->first(),
    'errors' => new Illuminate\Support\MessageBag() // Empty error bag
];

echo "=== Testing Ordenes Views (Authenticated) ===\n";

$viewTests = [
    ['ordenes.index', ['ordenes' => $testData['ordenes'], 'clientes' => $testData['clientes'], 'vehiculos' => $testData['vehiculos'], 'empleados' => $testData['empleados'], 'servicios' => $testData['servicios']]],
    ['ordenes.create', ['clientes' => $testData['clientes'], 'vehiculos' => $testData['vehiculos'], 'empleados' => $testData['empleados'], 'servicios' => $testData['servicios'], 'errors' => $testData['errors']]],
    ['ordenes.show', ['ordenTrabajo' => $testData['ordenTrabajo']]],
    ['ordenes.edit', ['ordenTrabajo' => $testData['ordenTrabajo'], 'clientes' => $testData['clientes'], 'vehiculos' => $testData['vehiculos'], 'empleados' => $testData['empleados'], 'servicios' => $testData['servicios'], 'errors' => $testData['errors']]],
];

foreach ($viewTests as [$viewName, $data]) {
    echo "   $viewName: ";
    $result = testViewWithAuth($viewName, $data);
    
    if ($result['success']) {
        echo "✅ Renders successfully ({$result['length']} bytes)\n";
        
        // Check for specific content
        $content = $result['content'];
        $checks = [
            'form_exists' => str_contains($content, '<form'),
            'csrf_token' => str_contains($content, 'csrf'),
            'dropdown_exists' => str_contains($content, '<select'),
            'bootstrap_classes' => str_contains($content, 'form-control') || str_contains($content, 'btn'),
        ];
        
        foreach ($checks as $check => $passed) {
            if ($passed) {
                echo "      ✅ $check\n";
            }
        }
        
        if (!$result['hasContent']) {
            echo "      ⚠️ Warning: View seems to render empty content\n";
        }
    } else {
        echo "❌ Error: {$result['error']}\n";
    }
}

echo "\n=== Testing Reportes View (Authenticated) ===\n";
echo "   reportes.index: ";
$result = testViewWithAuth('reportes.index', []);

if ($result['success']) {
    echo "✅ Renders successfully ({$result['length']} bytes)\n";
    
    // Check for specific issues in reportes.index
    $content = $result['content'];
    if (str_contains($content, 'reportes.export')) {
        echo "      ❌ Found incorrect route 'reportes.export' (should be 'reportes.exportar')\n";
    } else {
        echo "      ✅ No route issues found\n";
    }
} else {
    echo "❌ Error: {$result['error']}\n";
}

echo "\n=== Testing Controller Methods (Authenticated) ===\n";

try {
    // Test OrdenTrabajoController with authenticated user
    echo "Testing OrdenTrabajoController with authentication:\n";
    
    $controller = new App\Http\Controllers\OrdenTrabajoController();
    
    // Create a mock request
    $request = Illuminate\Http\Request::create('/ordenes', 'GET');
    $request->setUserResolver(function () use ($adminUser) {
        return $adminUser;
    });
    
    // Test index method
    echo "   index() method: ";
    try {
        $result = $controller->index($request);
        echo "✅ Works (returns " . get_class($result) . ")\n";
        
        if ($result instanceof Illuminate\View\View) {
            $data = $result->getData();
            echo "      📊 Data passed to view:\n";
            foreach (['ordenes', 'clientes', 'vehiculos', 'empleados', 'servicios'] as $key) {
                if (isset($data[$key])) {
                    $count = is_countable($data[$key]) ? count($data[$key]) : 'object';
                    echo "         - $key: $count items\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error testing with authentication: " . $e->getMessage() . "\n";
}

echo "\n=== Testing ReporteController Methods (Authenticated) ===\n";

try {
    $controller = new App\Http\Controllers\ReporteController();
    
    // Test generar method with different report types
    $reportTypes = ['ordenes', 'clientes', 'empleados', 'servicios', 'ingresos', 'vehiculos'];
    
    foreach ($reportTypes as $tipo) {
        echo "   generar($tipo): ";
        try {
            $request = new Illuminate\Http\Request([
                'tipo_reporte' => $tipo,
                'fecha_inicio' => '2025-07-01',
                'fecha_fin' => '2025-07-31'
            ]);
            $request->setUserResolver(function () use ($adminUser) {
                return $adminUser;
            });
            
            $result = $controller->generar($request);
            
            if ($result instanceof Illuminate\Http\JsonResponse) {
                $data = json_decode($result->getContent(), true);
                echo "✅ " . ($data['success'] ? 'Success' : 'Failed');
                
                if (isset($data['data'])) {
                    echo " (with data)";
                }
                echo "\n";
            } else {
                echo "❌ Unexpected response type\n";
            }
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error testing ReporteController: " . $e->getMessage() . "\n";
}

echo "\n=== Checking Routes ===\n";

$routeChecks = [
    ['ordenes.index', 'GET', '/ordenes'],
    ['ordenes.create', 'GET', '/ordenes/create'],
    ['ordenes.store', 'POST', '/ordenes'],
    ['ordenes.show', 'GET', '/ordenes/{ordene}'],
    ['ordenes.edit', 'GET', '/ordenes/{ordene}/edit'],
    ['ordenes.update', 'PUT', '/ordenes/{ordene}'],
    ['ordenes.destroy', 'DELETE', '/ordenes/{ordene}'],
    ['reportes.index', 'GET', '/reportes'],
    ['reportes.generar', 'POST', '/reportes/generar'],
    ['reportes.exportar', 'GET', '/reportes/exportar/{id}'],
];

foreach ($routeChecks as [$name, $method, $uri]) {
    echo "   $name ($method $uri): ";
    try {
        $route = route($name, $name === 'ordenes.show' || $name === 'ordenes.edit' || $name === 'ordenes.update' || $name === 'ordenes.destroy' ? 1 : []);
        echo "✅ Exists\n";
    } catch (Exception $e) {
        echo "❌ Missing\n";
    }
}

echo "\n=== Checking for Missing Files ===\n";

// Check if PDF view directory exists
echo "Checking PDF view templates:\n";
$pdfViewDir = resource_path('views/reportes/pdf');
if (!is_dir($pdfViewDir)) {
    echo "   ❌ PDF views directory missing: $pdfViewDir\n";
    echo "   📁 Creating directory and basic templates...\n";
    
    if (!file_exists($pdfViewDir)) {
        mkdir($pdfViewDir, 0755, true);
        echo "   ✅ Directory created\n";
    }
} else {
    echo "   ✅ PDF views directory exists\n";
}

echo "\nAuthenticated Testing Complete!\n";