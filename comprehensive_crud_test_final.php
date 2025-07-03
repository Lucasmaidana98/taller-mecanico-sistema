<?php

/**
 * Comprehensive CRUD Testing Script
 * Tests all modules: Clientes, Vehiculos, Servicios, Empleados, Ordenes
 * Tests: Create, Read, Update, Delete operations
 * Tests: Alert systems, data persistence, validation
 */

// Initialize cURL session with cookie support
$cookieFile = __DIR__ . '/comprehensive_test_cookies.txt';
$baseUrl = 'http://localhost:8001';

// Initialize test results
$testResults = [
    'login' => [],
    'dashboard' => [],
    'clientes' => ['create' => [], 'read' => [], 'update' => [], 'delete' => [], 'validation' => []],
    'vehiculos' => ['create' => [], 'read' => [], 'update' => [], 'delete' => [], 'validation' => []],
    'servicios' => ['create' => [], 'read' => [], 'update' => [], 'delete' => [], 'validation' => []],
    'empleados' => ['create' => [], 'read' => [], 'update' => [], 'delete' => [], 'validation' => []],
    'ordenes' => ['create' => [], 'read' => [], 'update' => [], 'delete' => [], 'validation' => []],
    'statistics' => [],
    'cross_references' => []
];

function makeCurlRequest($url, $postData = null, $method = 'GET') {
    global $cookieFile;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST' && $postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

function extractCSRFToken($html) {
    if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $html, $matches)) {
        return $matches[1];
    }
    return null;
}

