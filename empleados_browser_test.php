<?php
/**
 * Browser-based test for Empleados Module
 * Tests specific endpoints and functionality
 */

function testHttpEndpoint($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

echo "🧪 EMPLEADOS MODULE BROWSER TEST\n";
echo "================================\n\n";

$baseUrl = 'http://localhost:8001';
$testResults = [];

// Test 1: Check if application is accessible
echo "🔗 Testing application accessibility...\n";
$result = testHttpEndpoint($baseUrl);
if ($result['status_code'] == 200 || $result['status_code'] == 302) {
    echo "✅ Application is accessible\n";
    $testResults['app_access'] = 'PASS';
} else {
    echo "❌ Application is not accessible (HTTP {$result['status_code']})\n";
    $testResults['app_access'] = 'FAIL';
}

// Test 2: Check empleados routes
echo "\n🛤️ Testing empleados routes...\n";
$routes = [
    '/empleados' => 'Index page',
    '/empleados/create' => 'Create form'
];

foreach ($routes as $route => $description) {
    $result = testHttpEndpoint($baseUrl . $route);
    
    if ($result['status_code'] == 200) {
        echo "✅ $description: Accessible\n";
        $testResults['route_' . str_replace('/', '_', $route)] = 'PASS';
    } elseif ($result['status_code'] == 302) {
        echo "🔒 $description: Redirected (likely requires authentication)\n";
        $testResults['route_' . str_replace('/', '_', $route)] = 'REDIRECT';
    } else {
        echo "❌ $description: Not accessible (HTTP {$result['status_code']})\n";
        $testResults['route_' . str_replace('/', '_', $route)] = 'FAIL';
    }
}

// Test 3: Check if create form contains expected fields
echo "\n📝 Analyzing create form structure...\n";
$createResult = testHttpEndpoint($baseUrl . '/empleados/create');

if ($createResult['status_code'] == 200) {
    $html = $createResult['response'];
    
    $expectedFields = [
        'name' => 'Name field',
        'email' => 'Email field', 
        'phone' => 'Phone field',
        'position' => 'Position field',
        'salary' => 'Salary field',
        'hire_date' => 'Hire date field',
        'status' => 'Status field'
    ];
    
    foreach ($expectedFields as $field => $description) {
        if (strpos($html, 'name="' . $field . '"') !== false) {
            echo "✅ $description: Present\n";
            $testResults['form_field_' . $field] = 'PASS';
        } else {
            echo "❌ $description: Missing\n";
            $testResults['form_field_' . $field] = 'FAIL';
        }
    }
    
    // Check for CSRF token
    if (strpos($html, '_token') !== false || strpos($html, 'csrf') !== false) {
        echo "✅ CSRF protection: Present\n";
        $testResults['csrf_protection'] = 'PASS';
    } else {
        echo "❌ CSRF protection: Missing\n";
        $testResults['csrf_protection'] = 'FAIL';
    }
    
    // Check for validation error display
    if (strpos($html, '@error') !== false || strpos($html, 'is-invalid') !== false) {
        echo "✅ Validation error display: Present\n";
        $testResults['validation_display'] = 'PASS';
    } else {
        echo "❌ Validation error display: Missing\n";
        $testResults['validation_display'] = 'FAIL';
    }
    
    // Check for JavaScript validation
    if (strpos($html, 'empleadoForm') !== false && strpos($html, 'validation') !== false) {
        echo "✅ Client-side validation: Present\n";
        $testResults['client_validation'] = 'PASS';
    } else {
        echo "⚠️ Client-side validation: Not clearly present\n";
        $testResults['client_validation'] = 'PARTIAL';
    }
}

// Test 4: Check database connectivity
echo "\n💾 Testing database structure...\n";
if (file_exists('database/database.sqlite')) {
    echo "✅ SQLite database file exists\n";
    $testResults['database_file'] = 'PASS';
    
    try {
        $pdo = new PDO('sqlite:database/database.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if empleados table exists
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='empleados'");
        if ($stmt->fetch()) {
            echo "✅ Empleados table exists\n";
            $testResults['empleados_table'] = 'PASS';
            
            // Check table structure
            $stmt = $pdo->query("PRAGMA table_info(empleados)");
            $columns = $stmt->fetchAll();
            
            $expectedColumns = ['name', 'email', 'phone', 'position', 'salary', 'hire_date', 'status'];
            $foundColumns = array_column($columns, 'name');
            
            foreach ($expectedColumns as $column) {
                if (in_array($column, $foundColumns)) {
                    echo "✅ Column $column: Present\n";
                    $testResults['column_' . $column] = 'PASS';
                } else {
                    echo "❌ Column $column: Missing\n";
                    $testResults['column_' . $column] = 'FAIL';
                }
            }
            
            // Check for existing data
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM empleados");
            $result = $stmt->fetch();
            echo "📊 Existing employees in database: {$result['count']}\n";
            $testResults['existing_data_count'] = $result['count'];
            
        } else {
            echo "❌ Empleados table does not exist\n";
            $testResults['empleados_table'] = 'FAIL';
        }
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
        $testResults['database_connection'] = 'FAIL';
    }
} else {
    echo "❌ Database file not found\n";
    $testResults['database_file'] = 'FAIL';
}

// Test 5: Check Laravel application structure
echo "\n🏗️ Testing Laravel application structure...\n";
$structureChecks = [
    'app/Http/Controllers/EmpleadoController.php' => 'Controller',
    'app/Models/Empleado.php' => 'Model',
    'app/Http/Requests/EmpleadoRequest.php' => 'Form Request',
    'resources/views/empleados/index.blade.php' => 'Index View',
    'resources/views/empleados/create.blade.php' => 'Create View',
    'resources/views/empleados/edit.blade.php' => 'Edit View',
    'resources/views/empleados/show.blade.php' => 'Show View'
];

foreach ($structureChecks as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description: Present\n";
        $testResults['structure_' . basename($file, '.php')] = 'PASS';
    } else {
        echo "❌ $description: Missing\n";
        $testResults['structure_' . basename($file, '.php')] = 'FAIL';
    }
}

// Generate summary
echo "\n📊 TEST SUMMARY\n";
echo "===============\n";

$totalTests = count($testResults);
$passedTests = count(array_filter($testResults, function($result) {
    return $result === 'PASS';
}));
$failedTests = count(array_filter($testResults, function($result) {
    return $result === 'FAIL';
}));
$partialTests = count(array_filter($testResults, function($result) {
    return in_array($result, ['PARTIAL', 'REDIRECT']);
}));

echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: $failedTests\n";
echo "Partial/Redirected: $partialTests\n\n";

$successRate = $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0;
echo "Success Rate: " . number_format($successRate, 1) . "%\n\n";

// Manual testing instructions
echo "🔧 MANUAL TESTING REQUIRED\n";
echo "==========================\n";
echo "The automated tests have verified the basic structure and accessibility.\n";
echo "Please perform the following manual tests:\n\n";

echo "1. 🔐 LOGIN TEST:\n";
echo "   - Go to http://localhost:8001\n";
echo "   - Login with admin@taller.com / admin123\n";
echo "   - Verify successful login\n\n";

echo "2. 📝 CREATE TEST:\n";
echo "   - Navigate to /empleados/create\n";
echo "   - Fill form with test data:\n";
echo "     * Name: 'Test Employee'\n";
echo "     * Email: 'test.employee@example.com'\n";
echo "     * Phone: '555-9999'\n";
echo "     * Position: 'Test Position'\n";
echo "     * Salary: '50000'\n";
echo "     * Hire Date: Today's date\n";
echo "     * Status: Active\n";
echo "   - Submit and verify success alert\n";
echo "   - Check if employee appears in index\n\n";

echo "3. ✏️ UPDATE TEST:\n";
echo "   - Edit the test employee\n";
echo "   - Change salary to '55000'\n";
echo "   - Change position to 'Senior Test Position'\n";
echo "   - Submit and verify success alert\n";
echo "   - Verify changes persist in views\n\n";

echo "4. 👁️ SHOW PAGE TEST:\n";
echo "   - Access employee show page\n";
echo "   - Verify statistics display correctly\n";
echo "   - Check work orders section\n\n";

echo "5. 🔍 VALIDATION TEST:\n";
echo "   - Try creating employee with duplicate email\n";
echo "   - Test required field validation\n";
echo "   - Verify error messages display properly\n\n";

echo "💾 Results saved with timestamp: " . date('Y-m-d H:i:s') . "\n";

// Save results to JSON file
$reportData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'test_results' => $testResults,
    'summary' => [
        'total_tests' => $totalTests,
        'passed' => $passedTests,
        'failed' => $failedTests,
        'partial' => $partialTests,
        'success_rate' => $successRate
    ]
];

file_put_contents('empleados_test_results.json', json_encode($reportData, JSON_PRETTY_PRINT));
echo "📁 Detailed results saved to: empleados_test_results.json\n";