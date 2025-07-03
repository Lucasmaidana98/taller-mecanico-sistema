<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Testing Clientes Module ===\n\n";

function makeTestRequest($method, $uri, $params = []) {
    global $app;
    
    try {
        $request = Request::create($uri, $method, $params);
        $request->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
        
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
            'trace' => $e->getTraceAsString(),
            'content' => null
        ];
    }
}

// Test 1: GET /clientes (index)
echo "1. Testing GET /clientes (index)\n";
$response = makeTestRequest('GET', '/clientes');
echo "Status: " . $response['status'] . "\n";

if ($response['status'] === 302) {
    echo "   -> Redirected (likely authentication required)\n";
    if (isset($response['headers']['location'])) {
        echo "   -> Redirect Location: " . implode(', ', $response['headers']['location']) . "\n";
    }
} elseif ($response['status'] === 200) {
    echo "   -> ✓ Success! Index page loaded\n";
    $contentLength = strlen($response['content']);
    echo "   -> Content length: $contentLength bytes\n";
    
    // Check for common UI elements
    $content = $response['content'];
    if (strpos($content, 'Clientes') !== false) {
        echo "   -> ✓ 'Clientes' text found in content\n";
    }
    if (strpos($content, 'table') !== false || strpos($content, 'Nuevo Cliente') !== false) {
        echo "   -> ✓ Table or 'Nuevo Cliente' button found\n";
    }
    if (strpos($content, 'error') !== false || strpos($content, 'Error') !== false) {
        echo "   -> ⚠ Error messages may be present\n";
    }
} else {
    echo "   -> ✗ Error occurred\n";
    if (isset($response['error'])) {
        echo "   -> Error: " . $response['error'] . "\n";
    }
}
echo "\n";

// Test 2: GET /clientes/create (create form)
echo "2. Testing GET /clientes/create (create form)\n";
$response = makeTestRequest('GET', '/clientes/create');
echo "Status: " . $response['status'] . "\n";

if ($response['status'] === 302) {
    echo "   -> Redirected (likely authentication required)\n";
    if (isset($response['headers']['location'])) {
        echo "   -> Redirect Location: " . implode(', ', $response['headers']['location']) . "\n";
    }
} elseif ($response['status'] === 200) {
    echo "   -> ✓ Success! Create form loaded\n";
    $contentLength = strlen($response['content']);
    echo "   -> Content length: $contentLength bytes\n";
    
    // Check for form elements
    $content = $response['content'];
    if (strpos($content, '<form') !== false) {
        echo "   -> ✓ Form element found\n";
    }
    if (strpos($content, 'nombre') !== false || strpos($content, 'Nombre') !== false) {
        echo "   -> ✓ 'Nombre' field likely present\n";
    }
    if (strpos($content, 'telefono') !== false || strpos($content, 'Teléfono') !== false) {
        echo "   -> ✓ 'Teléfono' field likely present\n";
    }
    if (strpos($content, 'email') !== false || strpos($content, 'Email') !== false) {
        echo "   -> ✓ 'Email' field likely present\n";
    }
} else {
    echo "   -> ✗ Error occurred\n";
    if (isset($response['error'])) {
        echo "   -> Error: " . $response['error'] . "\n";
    }
}
echo "\n";

// Test 3: GET /clientes/1 (show specific client)
echo "3. Testing GET /clientes/1 (show specific client)\n";
$response = makeTestRequest('GET', '/clientes/1');
echo "Status: " . $response['status'] . "\n";

if ($response['status'] === 302) {
    echo "   -> Redirected (likely authentication required)\n";
} elseif ($response['status'] === 200) {
    echo "   -> ✓ Success! Client details loaded\n";
    $contentLength = strlen($response['content']);
    echo "   -> Content length: $contentLength bytes\n";
} elseif ($response['status'] === 404) {
    echo "   -> Client not found (expected if no data exists)\n";
} else {
    echo "   -> ✗ Error occurred\n";
    if (isset($response['error'])) {
        echo "   -> Error: " . $response['error'] . "\n";
    }
}
echo "\n";

// Test 4: GET /clientes/1/edit (edit form)
echo "4. Testing GET /clientes/1/edit (edit form)\n";
$response = makeTestRequest('GET', '/clientes/1/edit');
echo "Status: " . $response['status'] . "\n";

