<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Testing HTTP Endpoints ===\n\n";

function makeTestRequest($method, $uri, $params = []) {
    global $app;
    
    try {
        $request = Request::create($uri, $method, $params);
        $request->headers->set('Accept', 'application/json');
        
        $response = $app->handle($request);
        
        return [
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(),
            'headers' => $response->headers->all()
        ];
    } catch (Exception $e) {
        return [
            'status' => 500,
            'error' => $e->getMessage(),
            'content' => null
        ];
    }
}

// Test 1: GET /ordenes (index)
echo "1. Testing GET /ordenes (index)\n";
$response = makeTestRequest('GET', '/ordenes');
echo "Status: " . $response['status'] . "\n";

if ($response['status'] === 302) {
    echo "   -> Redirected (authentication required)\n";
} elseif ($response['status'] === 200) {
    echo "   -> Success! Page loaded\n";
    $contentLength = strlen($response['content']);
    echo "   -> Content length: $contentLength bytes\n";
} else {
    echo "   -> Error: " . ($response['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 2: GET /ordenes/create (create form) 
echo "2. Testing GET /ordenes/create (create form)\n";
$response = makeTestRequest('GET', '/ordenes/create');
echo "Status: " . $response['status'] . "\n";

if ($response['status'] === 302) {
    echo "   -> Redirected (authentication required)\n";
} elseif ($response['status'] === 200) {
    echo "   -> Success! Create form loaded\n";
    $contentLength = strlen($response['content']);
    echo "   -> Content length: $contentLength bytes\n";
} else {
    echo "   -> Error: " . ($response['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 3: GET /ordenes/{id} (show)
echo "3. Testing GET /ordenes/1 (show)\n";
$response = makeTestRequest('GET', '/ordenes/1');
echo "Status: " . $response['status'] . "\n";

if ($response['status'] === 302) {
    echo "   -> Redirected (authentication required)\n";
} elseif ($response['status'] === 200) {
    echo "   -> Success! Order details loaded\n";
    $contentLength = strlen($response['content']);
    echo "   -> Content length: $contentLength bytes\n";
} elseif ($response['status'] === 404) {
    echo "   -> Order not found\n";
} else {
    echo "   -> Error: " . ($response['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 4: GET /ordenes/{id}/edit (edit form)
echo "4. Testing GET /ordenes/1/edit (edit form)\n";
$response = makeTestRequest('GET', '/ordenes/1/edit');
echo "Status: " . $response['status'] . "\n";

if ($response['status'] === 302) {
    echo "   -> Redirected (authentication required)\n";
} elseif ($response['status'] === 200) {
    echo "   -> Success! Edit form loaded\n";
    $contentLength = strlen($response['content']);
    echo "   -> Content length: $contentLength bytes\n";
} elseif ($response['status'] === 404) {
    echo "   -> Order not found\n";
} else {
    echo "   -> Error: " . ($response['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 5: GET /reportes (index)
echo "5. Testing GET /reportes (index)\n";
$response = makeTestRequest('GET', '/reportes');
echo "Status: " . $response['status'] . "\n";

if ($response['status'] === 302) {
    echo "   -> Redirected (authentication required)\n";
} elseif ($response['status'] === 200) {
    echo "   -> Success! Reports page loaded\n";
    $contentLength = strlen($response['content']);
    echo "   -> Content length: $contentLength bytes\n";
} else {
    echo "   -> Error: " . ($response['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 6: POST /reportes/generar (generate report)
echo "6. Testing POST /reportes/generar (generate report)\n";
$reportParams = [
    'tipo_reporte' => 'ordenes',
    'fecha_inicio' => '2025-07-01',
    'fecha_fin' => '2025-07-31'
];
$response = makeTestRequest('POST', '/reportes/generar', $reportParams);
echo "Status: " . $response['status'] . "\n";

if ($response['status'] === 302) {
    echo "   -> Redirected (authentication required)\n";
} elseif ($response['status'] === 200) {
    echo "   -> Success! Report generated\n";
    $contentLength = strlen($response['content']);
    echo "   -> Content length: $contentLength bytes\n";
    
    // Try to decode JSON response
    $jsonData = json_decode($response['content'], true);
    if ($jsonData && isset($jsonData['success'])) {
        echo "   -> JSON Response: " . ($jsonData['success'] ? 'Success' : 'Failed') . "\n";
        if (isset($jsonData['message'])) {
            echo "   -> Message: " . $jsonData['message'] . "\n";
        }
    }
} elseif ($response['status'] === 422) {
    echo "   -> Validation error\n";
} else {
    echo "   -> Error: " . ($response['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test Routes List
echo "=== Available Routes Analysis ===\n";
try {
    $routes = collect(Route::getRoutes())->map(function ($route) {
        return [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $route->getActionName()
        ];
    });
    
    $ordenRoutes = $routes->filter(function ($route) {
        return str_contains($route['uri'], 'orden') || str_contains($route['name'] ?? '', 'orden');
    });
    
    $reporteRoutes = $routes->filter(function ($route) {
        return str_contains($route['uri'], 'reporte') || str_contains($route['name'] ?? '', 'reporte');
    });
    
    echo "Orden-related routes:\n";
    foreach ($ordenRoutes as $route) {
        echo "   {$route['method']} /{$route['uri']} -> {$route['action']}\n";
    }
    
    echo "\nReporte-related routes:\n";
    foreach ($reporteRoutes as $route) {
        echo "   {$route['method']} /{$route['uri']} -> {$route['action']}\n";
    }
    
} catch (Exception $e) {
    echo "Error analyzing routes: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Controller Methods Directly ===\n";

try {
    // Test OrdenTrabajoController methods
    echo "Testing OrdenTrabajoController methods:\n";
    
    $controller = new App\Http\Controllers\OrdenTrabajoController();
    
    // Test index method
    echo "   index() method: ";
    try {
        $request = new Illuminate\Http\Request();
        $result = $controller->index($request);
        echo "✓ Works (returns " . get_class($result) . ")\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
    
    // Test create method
    echo "   create() method: ";
    try {
        $result = $controller->create();
        echo "✓ Works (returns " . get_class($result) . ")\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
    
    // Test show method
    echo "   show() method: ";
    try {
        $orden = App\Models\OrdenTrabajo::first();
        if ($orden) {
            $request = new Illuminate\Http\Request();
            $result = $controller->show($orden, $request);
            echo "✓ Works (returns " . get_class($result) . ")\n";
        } else {
            echo "✗ No orders available for testing\n";
        }
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
    
    // Test edit method
    echo "   edit() method: ";
    try {
        $orden = App\Models\OrdenTrabajo::first();
        if ($orden) {
            $result = $controller->edit($orden);
            echo "✓ Works (returns " . get_class($result) . ")\n";
        } else {
            echo "✗ No orders available for testing\n";
        }
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error testing OrdenTrabajoController: " . $e->getMessage() . "\n";
}

echo "\n";

try {
    // Test ReporteController methods
    echo "Testing ReporteController methods:\n";
    
    $controller = new App\Http\Controllers\ReporteController();
    
    // Test index method
    echo "   index() method: ";
    try {
        $result = $controller->index();
        echo "✓ Works (returns " . get_class($result) . ")\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
    
    // Test generar method
    echo "   generar() method: ";
    try {
        $request = new Illuminate\Http\Request([
            'tipo_reporte' => 'ordenes',
            'fecha_inicio' => '2025-07-01',
            'fecha_fin' => '2025-07-31'
        ]);
        $result = $controller->generar($request);
        echo "✓ Works (returns " . get_class($result) . ")\n";
        
        // Check if response is JSON
        $responseData = json_decode($result->getContent(), true);
        if ($responseData && isset($responseData['success'])) {
            echo "      -> JSON Response success: " . ($responseData['success'] ? 'true' : 'false') . "\n";
            if (isset($responseData['data'])) {
                echo "      -> Data included in response\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error testing ReporteController: " . $e->getMessage() . "\n";
}

echo "\nHTTP Endpoint Testing Complete!\n";