function checkForAlerts($html) {
    $alerts = [];
    
    // Check for success alerts
    if (preg_match_all('/<div[^>]*class="[^"]*alert-success[^"]*"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
        foreach ($matches[1] as $match) {
            $alerts['success'][] = strip_tags($match);
        }
    }
    
    // Check for error alerts
    if (preg_match_all('/<div[^>]*class="[^"]*alert-danger[^"]*"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
        foreach ($matches[1] as $match) {
            $alerts['error'][] = strip_tags($match);
        }
    }
    
    // Check for validation errors
    if (preg_match_all('/<div[^>]*class="[^"]*text-red[^"]*"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
        foreach ($matches[1] as $match) {
            $alerts['validation'][] = strip_tags($match);
        }
    }
    
    return $alerts;
}

function generateTestData($type) {
    $timestamp = time();
    
    switch ($type) {
        case 'cliente':
            return [
                'nombre' => "Cliente Test {$timestamp}",
                'apellido' => "Apellido Test",
                'email' => "cliente{$timestamp}@test.com",
                'telefono' => "123456789{$timestamp}",
                'documento' => "12345{$timestamp}",
                'direccion' => "Dirección Test {$timestamp}"
            ];
        
        case 'vehiculo':
            return [
                'marca' => "Toyota",
                'modelo' => "Corolla Test {$timestamp}",
                'año' => "2020",
                'patente' => "ABC{$timestamp}",
                'color' => "Rojo",
                'cliente_id' => 1 // Will be updated with actual client ID
            ];
        
        case 'servicio':
            return [
                'nombre' => "Servicio Test {$timestamp}",
                'descripcion' => "Descripción del servicio test {$timestamp}",
                'precio' => "150.00",
                'duracion_estimada' => "120"
            ];
        
        case 'empleado':
            return [
                'nombre' => "Empleado Test {$timestamp}",
                'apellido' => "Apellido Test",
                'email' => "empleado{$timestamp}@test.com",
                'telefono' => "987654321{$timestamp}",
                'documento' => "98765{$timestamp}",
                'cargo' => "Mecánico",
                'salario' => "2500.00"
            ];
        
        case 'orden':
            return [
                'cliente_id' => 1,
                'vehiculo_id' => 1,
                'servicio_id' => 1,
                'empleado_id' => 1,
                'fecha_ingreso' => date('Y-m-d'),
                'descripcion_problema' => "Problema test {$timestamp}",
                'estado' => 'pendiente'
            ];
    }
    
    return [];
}

echo "=== COMPREHENSIVE CRUD TESTING STARTED ===\n";
echo date('Y-m-d H:i:s') . "\n\n";

// Step 1: Login
echo "1. TESTING LOGIN\n";
echo "================\n";

$loginPage = makeCurlRequest("$baseUrl/login");
if ($loginPage['http_code'] !== 200) {
    echo "❌ Failed to access login page\n";
    exit(1);
}

$csrfToken = extractCSRFToken($loginPage['response']);
if (!$csrfToken) {
    echo "❌ CSRF token not found\n";
    exit(1);
}

$loginData = http_build_query([
    '_token' => $csrfToken,
    'email' => 'admin@taller.com',
    'password' => 'admin123'
]);

$loginResult = makeCurlRequest("$baseUrl/login", $loginData, 'POST');
$testResults['login']['status'] = $loginResult['http_code'] === 302 ? 'SUCCESS' : 'FAILED';
$testResults['login']['redirect'] = $loginResult['http_code'] === 302;

echo "Login attempt: " . ($testResults['login']['status'] === 'SUCCESS' ? '✅' : '❌') . "\n\n";

// Step 2: Access Dashboard and check statistics
echo "2. TESTING DASHBOARD & STATISTICS\n";
echo "=================================\n";

$dashboard = makeCurlRequest("$baseUrl/dashboard");
$testResults['dashboard']['access'] = $dashboard['http_code'] === 200;
$testResults['dashboard']['statistics'] = [];

if ($dashboard['http_code'] === 200) {
    // Extract statistics from dashboard
    if (preg_match_all('/(\d+)\s*(?:Cliente|Vehículo|Servicio|Empleado|Orden)/i', $dashboard['response'], $matches)) {
        $testResults['dashboard']['statistics'] = $matches[1];
    }
    echo "✅ Dashboard accessible\n";
    echo "📊 Initial statistics: " . implode(', ', $testResults['dashboard']['statistics']) . "\n\n";
} else {
    echo "❌ Dashboard not accessible\n\n";
}

// Step 3: Test each module
$modules = ['clientes', 'vehiculos', 'servicios', 'empleados', 'ordenes'];
$createdItems = [];

foreach ($modules as $module) {
    echo "3. TESTING MODULE: " . strtoupper($module) . "\n";
    echo str_repeat("=", 30 + strlen($module)) . "\n";
    
    // 3.1 Test CREATE operation
    echo "3.1 Testing CREATE operation\n";
    
    $createPage = makeCurlRequest("$baseUrl/$module/create");
    if ($createPage['http_code'] !== 200) {
        echo "❌ Create page not accessible\n";
        $testResults[$module]['create']['page_access'] = false;
        continue;
    } else {
        echo "✅ Create page accessible\n";
        $testResults[$module]['create']['page_access'] = true;
    }
    
    $csrfToken = extractCSRFToken($createPage['response']);
    if (!$csrfToken) {
        echo "❌ CSRF token not found on create page\n";
        continue;
    }
    
    $testData = generateTestData(rtrim($module, 's'));
    
    // Update references for dependent modules
    if ($module === 'vehiculos' && !empty($createdItems['clientes'])) {
        $testData['cliente_id'] = $createdItems['clientes'];
    }
    if ($module === 'ordenes') {
        if (!empty($createdItems['clientes'])) $testData['cliente_id'] = $createdItems['clientes'];
        if (!empty($createdItems['vehiculos'])) $testData['vehiculo_id'] = $createdItems['vehiculos'];
        if (!empty($createdItems['servicios'])) $testData['servicio_id'] = $createdItems['servicios'];
        if (!empty($createdItems['empleados'])) $testData['empleado_id'] = $createdItems['empleados'];
    }
    
    $testData['_token'] = $csrfToken;
    $createData = http_build_query($testData);
    
    $createResult = makeCurlRequest("$baseUrl/$module", $createData, 'POST');
    $testResults[$module]['create']['submission'] = $createResult['http_code'];
    
    if ($createResult['http_code'] === 302) {
        echo "✅ Create operation successful (redirected)\n";
        $testResults[$module]['create']['status'] = 'SUCCESS';
        
        // Follow redirect to check for alerts
        $redirectUrl = $createResult['response'];
        if (empty($redirectUrl)) {
            $redirectUrl = "$baseUrl/$module";
        }
        
        $listPage = makeCurlRequest($redirectUrl);
        $alerts = checkForAlerts($listPage['response']);
        $testResults[$module]['create']['alerts'] = $alerts;
        
        if (!empty($alerts['success'])) {
            echo "✅ Success alert displayed: " . implode(', ', $alerts['success']) . "\n";
        }
        
        // Extract created item ID if possible
        if (preg_match('/\/\d+\/edit/', $listPage['response'], $matches)) {
            preg_match('/\/(\d+)\/edit/', $matches[0], $idMatches);
            $createdItems[$module] = $idMatches[1];
            echo "📝 Created item ID: " . $createdItems[$module] . "\n";
        }
        
    } else {
        echo "❌ Create operation failed (HTTP: {$createResult['http_code']})\n";
        $testResults[$module]['create']['status'] = 'FAILED';
        
        // Check for validation errors
        $alerts = checkForAlerts($createResult['response']);
        if (!empty($alerts['validation'])) {
            echo "🔍 Validation errors: " . implode(', ', $alerts['validation']) . "\n";
        }
    }
    
    // 3.2 Test READ operation (List view)
    echo "3.2 Testing READ operation (List)\n";
    
    $listPage = makeCurlRequest("$baseUrl/$module");
    $testResults[$module]['read']['list_access'] = $listPage['http_code'] === 200;
    
    if ($listPage['http_code'] === 200) {
        echo "✅ List page accessible\n";
        
        // Check if created item appears in list
        $testName = $testData['nombre'] ?? $testData['marca'] ?? 'test';
        if (strpos($listPage['response'], $testName) !== false) {
            echo "✅ Created item appears in listing\n";
            $testResults[$module]['read']['item_in_list'] = true;
        } else {
            echo "⚠️ Created item may not appear in listing immediately\n";
            $testResults[$module]['read']['item_in_list'] = false;
        }
    } else {
        echo "❌ List page not accessible\n";
    }
    
    // 3.3 Test UPDATE operation
    echo "3.3 Testing UPDATE operation\n";
    
    if (!empty($createdItems[$module])) {
        $editPage = makeCurlRequest("$baseUrl/$module/{$createdItems[$module]}/edit");
        
        if ($editPage['http_code'] === 200) {
            echo "✅ Edit page accessible\n";
            $testResults[$module]['update']['page_access'] = true;
            
            $csrfToken = extractCSRFToken($editPage['response']);
            if ($csrfToken) {
                $updateData = generateTestData(rtrim($module, 's'));
                $updateData['_token'] = $csrfToken;
                $updateData['_method'] = 'PUT';
                
                // Modify data to show update
                if (isset($updateData['nombre'])) {
                    $updateData['nombre'] .= ' - UPDATED';
                } elseif (isset($updateData['marca'])) {
                    $updateData['marca'] .= ' - UPDATED';
                }
                
                $updateDataStr = http_build_query($updateData);
                $updateResult = makeCurlRequest("$baseUrl/$module/{$createdItems[$module]}", $updateDataStr, 'POST');
                
                if ($updateResult['http_code'] === 302) {
                    echo "✅ Update operation successful\n";
                    $testResults[$module]['update']['status'] = 'SUCCESS';
                    
                    // Check for success alert
                    $redirectPage = makeCurlRequest("$baseUrl/$module");
                    $alerts = checkForAlerts($redirectPage['response']);
                    if (!empty($alerts['success'])) {
                        echo "✅ Update success alert displayed\n";
                    }
                } else {
                    echo "❌ Update operation failed\n";
                    $testResults[$module]['update']['status'] = 'FAILED';
                }
            }
        } else {
            echo "❌ Edit page not accessible\n";
            $testResults[$module]['update']['page_access'] = false;
        }
    } else {
        echo "⚠️ No created item to update\n";
    }
    
    // 3.4 Test DELETE operation
    echo "3.4 Testing DELETE operation\n";
    
    if (!empty($createdItems[$module])) {
        // First, get the CSRF token from the list page
        $listPage = makeCurlRequest("$baseUrl/$module");
        $csrfToken = extractCSRFToken($listPage['response']);
        
        if ($csrfToken) {
            $deleteData = http_build_query([
                '_token' => $csrfToken,
                '_method' => 'DELETE'
            ]);
            
            $deleteResult = makeCurlRequest("$baseUrl/$module/{$createdItems[$module]}", $deleteData, 'POST');
            
            if ($deleteResult['http_code'] === 302) {
                echo "✅ Delete operation successful\n";
                $testResults[$module]['delete']['status'] = 'SUCCESS';
                
                // Check for success alert
                $redirectPage = makeCurlRequest("$baseUrl/$module");
                $alerts = checkForAlerts($redirectPage['response']);
                if (!empty($alerts['success'])) {
                    echo "✅ Delete success alert displayed\n";
                }
                
                // Verify item is removed from listing
                $testName = $testData['nombre'] ?? $testData['marca'] ?? 'test';
                if (strpos($redirectPage['response'], $testName) === false) {
                    echo "✅ Item removed from listing\n";
                    $testResults[$module]['delete']['removed_from_list'] = true;
                }
            } else {
                echo "❌ Delete operation failed\n";
                $testResults[$module]['delete']['status'] = 'FAILED';
            }
        }
    } else {
        echo "⚠️ No created item to delete\n";
    }
    
    // 3.5 Test VALIDATION
    echo "3.5 Testing VALIDATION\n";
    
    $createPage = makeCurlRequest("$baseUrl/$module/create");
    $csrfToken = extractCSRFToken($createPage['response']);
    
    if ($csrfToken) {
        // Submit empty form to test required field validation
        $emptyData = http_build_query(['_token' => $csrfToken]);
        $validationResult = makeCurlRequest("$baseUrl/$module", $emptyData, 'POST');
        
        $alerts = checkForAlerts($validationResult['response']);
        if (!empty($alerts['validation']) || $validationResult['http_code'] === 422) {
            echo "✅ Validation working (required fields checked)\n";
            $testResults[$module]['validation']['required_fields'] = true;
        } else {
            echo "⚠️ Validation may not be working properly\n";
            $testResults[$module]['validation']['required_fields'] = false;
        }
        
        // Test duplicate data if applicable
        if (in_array($module, ['clientes', 'empleados'])) {
            $duplicateData = generateTestData(rtrim($module, 's'));
            $duplicateData['email'] = 'admin@taller.com'; // Use existing email
            $duplicateData['_token'] = $csrfToken;
            
            $duplicateResult = makeCurlRequest("$baseUrl/$module", http_build_query($duplicateData), 'POST');
            
            if ($duplicateResult['http_code'] !== 302) {
                echo "✅ Duplicate email validation working\n";
                $testResults[$module]['validation']['duplicate_email'] = true;
            } else {
                echo "⚠️ Duplicate email validation may not be working\n";
                $testResults[$module]['validation']['duplicate_email'] = false;
            }
        }
    }
    
    echo "\n";
}

// Step 4: Check statistics after operations
echo "4. CHECKING STATISTICS AFTER OPERATIONS\n";
echo "=======================================\n";

$finalDashboard = makeCurlRequest("$baseUrl/dashboard");
if ($finalDashboard['http_code'] === 200) {
    if (preg_match_all('/(\d+)\s*(?:Cliente|Vehículo|Servicio|Empleado|Orden)/i', $finalDashboard['response'], $matches)) {
        $finalStats = $matches[1];
        echo "📊 Final statistics: " . implode(', ', $finalStats) . "\n";
        
        // Compare with initial stats
        if ($testResults['dashboard']['statistics'] !== $finalStats) {
            echo "✅ Statistics updated after operations\n";
            $testResults['statistics']['updated'] = true;
        } else {
            echo "⚠️ Statistics may not have updated\n";
            $testResults['statistics']['updated'] = false;
        }
    }
}

// Step 5: Generate comprehensive report
echo "\n5. GENERATING COMPREHENSIVE REPORT\n";
echo "==================================\n";

$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'test_summary' => [],
    'detailed_results' => $testResults,
    'recommendations' => []
];

// Calculate success rates
foreach ($modules as $module) {
    $successes = 0;
    $total = 0;
    
    foreach (['create', 'read', 'update', 'delete'] as $operation) {
        if (isset($testResults[$module][$operation]['status'])) {
            $total++;
            if ($testResults[$module][$operation]['status'] === 'SUCCESS') {
                $successes++;
            }
        }
    }
    
    $successRate = $total > 0 ? round(($successes / $total) * 100, 2) : 0;
    $report['test_summary'][$module] = [
        'success_rate' => $successRate,
        'operations_tested' => $total,
        'successful_operations' => $successes
    ];
    
    echo "📊 $module: $successRate% success rate ($successes/$total operations)\n";
}

// Save detailed report
file_put_contents(__DIR__ . '/comprehensive_test_report.json', json_encode($report, JSON_PRETTY_PRINT));

echo "\n=== COMPREHENSIVE CRUD TESTING COMPLETED ===\n";
echo "Detailed report saved to: comprehensive_test_report.json\n";

?>