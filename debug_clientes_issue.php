<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Debugging Clientes Module Issues ===\n\n";

// Test 1: Check database connection and table
echo "1. Testing Database Connection and Table Structure\n";
try {
    $pdo = DB::connection()->getPdo();
    echo "   ✓ Database connected successfully\n";
    
    // Check if clientes table exists
    $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='clientes'");
    if (!empty($tables)) {
        echo "   ✓ 'clientes' table exists\n";
        
        // Get table structure
        $columns = DB::select("PRAGMA table_info(clientes)");
        echo "   Table structure:\n";
        foreach ($columns as $column) {
            echo "      - {$column->name}: {$column->type}" . ($column->notnull ? " NOT NULL" : "") . "\n";
        }
    } else {
        echo "   ✗ 'clientes' table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Check if we have sample data
echo "2. Checking Sample Data\n";
try {
    $count = App\Models\Cliente::count();
    echo "   Total clients in database: $count\n";
    
    if ($count > 0) {
        $sample = App\Models\Cliente::first();
        echo "   Sample client data:\n";
        echo "      ID: " . $sample->id . "\n";
        echo "      Name: " . $sample->name . "\n";
        echo "      Email: " . $sample->email . "\n";
        echo "      Phone: " . $sample->phone . "\n";
        echo "      Document: " . $sample->document_number . "\n";
        echo "      Status: " . ($sample->status ? 'Active' : 'Inactive') . "\n";
        echo "      Created: " . $sample->created_at . "\n";
    } else {
        echo "   ⚠ No sample data found. Creating a test client...\n";
        try {
            $testClient = App\Models\Cliente::create([
                'name' => 'Test Cliente',
                'email' => 'test@example.com',
                'phone' => '123456789',
                'address' => 'Test Address 123',
                'document_number' => 'DOC123456',
                'status' => true
            ]);
            echo "   ✓ Test client created with ID: " . $testClient->id . "\n";
        } catch (Exception $e) {
            echo "   ✗ Failed to create test client: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Error accessing Cliente model: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Test Controller Methods in Isolation
echo "3. Testing Controller Methods in Isolation\n";
try {
    $controller = new App\Http\Controllers\ClienteController();
    
    // Test index method
    echo "   Testing index() method: ";
    try {
        $request = new Illuminate\Http\Request();
        $result = $controller->index($request);
        echo "✓ Works (returns " . get_class($result) . ")\n";
        
        // Check if result has data
        if ($result instanceof Illuminate\View\View) {
            $data = $result->getData();
            if (isset($data['clientes'])) {
                echo "      - Has clientes data: " . $data['clientes']->count() . " items\n";
                echo "      - Has stats data: " . (isset($data['stats']) ? 'Yes' : 'No') . "\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        echo "      Trace: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    // Test create method
    echo "   Testing create() method: ";
    try {
        $result = $controller->create();
        echo "✓ Works (returns " . get_class($result) . ")\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Error instantiating ClienteController: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Test Routes Registration
echo "4. Testing Routes Registration\n";
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
        return str_contains($route['uri'], 'cliente');
    });
    
    echo "   Found " . $clienteRoutes->count() . " cliente routes:\n";
    foreach ($clienteRoutes as $route) {
        echo "      {$route['method']} /{$route['uri']} -> {$route['action']}\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Error analyzing routes: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Test View Files Exist
echo "5. Testing View Files Existence\n";
$viewFiles = [
    'clientes.index' => 'resources/views/clientes/index.blade.php',
    'clientes.create' => 'resources/views/clientes/create.blade.php',
    'clientes.show' => 'resources/views/clientes/show.blade.php',
    'clientes.edit' => 'resources/views/clientes/edit.blade.php'
];

foreach ($viewFiles as $viewName => $filePath) {
    echo "   $viewName: ";
    if (file_exists($filePath)) {
        echo "✓ Exists\n";
    } else {
        echo "✗ Missing\n";
    }
}
echo "\n";

// Test 6: Test HTTP Requests Directly (Simulating Browser)
echo "6. Testing HTTP Requests Directly\n";

function makeDetailedTestRequest($method, $uri, $params = []) {
    global $app;
    
    try {
        $request = Request::create($uri, $method, $params);
        $request->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
        
        $response = $app->handle($request);
        
        return [
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(),
            'headers' => $response->headers->all(),
            'success' => true
        ];
    } catch (Exception $e) {
        return [
            'status' => 500,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'content' => null,
            'success' => false
        ];
    }
}

// Test GET /clientes
echo "   Testing GET /clientes:\n";
$response = makeDetailedTestRequest('GET', '/clientes');
echo "      Status: " . $response['status'] . "\n";
if (!$response['success']) {
    echo "      Error: " . $response['error'] . "\n";
    // Show first few lines of stack trace
    $traceLines = explode("\n", $response['trace']);
    for ($i = 0; $i < min(5, count($traceLines)); $i++) {
        echo "      " . $traceLines[$i] . "\n";
    }
} else {
    echo "      ✓ Request successful\n";
    if ($response['status'] == 302) {
        $location = $response['headers']['location'] ?? ['Not set'];
        echo "      Redirect to: " . implode(', ', $location) . "\n";
    }
}

echo "\nDebugging Complete!\n";