if ($response['status'] === 302) {
    echo "   -> Redirected (likely authentication required)\n";
} elseif ($response['status'] === 200) {
    echo "   -> ✓ Success! Edit form loaded\n";
    $contentLength = strlen($response['content']);
    echo "   -> Content length: $contentLength bytes\n";
    
    // Check for form elements
    $content = $response['content'];
    if (strpos($content, '<form') !== false) {
        echo "   -> ✓ Form element found\n";
    }
    if (strpos($content, 'method') !== false && strpos($content, 'PATCH') !== false) {
        echo "   -> ✓ PATCH method detected (proper for edit)\n";
    }
} elseif ($response['status'] === 404) {
    echo "   -> Client not found (expected if no data exists)\n";
} else {
    echo "   -> ✗ Error occurred\n";
    if (isset($response['error'])) {
        echo "   -> Error: " . $response['error'] . "\n";
    }
}
echo "\n";

// Test 5: POST /clientes (store - create new client)
echo "5. Testing POST /clientes (store new client)\n";
$clientData = [
    'nombre' => 'Test Cliente',
    'telefono' => '123456789',
    'email' => 'test@example.com',
    'direccion' => 'Test Address 123'
];
$response = makeTestRequest('POST', '/clientes', $clientData);
echo "Status: " . $response['status'] . "\n";

if ($response['status'] === 302) {
    echo "   -> Redirected (might be successful creation or auth required)\n";
    if (isset($response['headers']['location'])) {
        $location = implode(', ', $response['headers']['location']);
        echo "   -> Redirect Location: $location\n";
        if (strpos($location, '/clientes') !== false) {
            echo "   -> ✓ Redirected to clientes (likely successful)\n";
        }
    }
} elseif ($response['status'] === 201 || $response['status'] === 200) {
    echo "   -> ✓ Success! Client created\n";
} elseif ($response['status'] === 422) {
    echo "   -> Validation error\n";
    $content = $response['content'];
    if (strpos($content, 'errors') !== false) {
        echo "   -> Validation errors present in response\n";
    }
} else {
    echo "   -> ✗ Error occurred\n";
    if (isset($response['error'])) {
        echo "   -> Error: " . $response['error'] . "\n";
    }
}
echo "\n";

// Test Routes Analysis
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
    
    $clienteRoutes = $routes->filter(function ($route) {
        return str_contains($route['uri'], 'cliente') || str_contains($route['name'] ?? '', 'cliente');
    });
    
    echo "Cliente-related routes:\n";
    foreach ($clienteRoutes as $route) {
        echo "   {$route['method']} /{$route['uri']} -> {$route['action']}\n";
    }
    
} catch (Exception $e) {
    echo "Error analyzing routes: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Controller Methods Directly ===\n";

try {
    // Test ClienteController methods
    echo "Testing ClienteController methods:\n";
    
    $controller = new App\Http\Controllers\ClienteController();
    
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
        $cliente = App\Models\Cliente::first();
        if ($cliente) {
            $result = $controller->show($cliente);
            echo "✓ Works (returns " . get_class($result) . ")\n";
        } else {
            echo "✗ No clients available for testing\n";
        }
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
    
    // Test edit method
    echo "   edit() method: ";
    try {
        $cliente = App\Models\Cliente::first();
        if ($cliente) {
            $result = $controller->edit($cliente);
            echo "✓ Works (returns " . get_class($result) . ")\n";
        } else {
            echo "✗ No clients available for testing\n";
        }
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error testing ClienteController: " . $e->getMessage() . "\n";
}

echo "\n=== Database Status ===\n";

try {
    // Check if clients table exists and has data
    $clientCount = App\Models\Cliente::count();
    echo "Total clients in database: $clientCount\n";
    
    if ($clientCount > 0) {
        echo "Sample client data:\n";
        $sample = App\Models\Cliente::first();
        echo "   ID: " . $sample->id . "\n";
        echo "   Nombre: " . $sample->nombre . "\n";
        echo "   Email: " . ($sample->email ?? 'Not set') . "\n";
        echo "   Teléfono: " . ($sample->telefono ?? 'Not set') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error checking database: " . $e->getMessage() . "\n";
}

echo "\nClientes Module Testing Complete!\n";