<?php

/**
 * INTEGRATION TEST DEBUG - MODEL-CONTROLLER-VIEW
 * Testing delete button and profile page issues
 * User: admin@taller.com / admin123
 * Date: 2025-07-03
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseUrl = 'http://localhost:8003';
$cookieFile = '/tmp/integration_test_cookies.txt';

// Test results storage
$testResults = [
    'authentication' => [],
    'delete_button' => [],
    'profile_page' => [],
    'model_operations' => [],
    'controller_responses' => [],
    'view_rendering' => [],
    'javascript_functionality' => [],
    'summary' => []
];

function makeCurlRequest($url, $method = 'GET', $data = null, $cookieFile = null, $headers = [], $followRedirects = true) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirects);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Separate headers and body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    return [
        'response' => $response,
        'body' => $body,
        'headers' => $headers,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

function extractCsrfToken($html) {
    // Try multiple patterns for CSRF token
    $patterns = [
        '/<input[^>]*name="_token"[^>]*value="([^"]*)"/',
        '/<meta name="csrf-token" content="([^"]*)"/',
        '/"_token":"([^"]*)"/',
        '/csrf_token\(\)\s*:\s*[\'"]([^\'"]*)[\'"]/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function loginAndGetToken($email, $password) {
    global $baseUrl, $cookieFile, $testResults;
    
    // Clean cookies
    if (file_exists($cookieFile)) unlink($cookieFile);
    
    echo "=== AUTHENTICATION TEST ===\n";
    
    // Step 1: Get login page
    echo "1. Getting login page...\n";
    $loginPage = makeCurlRequest("$baseUrl/login", 'GET', null, $cookieFile);
    $testResults['authentication']['login_page_access'] = $loginPage['http_code'] === 200 ? 'PASS' : 'FAIL';
    echo "Login page HTTP: {$loginPage['http_code']}\n";
    
    // Extract CSRF token
    $csrfToken = extractCsrfToken($loginPage['body']);
    $testResults['authentication']['csrf_token_found'] = $csrfToken ? 'PASS' : 'FAIL';
    
    if (!$csrfToken) {
        echo "❌ No CSRF token found\n";
        return false;
    }
    echo "✅ CSRF token found: " . substr($csrfToken, 0, 20) . "...\n";
    
    // Step 2: Login
    echo "2. Attempting login...\n";
    $loginData = http_build_query([
        '_token' => $csrfToken,
        'email' => $email,
        'password' => $password
    ]);
    
    $loginResponse = makeCurlRequest("$baseUrl/login", 'POST', $loginData, $cookieFile, [
        'Content-Type: application/x-www-form-urlencoded'
    ], false); // Don't follow redirects
    
    $testResults['authentication']['login_attempt'] = $loginResponse['http_code'] === 302 ? 'PASS' : 'FAIL';
    echo "Login HTTP: {$loginResponse['http_code']}\n";
    
    if ($loginResponse['http_code'] === 302) {
        echo "✅ Login successful (redirected)\n";
        
        // Step 3: Verify dashboard access
        echo "3. Verifying dashboard access...\n";
        $dashboard = makeCurlRequest("$baseUrl/dashboard", 'GET', null, $cookieFile);
        $testResults['authentication']['dashboard_access'] = $dashboard['http_code'] === 200 ? 'PASS' : 'FAIL';
        echo "Dashboard HTTP: {$dashboard['http_code']}\n";
        
        if ($dashboard['http_code'] === 200) {
            echo "✅ Authentication fully verified\n";
            return extractCsrfToken($dashboard['body']);
        }
    }
    
    echo "❌ Login failed\n";
    return false;
}

function testDeleteFunctionality($csrfToken) {
    global $baseUrl, $cookieFile, $testResults;
    
    echo "\n=== DELETE BUTTON TEST ===\n";
    
    // Step 1: Access clientes index
    echo "1. Accessing clientes index...\n";
    $clientesIndex = makeCurlRequest("$baseUrl/clientes", 'GET', null, $cookieFile);
    $testResults['delete_button']['clientes_access'] = $clientesIndex['http_code'] === 200 ? 'PASS' : 'FAIL';
    echo "Clientes index HTTP: {$clientesIndex['http_code']}\n";
    
    if ($clientesIndex['http_code'] !== 200) {
        echo "❌ Cannot access clientes module\n";
        return;
    }
    
    // Step 2: Check if delete buttons exist
    $deleteButtonCount = preg_match_all('/btn-delete|fas fa-trash/', $clientesIndex['body'], $matches);
    $testResults['delete_button']['delete_buttons_found'] = $deleteButtonCount > 0 ? 'PASS' : 'FAIL';
    echo "Delete buttons found: $deleteButtonCount\n";
    
    // Step 3: Extract first cliente ID for testing
    if (preg_match('/\/clientes\/(\d+)/', $clientesIndex['body'], $matches)) {
        $clienteId = $matches[1];
        echo "Found cliente ID for testing: $clienteId\n";
        
        // Step 4: Test delete operation
        echo "2. Testing delete operation...\n";
        $deleteData = http_build_query([
            '_token' => $csrfToken,
            '_method' => 'DELETE'
        ]);
        
        $deleteResponse = makeCurlRequest("$baseUrl/clientes/$clienteId", 'POST', $deleteData, $cookieFile, [
            'Content-Type: application/x-www-form-urlencoded',
            'X-Requested-With: XMLHttpRequest'
        ], false);
        
        echo "Delete response HTTP: {$deleteResponse['http_code']}\n";
        echo "Delete response headers: " . substr($deleteResponse['headers'], 0, 500) . "\n";
        echo "Delete response body: " . substr($deleteResponse['body'], 0, 500) . "...\n";
        
        $testResults['delete_button']['delete_operation'] = in_array($deleteResponse['http_code'], [200, 302]) ? 'PASS' : 'FAIL';
        
        // Step 5: Check if item was actually deleted
        echo "3. Verifying deletion...\n";
        $verifyResponse = makeCurlRequest("$baseUrl/clientes/$clienteId", 'GET', null, $cookieFile);
        $testResults['delete_button']['deletion_verified'] = $verifyResponse['http_code'] === 404 ? 'PASS' : 'FAIL';
        echo "Verification HTTP: {$verifyResponse['http_code']}\n";
        
    } else {
        echo "❌ No cliente IDs found for testing\n";
        $testResults['delete_button']['cliente_id_found'] = 'FAIL';
    }
    
    // Step 6: Check JavaScript functionality
    echo "4. Checking JavaScript delete confirmation...\n";
    $hasConfirmDialog = strpos($clientesIndex['body'], 'confirm(') !== false || 
                       strpos($clientesIndex['body'], 'Swal.fire') !== false ||
                       strpos($clientesIndex['body'], 'sweetAlert') !== false;
    $testResults['delete_button']['js_confirmation'] = $hasConfirmDialog ? 'PASS' : 'FAIL';
    echo "JavaScript confirmation found: " . ($hasConfirmDialog ? 'YES' : 'NO') . "\n";
}

function testProfilePage($csrfToken) {
    global $baseUrl, $cookieFile, $testResults;
    
    echo "\n=== PROFILE PAGE TEST ===\n";
    
    // Step 1: Access profile page
    echo "1. Accessing profile page...\n";
    $profileResponse = makeCurlRequest("$baseUrl/profile", 'GET', null, $cookieFile);
    $testResults['profile_page']['profile_access'] = $profileResponse['http_code'] === 200 ? 'PASS' : 'FAIL';
    echo "Profile page HTTP: {$profileResponse['http_code']}\n";
    
    if ($profileResponse['http_code'] === 200) {
        echo "✅ Profile page accessible\n";
        
        // Step 2: Check content elements
        $contentChecks = [
            'user_form' => strpos($profileResponse['body'], 'name="name"') !== false,
            'email_form' => strpos($profileResponse['body'], 'name="email"') !== false,
            'password_form' => strpos($profileResponse['body'], 'name="password"') !== false,
            'delete_account' => strpos($profileResponse['body'], 'delete') !== false && strpos($profileResponse['body'], 'account') !== false,
            'csrf_token' => extractCsrfToken($profileResponse['body']) !== null
        ];
        
        foreach ($contentChecks as $check => $result) {
            $testResults['profile_page'][$check] = $result ? 'PASS' : 'FAIL';
            echo "$check: " . ($result ? 'FOUND' : 'MISSING') . "\n";
        }
        
        // Step 3: Test profile update
        echo "2. Testing profile update...\n";
        $updateData = http_build_query([
            '_token' => $csrfToken,
            '_method' => 'PATCH',
            'name' => 'Test Admin Updated',
            'email' => 'admin@taller.com'
        ]);
        
        $updateResponse = makeCurlRequest("$baseUrl/profile", 'POST', $updateData, $cookieFile, [
            'Content-Type: application/x-www-form-urlencoded'
        ], false);
        
        $testResults['profile_page']['profile_update'] = in_array($updateResponse['http_code'], [200, 302]) ? 'PASS' : 'FAIL';
        echo "Profile update HTTP: {$updateResponse['http_code']}\n";
        
    } else if ($profileResponse['http_code'] === 302) {
        echo "❌ Profile page redirected (authentication issue)\n";
        echo "Redirect location: " . (preg_match('/Location: (.+)/i', $profileResponse['headers'], $matches) ? trim($matches[1]) : 'Unknown') . "\n";
    } else {
        echo "❌ Profile page error: {$profileResponse['http_code']}\n";
    }
}

function testModelControllerView() {
    global $testResults;
    
    echo "\n=== MODEL-CONTROLLER-VIEW INTEGRATION TEST ===\n";
    
    // Test 1: Check if models exist and are accessible
    echo "1. Testing model layer...\n";
    exec('php artisan tinker --execute="echo \'Cliente count: \' . App\\\\Models\\\\Cliente::count();"', $output1, $return1);
    $testResults['model_operations']['cliente_model'] = $return1 === 0 ? 'PASS' : 'FAIL';
    echo "Cliente model: " . ($return1 === 0 ? 'ACCESSIBLE' : 'ERROR') . "\n";
    if ($return1 === 0 && !empty($output1)) {
        echo "Output: " . implode(', ', $output1) . "\n";
    }
    
    // Test 2: Check route registration
    echo "2. Testing route layer...\n";
    exec('php artisan route:list --path=clientes 2>/dev/null', $output2, $return2);
    $testResults['controller_responses']['routes_registered'] = $return2 === 0 ? 'PASS' : 'FAIL';
    echo "Clientes routes: " . ($return2 === 0 ? 'REGISTERED' : 'ERROR') . "\n";
    
    // Test 3: Check view files exist
    echo "3. Testing view layer...\n";
    $viewFiles = [
        '/mnt/c/Users/lukka/taller-sistema/resources/views/clientes/index.blade.php',
        '/mnt/c/Users/lukka/taller-sistema/resources/views/profile/edit.blade.php',
        '/mnt/c/Users/lukka/taller-sistema/resources/views/layouts/app.blade.php'
    ];
    
    foreach ($viewFiles as $file) {
        $exists = file_exists($file);
        $viewName = basename(dirname($file)) . '/' . basename($file);
        $testResults['view_rendering'][$viewName] = $exists ? 'PASS' : 'FAIL';
        echo "$viewName: " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
    }
}

function generateReport() {
    global $testResults;
    
    echo "\n=== INTEGRATION TEST REPORT ===\n";
    
    $totalTests = 0;
    $passedTests = 0;
    
    foreach ($testResults as $category => $tests) {
        if ($category === 'summary') continue;
        
        echo "\n" . strtoupper(str_replace('_', ' ', $category)) . ":\n";
        foreach ($tests as $test => $result) {
            echo "  $test: $result\n";
            $totalTests++;
            if ($result === 'PASS') $passedTests++;
        }
    }
    
    $successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
    
    $testResults['summary'] = [
        'total_tests' => $totalTests,
        'passed' => $passedTests,
        'failed' => $totalTests - $passedTests,
        'success_rate' => $successRate
    ];
    
    echo "\nSUMMARY:\n";
    echo "  Total Tests: $totalTests\n";
    echo "  Passed: $passedTests\n";
    echo "  Failed: " . ($totalTests - $passedTests) . "\n";
    echo "  Success Rate: $successRate%\n";
    
    // Save results to JSON
    file_put_contents('/mnt/c/Users/lukka/taller-sistema/integration_test_results.json', 
                      json_encode($testResults, JSON_PRETTY_PRINT));
    
    echo "\nResults saved to: integration_test_results.json\n";
}

// Main execution
echo "STARTING INTEGRATION TESTS FOR DELETE BUTTON AND PROFILE PAGE\n";
echo "Testing with user: admin@taller.com\n\n";

// Step 1: Login and get authenticated session
$csrfToken = loginAndGetToken('admin@taller.com', 'admin123');

if ($csrfToken) {
    // Step 2: Test delete functionality
    testDeleteFunctionality($csrfToken);
    
    // Step 3: Test profile page
    testProfilePage($csrfToken);
    
    // Step 4: Test model-controller-view integration
    testModelControllerView();
    
    // Step 5: Generate report
    generateReport();
} else {
    echo "❌ Cannot proceed with tests - authentication failed\n";
}

// Cleanup
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "\nINTEGRATION TESTS COMPLETED\n";

?>