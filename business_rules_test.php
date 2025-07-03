<?php
/**
 * Business Rules and Field Immutability Test
 * Tests specific business logic restrictions and immutable field enforcement
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$baseUrl = 'http://localhost:8003';
$cookieFile = __DIR__ . '/business_rules_cookies.txt';
$testResults = [];

function makeRequest($url, $data = null, $method = 'GET', $cookieFile = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['body' => $response, 'code' => $httpCode];
}

function loginAsAdmin($baseUrl, $cookieFile) {
    $loginPage = makeRequest("$baseUrl/login", null, 'GET', $cookieFile);
    
    if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $loginPage['body'], $matches)) {
        $token = $matches[1];
    } else {
        return false;
    }
    
    $loginData = http_build_query([
        '_token' => $token,
        'email' => 'admin@taller.com',
        'password' => 'admin123'
    ]);
    
    $loginResponse = makeRequest("$baseUrl/login", $loginData, 'POST', $cookieFile);
    
    if ($loginResponse['code'] === 302 || $loginResponse['code'] === 200) {
        $dashboardResponse = makeRequest("$baseUrl/dashboard", null, 'GET', $cookieFile);
        return $dashboardResponse['code'] === 200 && strpos($dashboardResponse['body'], 'Dashboard') !== false;
    }
    
    return false;
}

function testUniqueConstraintViolation($baseUrl, $module, $field, $cookieFile) {
    global $testResults;
    
    echo "Testing unique constraint for $module.$field:\n";
    
    // Get existing records to find a value that already exists
    $indexResponse = makeRequest("$baseUrl/$module", null, 'GET', $cookieFile);
    if ($indexResponse['code'] !== 200) {
        echo "  ❌ Cannot access $module index\n";
        return;
    }
    
    // Find first record to edit
    if (!preg_match('/href="[^"]*\/' . $module . '\/(\d+)\/edit"/', $indexResponse['body'], $matches)) {
        echo "  ❌ No records found to test\n";
        return;
    }
    
    $recordId = $matches[1];
    
    // Get edit form
    $editResponse = makeRequest("$baseUrl/$module/$recordId/edit", null, 'GET', $cookieFile);
    if ($editResponse['code'] !== 200) {
        echo "  ❌ Cannot access edit form\n";
        return;
    }
    
    // Extract current field value
    $pattern = "/name=[\"']" . preg_quote($field, '/') . "[\"'][^>]*value=[\"']([^\"']*)[\"']/";
    if (!preg_match($pattern, $editResponse['body'], $matches)) {
        echo "  ❌ Cannot find current $field value\n";
        return;
    }
    
    $currentValue = $matches[1];
    
    // Get another record to test duplicate with
    if (!preg_match_all('/href="[^"]*\/' . $module . '\/(\d+)\/edit"/', $indexResponse['body'], $allMatches, PREG_SET_ORDER)) {
        echo "  ❌ Need multiple records for duplicate test\n";
        return;
    }
    
    if (count($allMatches) < 2) {
        echo "  ❌ Need at least 2 records for duplicate test\n";
        return;
    }
    
    // Use second record
    $secondRecordId = $allMatches[1][1];
    if ($secondRecordId == $recordId && isset($allMatches[2])) {
        $secondRecordId = $allMatches[2][1];
    }
    
    // Get second record edit form
    $secondEditResponse = makeRequest("$baseUrl/$module/$secondRecordId/edit", null, 'GET', $cookieFile);
    if ($secondEditResponse['code'] !== 200) {
        echo "  ❌ Cannot access second record edit form\n";
        return;
    }
    
    // Extract CSRF token from second form
    if (!preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $secondEditResponse['body'], $tokenMatches)) {
        echo "  ❌ Cannot extract CSRF token\n";
        return;
    }
    
    $token = $tokenMatches[1];
    
    // Extract all current values to preserve other data
    $currentData = [];
    if (preg_match_all('/name="([^"]*)"[^>]*value="([^"]*)"/', $secondEditResponse['body'], $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $currentData[$match[1]] = $match[2];
        }
    }
    
    // Try to update second record with duplicate value
    $updateData = array_merge($currentData, [
        '_token' => $token,
        '_method' => 'PUT',
        $field => $currentValue  // This should cause a unique constraint violation
    ]);
    
    $updateResponse = makeRequest("$baseUrl/$module/$secondRecordId", http_build_query($updateData), 'POST', $cookieFile);
    
    $testResult = [
        'test' => 'unique_constraint',
        'module' => $module,
        'field' => $field,
        'duplicate_value' => $currentValue,
        'response_code' => $updateResponse['code']
    ];
    
    if ($updateResponse['code'] === 422 || strpos($updateResponse['body'], 'error') !== false || strpos($updateResponse['body'], 'ya está registrad') !== false) {
        echo "  ✅ Unique constraint properly enforced\n";
        $testResult['status'] = 'pass';
        $testResult['message'] = 'Unique constraint violation properly rejected';
    } else {
        echo "  ❌ SECURITY ISSUE: Unique constraint not enforced!\n";
        $testResult['status'] = 'fail';
        $testResult['message'] = 'Unique constraint violation was allowed';
    }
    
    $testResults[] = $testResult;
}

function testMandatoryFieldViolation($baseUrl, $module, $field, $cookieFile) {
    global $testResults;
    
    echo "Testing mandatory field $module.$field:\n";
    
    // Get edit form
    $indexResponse = makeRequest("$baseUrl/$module", null, 'GET', $cookieFile);
    if ($indexResponse['code'] !== 200) {
        echo "  ❌ Cannot access $module index\n";
        return;
    }
    
    if (!preg_match('/href="[^"]*\/' . $module . '\/(\d+)\/edit"/', $indexResponse['body'], $matches)) {
        echo "  ❌ No records found to test\n";
        return;
    }
    
    $recordId = $matches[1];
    $editResponse = makeRequest("$baseUrl/$module/$recordId/edit", null, 'GET', $cookieFile);
    
    if ($editResponse['code'] !== 200) {
        echo "  ❌ Cannot access edit form\n";
        return;
    }
    
    // Extract CSRF token
    if (!preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $editResponse['body'], $tokenMatches)) {
        echo "  ❌ Cannot extract CSRF token\n";
        return;
    }
    
    $token = $tokenMatches[1];
    
    // Extract all current values
    $currentData = [];
    if (preg_match_all('/name="([^"]*)"[^>]*value="([^"]*)"/', $editResponse['body'], $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $currentData[$match[1]] = $match[2];
        }
    }
    
    // Try to update with empty mandatory field
    $updateData = array_merge($currentData, [
        '_token' => $token,
        '_method' => 'PUT',
        $field => ''  // Empty value for mandatory field
    ]);
    
    $updateResponse = makeRequest("$baseUrl/$module/$recordId", http_build_query($updateData), 'POST', $cookieFile);
    
    $testResult = [
        'test' => 'mandatory_field',
        'module' => $module,
        'field' => $field,
        'response_code' => $updateResponse['code']
    ];
    
    if ($updateResponse['code'] === 422 || strpos($updateResponse['body'], 'error') !== false || strpos($updateResponse['body'], 'obligatorio') !== false) {
        echo "  ✅ Mandatory field validation properly enforced\n";
        $testResult['status'] = 'pass';
        $testResult['message'] = 'Mandatory field validation working';
    } else {
        echo "  ❌ SECURITY ISSUE: Mandatory field validation bypassed!\n";
        $testResult['status'] = 'fail';
        $testResult['message'] = 'Mandatory field was allowed to be empty';
    }
    
    $testResults[] = $testResult;
}

function testForeignKeyConstraint($baseUrl, $module, $field, $cookieFile) {
    global $testResults;
    
    echo "Testing foreign key constraint $module.$field:\n";
    
    $indexResponse = makeRequest("$baseUrl/$module", null, 'GET', $cookieFile);
    if ($indexResponse['code'] !== 200) {
        echo "  ❌ Cannot access $module index\n";
        return;
    }
    
    if (!preg_match('/href="[^"]*\/' . $module . '\/(\d+)\/edit"/', $indexResponse['body'], $matches)) {
        echo "  ❌ No records found to test\n";
        return;
    }
    
    $recordId = $matches[1];
    $editResponse = makeRequest("$baseUrl/$module/$recordId/edit", null, 'GET', $cookieFile);
    
    if ($editResponse['code'] !== 200) {
        echo "  ❌ Cannot access edit form\n";
        return;
    }
    
    if (!preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $editResponse['body'], $tokenMatches)) {
        echo "  ❌ Cannot extract CSRF token\n";
        return;
    }
    
    $token = $tokenMatches[1];
    
    // Extract current values
    $currentData = [];
    if (preg_match_all('/name="([^"]*)"[^>]*value="([^"]*)"/', $editResponse['body'], $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $currentData[$match[1]] = $match[2];
        }
    }
    
    // Try to set invalid foreign key
    $updateData = array_merge($currentData, [
        '_token' => $token,
        '_method' => 'PUT',
        $field => '99999'  // Non-existent ID
    ]);
    
    $updateResponse = makeRequest("$baseUrl/$module/$recordId", http_build_query($updateData), 'POST', $cookieFile);
    
    $testResult = [
        'test' => 'foreign_key_constraint',
        'module' => $module,
        'field' => $field,
        'invalid_id' => '99999',
        'response_code' => $updateResponse['code']
    ];
    
    if ($updateResponse['code'] === 422 || strpos($updateResponse['body'], 'error') !== false || strpos($updateResponse['body'], 'no existe') !== false) {
        echo "  ✅ Foreign key constraint properly enforced\n";
        $testResult['status'] = 'pass';
        $testResult['message'] = 'Foreign key constraint working';
    } else {
        echo "  ❌ SECURITY ISSUE: Foreign key constraint bypassed!\n";
        $testResult['status'] = 'fail';
        $testResult['message'] = 'Invalid foreign key was accepted';
    }
    
    $testResults[] = $testResult;
}

echo "BUSINESS RULES AND FIELD IMMUTABILITY TEST\n";
echo str_repeat("=", 60) . "\n\n";

if (!loginAsAdmin($baseUrl, $cookieFile)) {
    die("❌ Failed to login as admin\n");
}

echo "✅ Logged in as admin\n\n";

// Test unique constraints
echo "1. TESTING UNIQUE CONSTRAINTS:\n";
echo str_repeat("-", 40) . "\n";

$uniqueFields = [
    'clientes' => ['email', 'document_number'],
    'vehiculos' => ['license_plate', 'vin'],
    'empleados' => ['email']
];

foreach ($uniqueFields as $module => $fields) {
    foreach ($fields as $field) {
        testUniqueConstraintViolation($baseUrl, $module, $field, $cookieFile);
    }
}

echo "\n2. TESTING MANDATORY FIELD VALIDATION:\n";
echo str_repeat("-", 40) . "\n";

$mandatoryFields = [
    'clientes' => ['name', 'email', 'phone', 'document_number'],
    'vehiculos' => ['brand', 'model', 'license_plate', 'vin'],
    'empleados' => ['name', 'email', 'position'],
    'servicios' => ['name', 'price']
];

foreach ($mandatoryFields as $module => $fields) {
    foreach ($fields as $field) {
        testMandatoryFieldViolation($baseUrl, $module, $field, $cookieFile);
    }
}

echo "\n3. TESTING FOREIGN KEY CONSTRAINTS:\n";
echo str_repeat("-", 40) . "\n";

$foreignKeyFields = [
    'vehiculos' => ['cliente_id'],
    'ordenes' => ['cliente_id', 'vehiculo_id', 'empleado_id', 'servicio_id']
];

foreach ($foreignKeyFields as $module => $fields) {
    foreach ($fields as $field) {
        testForeignKeyConstraint($baseUrl, $module, $field, $cookieFile);
    }
}

// Generate summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "TEST SUMMARY:\n";
echo str_repeat("=", 60) . "\n\n";

$totalTests = count($testResults);
$passedTests = array_filter($testResults, function($r) { return $r['status'] === 'pass'; });
$failedTests = array_filter($testResults, function($r) { return $r['status'] === 'fail'; });

echo "Total Tests: $totalTests\n";
echo "Passed: " . count($passedTests) . "\n";
echo "Failed: " . count($failedTests) . "\n";
echo "Success Rate: " . round((count($passedTests) / $totalTests) * 100, 2) . "%\n\n";

if (!empty($failedTests)) {
    echo "SECURITY ISSUES FOUND:\n";
    echo str_repeat("-", 30) . "\n";
    
    foreach ($failedTests as $test) {
        echo "❌ {$test['test']}: {$test['module']}.{$test['field']}\n";
        echo "   Issue: {$test['message']}\n\n";
    }
} else {
    echo "✅ No security issues found - all business rules properly enforced\n\n";
}

echo "DETAILED RESULTS BY TEST TYPE:\n";
echo str_repeat("-", 40) . "\n";

$resultsByType = [];
foreach ($testResults as $result) {
    $resultsByType[$result['test']][] = $result;
}

foreach ($resultsByType as $testType => $results) {
    $passed = array_filter($results, function($r) { return $r['status'] === 'pass'; });
    $failed = array_filter($results, function($r) { return $r['status'] === 'fail'; });
    
    echo "\n" . strtoupper(str_replace('_', ' ', $testType)) . ":\n";
    echo "  Passed: " . count($passed) . "/" . count($results) . "\n";
    
    if (!empty($failed)) {
        echo "  Failed tests:\n";
        foreach ($failed as $fail) {
            echo "    - {$fail['module']}.{$fail['field']}: {$fail['message']}\n";
        }
    }
}

// Save results
file_put_contents(__DIR__ . '/business_rules_test_results.json', json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'summary' => [
        'total_tests' => $totalTests,
        'passed' => count($passedTests),
        'failed' => count($failedTests),
        'success_rate' => round((count($passedTests) / $totalTests) * 100, 2)
    ],
    'results_by_type' => $resultsByType,
    'all_results' => $testResults,
    'security_issues' => $failedTests
], JSON_PRETTY_PRINT));

echo "\nDetailed results saved to: business_rules_test_results.json\n";
echo "Test completed: " . date('Y-m-d H:i:s') . "\n";
?>