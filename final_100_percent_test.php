<?php

/**
 * FINAL 100% SUCCESS TEST
 * Testing all fixes with clean test data
 * Target: 100% success rate (21/21 tests)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseUrl = 'http://localhost:8003';
$cookieFile = '/tmp/final_100_test_cookies.txt';

$testResults = [
    'authentication' => [],
    'delete_operations' => [],
    'profile_functionality' => [],
    'model_integration' => [],
    'error_handling' => [],
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
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    
    return ['response' => $response, 'body' => $body, 'headers' => $headers, 'http_code' => $httpCode];
}

function extractCsrfToken($html) {
    $patterns = [
        '/<input[^>]*name="_token"[^>]*value="([^"]*)"/',
        '/<meta name="csrf-token" content="([^"]*)"/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function loginUser($email, $password) {
    global $baseUrl, $cookieFile, $testResults;
    
    if (file_exists($cookieFile)) unlink($cookieFile);
    
    echo "🔐 Authenticating user: $email\n";
    
    $loginPage = makeCurlRequest("$baseUrl/login", 'GET', null, $cookieFile);
    $testResults['authentication']['login_page_access'] = $loginPage['http_code'] === 200 ? 'PASS' : 'FAIL';
    
    $csrfToken = extractCsrfToken($loginPage['body']);
    $testResults['authentication']['csrf_token_found'] = $csrfToken ? 'PASS' : 'FAIL';
    
    if (!$csrfToken) return false;
    
    $loginData = http_build_query([
        '_token' => $csrfToken,
        'email' => $email,
        'password' => $password
    ]);
    
    $loginResponse = makeCurlRequest("$baseUrl/login", 'POST', $loginData, $cookieFile, [
        'Content-Type: application/x-www-form-urlencoded'
    ], false);
    
    $testResults['authentication']['login_success'] = $loginResponse['http_code'] === 302 ? 'PASS' : 'FAIL';
    
    // Verify dashboard access
    $dashboard = makeCurlRequest("$baseUrl/dashboard", 'GET', null, $cookieFile);
    $testResults['authentication']['dashboard_access'] = $dashboard['http_code'] === 200 ? 'PASS' : 'FAIL';
    
    return $loginResponse['http_code'] === 302;
}

function testProfileFunctionality() {
    global $baseUrl, $cookieFile, $testResults;
    
    echo "\n📄 Testing Profile Functionality...\n";
    
    $profileResponse = makeCurlRequest("$baseUrl/profile", 'GET', null, $cookieFile);
    $testResults['profile_functionality']['profile_access'] = $profileResponse['http_code'] === 200 ? 'PASS' : 'FAIL';
    
    if ($profileResponse['http_code'] === 200) {
        // Enhanced checks for Bootstrap implementation
        $checks = [
            'name_input' => strpos($profileResponse['body'], 'name="name"') !== false,
            'email_input' => strpos($profileResponse['body'], 'name="email"') !== false,
            'password_input' => strpos($profileResponse['body'], 'name="password"') !== false,
            'current_password_input' => strpos($profileResponse['body'], 'name="current_password"') !== false,
            'password_confirmation' => strpos($profileResponse['body'], 'name="password_confirmation"') !== false,
            'bootstrap_forms' => strpos($profileResponse['body'], 'form-control') !== false,
            'bootstrap_cards' => strpos($profileResponse['body'], 'card-header') !== false,
            'profile_update_route' => strpos($profileResponse['body'], 'profile.update') !== false,
            'password_update_route' => strpos($profileResponse['body'], 'password.update') !== false,
            'profile_destroy_route' => strpos($profileResponse['body'], 'profile.destroy') !== false,
            'csrf_protection' => extractCsrfToken($profileResponse['body']) !== null,
            'delete_modal' => strpos($profileResponse['body'], 'deleteAccountModal') !== false
        ];
        
        foreach ($checks as $check => $result) {
            $testResults['profile_functionality'][$check] = $result ? 'PASS' : 'FAIL';
            echo "  ✓ $check: " . ($result ? 'FOUND' : 'MISSING') . "\n";
        }
    } else {
        echo "  ❌ Profile page not accessible (HTTP {$profileResponse['http_code']})\n";
    }
}

function testDeleteOperations() {
    global $baseUrl, $cookieFile, $testResults;
    
    echo "\n🗑️ Testing Delete Operations...\n";
    
    $clientesResponse = makeCurlRequest("$baseUrl/clientes", 'GET', null, $cookieFile);
    $testResults['delete_operations']['clientes_access'] = $clientesResponse['http_code'] === 200 ? 'PASS' : 'FAIL';
    
    // Check delete buttons exist
    $deleteButtonCount = preg_match_all('/btn-delete|fa-trash/', $clientesResponse['body'], $matches);
    $testResults['delete_operations']['delete_buttons_found'] = $deleteButtonCount > 0 ? 'PASS' : 'FAIL';
    echo "  ✓ Delete buttons found: $deleteButtonCount\n";
    
    // Test with clean client (should succeed)
    if (preg_match('/delete-test@example\.com.*?\/clientes\/(\d+)/', $clientesResponse['body'], $matches)) {
        $cleanClientId = $matches[1];
        echo "  ✓ Found clean test client ID: $cleanClientId\n";
        
        $csrfToken = extractCsrfToken($clientesResponse['body']);
        if ($csrfToken) {
            $deleteData = http_build_query([
                '_token' => $csrfToken,
                '_method' => 'DELETE'
            ]);
            
            $deleteResponse = makeCurlRequest("$baseUrl/clientes/$cleanClientId", 'POST', $deleteData, $cookieFile, [
                'Content-Type: application/x-www-form-urlencoded',
                'X-Requested-With: XMLHttpRequest'
            ], false);
            
            echo "  ✓ Clean delete response: HTTP {$deleteResponse['http_code']}\n";
            
            // Should succeed (200) or soft delete (200)
            $testResults['delete_operations']['clean_delete_success'] = in_array($deleteResponse['http_code'], [200, 302]) ? 'PASS' : 'FAIL';
            
            if ($deleteResponse['http_code'] === 200) {
                $responseData = json_decode($deleteResponse['body'], true);
                $testResults['delete_operations']['delete_response_valid'] = isset($responseData['success']) ? 'PASS' : 'FAIL';
                echo "  ✓ Delete response format: " . (isset($responseData['success']) ? 'VALID' : 'INVALID') . "\n";
            }
        }
    } else {
        echo "  ❌ Clean test client not found\n";
        $testResults['delete_operations']['clean_client_found'] = 'FAIL';
    }
    
    // Test with client that has orders (should fail with 422)
    if (preg_match('/\/clientes\/(\d+)/', $clientesResponse['body'], $matches)) {
        $clientWithOrders = $matches[1];
        if ($clientWithOrders != ($cleanClientId ?? 0)) {
            echo "  ✓ Testing error handling with client ID: $clientWithOrders\n";
            
            $csrfToken = extractCsrfToken($clientesResponse['body']);
            if ($csrfToken) {
                $deleteData = http_build_query([
                    '_token' => $csrfToken,
                    '_method' => 'DELETE'
                ]);
                
                $errorResponse = makeCurlRequest("$baseUrl/clientes/$clientWithOrders", 'POST', $deleteData, $cookieFile, [
                    'Content-Type: application/x-www-form-urlencoded',
                    'X-Requested-With: XMLHttpRequest'
                ], false);
                
                echo "  ✓ Error handling response: HTTP {$errorResponse['http_code']}\n";
                $testResults['delete_operations']['error_handling_422'] = $errorResponse['http_code'] === 422 ? 'PASS' : 'FAIL';
                
                if ($errorResponse['http_code'] === 422) {
                    $errorData = json_decode($errorResponse['body'], true);
                    $testResults['delete_operations']['error_message_present'] = isset($errorData['message']) ? 'PASS' : 'FAIL';
                    echo "  ✓ Error message: " . ($errorData['message'] ?? 'None') . "\n";
                }
            }
        }
    }
    
    // Test JavaScript confirmation
    $hasConfirmation = strpos($clientesResponse['body'], 'confirm') !== false || 
                      strpos($clientesResponse['body'], 'Swal.fire') !== false;
    $testResults['delete_operations']['js_confirmation'] = $hasConfirmation ? 'PASS' : 'FAIL';
    echo "  ✓ JavaScript confirmation: " . ($hasConfirmation ? 'FOUND' : 'MISSING') . "\n";
}

function testModelIntegration() {
    global $testResults;
    
    echo "\n🏗️ Testing Model Integration...\n";
    
    // Test model accessibility
    exec('php artisan tinker --execute="echo App\\\\Models\\\\Cliente::count();"', $output, $return);
    $testResults['model_integration']['cliente_model_access'] = $return === 0 ? 'PASS' : 'FAIL';
    echo "  ✓ Cliente model: " . ($return === 0 ? 'ACCESSIBLE' : 'ERROR') . "\n";
    
    // Test routes
    exec('php artisan route:list --path=clientes --compact', $output2, $return2);
    $testResults['model_integration']['routes_registered'] = $return2 === 0 ? 'PASS' : 'FAIL';
    echo "  ✓ Routes: " . ($return2 === 0 ? 'REGISTERED' : 'ERROR') . "\n";
    
    // Test views exist
    $views = [
        'clientes/index.blade.php' => file_exists('/mnt/c/Users/lukka/taller-sistema/resources/views/clientes/index.blade.php'),
        'profile/edit.blade.php' => file_exists('/mnt/c/Users/lukka/taller-sistema/resources/views/profile/edit.blade.php'),
        'layouts/app.blade.php' => file_exists('/mnt/c/Users/lukka/taller-sistema/resources/views/layouts/app.blade.php')
    ];
    
    foreach ($views as $view => $exists) {
        $testResults['model_integration'][$view] = $exists ? 'PASS' : 'FAIL';
        echo "  ✓ $view: " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
    }
}

function generateFinalReport() {
    global $testResults;
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎯 FINAL 100% SUCCESS RATE TEST REPORT\n";
    echo str_repeat("=", 60) . "\n";
    
    $totalTests = 0;
    $passedTests = 0;
    $categories = [];
    
    foreach ($testResults as $category => $tests) {
        if ($category === 'summary') continue;
        
        $categoryPassed = 0;
        $categoryTotal = 0;
        
        echo "\n📊 " . strtoupper(str_replace('_', ' ', $category)) . ":\n";
        foreach ($tests as $test => $result) {
            echo "   $test: $result\n";
            $categoryTotal++;
            $totalTests++;
            if ($result === 'PASS') {
                $categoryPassed++;
                $passedTests++;
            }
        }
        
        $categoryRate = $categoryTotal > 0 ? round(($categoryPassed / $categoryTotal) * 100, 1) : 0;
        $categories[$category] = $categoryRate;
        echo "   → Category Success Rate: $categoryRate% ($categoryPassed/$categoryTotal)\n";
    }
    
    $successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
    
    $testResults['summary'] = [
        'total_tests' => $totalTests,
        'passed' => $passedTests,
        'failed' => $totalTests - $passedTests,
        'success_rate' => $successRate,
        'categories' => $categories
    ];
    
    echo "\n🏆 OVERALL RESULTS:\n";
    echo "   Total Tests: $totalTests\n";
    echo "   Passed: $passedTests\n";
    echo "   Failed: " . ($totalTests - $passedTests) . "\n";
    echo "   SUCCESS RATE: $successRate%\n";
    
    if ($successRate >= 100) {
        echo "\n🎉 PERFECT SCORE ACHIEVED! 🎉\n";
    } elseif ($successRate >= 95) {
        echo "\n✨ EXCELLENT RESULTS! ✨\n";
    } elseif ($successRate >= 90) {
        echo "\n🚀 GREAT IMPROVEMENT! 🚀\n";
    }
    
    // Save results
    file_put_contents('/mnt/c/Users/lukka/taller-sistema/final_100_percent_results.json', 
                      json_encode($testResults, JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to: final_100_percent_results.json\n";
}

// MAIN EXECUTION
echo "🚀 STARTING FINAL 100% SUCCESS RATE TEST\n";
echo "Target: Perfect score (100% success rate)\n";
echo "User: admin@taller.com\n\n";

if (loginUser('admin@taller.com', 'admin123')) {
    testProfileFunctionality();
    testDeleteOperations();
    testModelIntegration();
    generateFinalReport();
} else {
    echo "❌ Authentication failed - cannot proceed\n";
    exit(1);
}

// Cleanup
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "\n✅ FINAL TEST COMPLETED\n";

?>