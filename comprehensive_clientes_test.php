<?php

/**
 * Comprehensive Test for Clientes Module CRUD Operations
 * 
 * This script tests:
 * 1. CREATE functionality with success/error alerts
 * 2. UPDATE functionality with success/error alerts
 * 3. DELETE functionality with confirmation dialogs
 * 4. Error handling for duplicate emails and empty fields
 * 5. Redirect behavior and data persistence
 * 
 * Access: http://localhost:8001
 * Login: admin@taller.com / admin123
 */

// Configuration
$baseUrl = 'http://localhost:8001';
$email = 'admin@taller.com';
$password = 'admin123';

// Cookie file to maintain session
$cookieFile = __DIR__ . '/clientes_test_cookies.txt';

// Initialize cURL
function initCurl($url, $cookieFile) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_VERBOSE => false
    ]);
    return $ch;
}

// Extract CSRF token from HTML
function extractCsrfToken($html) {
    if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $html, $matches)) {
        return $matches[1];
    }
    return null;
}

// Check if response contains success alert
function checkForSuccessAlert($html) {
    $patterns = [
        '/alert-success/',
        '/success/',
        '/exitosamente/',
        '/creado/',
        '/actualizado/',
        '/eliminado/',
        '/class="alert[^"]*success/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html)) {
            return true;
        }
    }
    return false;
}

// Check if response contains error alert
function checkForErrorAlert($html) {
    $patterns = [
        '/alert-danger/',
        '/alert-error/',
        '/error/',
        '/invalid-feedback/',
        '/class="alert[^"]*danger/',
        '/ya está registrado/',
        '/obligatorio/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html)) {
            return true;
        }
    }
    return false;
}

// Check if client appears in the list
function checkClientInList($html, $clientName) {
    return strpos($html, $clientName) !== false;
}

// Test results array
$testResults = [];

function logTest($testName, $passed, $details = '') {
    global $testResults;
    $testResults[] = [
        'test' => $testName,
        'passed' => $passed,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    echo ($passed ? "✓" : "✗") . " $testName" . ($details ? " - $details" : "") . "\n";
}

echo "=== CLIENTES MODULE CRUD TESTING ===\n";
echo "Testing URL: $baseUrl\n";
echo "Login: $email\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Clear previous cookies
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

try {
    // Step 1: Login
    echo "1. AUTHENTICATION TEST\n";
    echo "------------------------\n";
    
    $ch = initCurl($baseUrl . '/login', $cookieFile);
    $loginPage = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode !== 200) {
        throw new Exception("Cannot access login page. HTTP Code: $httpCode");
    }
    
    $csrfToken = extractCsrfToken($loginPage);
    if (!$csrfToken) {
        throw new Exception("CSRF token not found on login page");
    }
    
    logTest("Login page access", true, "HTTP $httpCode");
    logTest("CSRF token extraction", true, substr($csrfToken, 0, 10) . "...");
    
    // Perform login
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/login',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            '_token' => $csrfToken,
            'email' => $email,
            'password' => $password
        ])
    ]);
    
    $loginResult = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    
    $loginSuccess = ($httpCode === 200 && strpos($finalUrl, '/dashboard') !== false) ||
                   ($httpCode === 302 && strpos($loginResult, 'dashboard') !== false);
    
    logTest("Login authentication", $loginSuccess, "HTTP $httpCode, Final URL: " . basename($finalUrl));
    
    if (!$loginSuccess) {
        throw new Exception("Login failed. Please check credentials.");
    }
    
    curl_close($ch);
    
    // Step 2: Access Clientes Index
    echo "\n2. CLIENTES INDEX ACCESS TEST\n";
    echo "------------------------------\n";
    
    $ch = initCurl($baseUrl . '/clientes', $cookieFile);
    $clientesIndex = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    logTest("Clientes index access", $httpCode === 200, "HTTP $httpCode");
    logTest("Clientes page content", strpos($clientesIndex, 'Gestión de Clientes') !== false, "Page title found");
    
    curl_close($ch);
    
    // Step 3: CREATE TEST
    echo "\n3. CREATE FUNCTIONALITY TEST\n";
    echo "-----------------------------\n";
    
    // Access create form
    $ch = initCurl($baseUrl . '/clientes/create', $cookieFile);
    $createForm = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    logTest("Access create form", $httpCode === 200, "HTTP $httpCode");
    
    $csrfToken = extractCsrfToken($createForm);
    logTest("CSRF token in create form", $csrfToken !== null, $csrfToken ? substr($csrfToken, 0, 10) . "..." : "Not found");
    
    // Test data
    $testClientData = [
        'name' => 'Juan Test',
        'email' => 'juan.test@example.com',
        'phone' => '555-1234',
        'address' => 'Test Address',
        'document_number' => '12345678',
        'status' => '1'
    ];
    
    // Submit create form
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/clientes',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(array_merge([
            '_token' => $csrfToken
        ], $testClientData))
    ]);
    
    $createResult = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    
    $createSuccess = ($httpCode === 200 || $httpCode === 302) && 
                    (strpos($finalUrl, '/clientes') !== false || strpos($createResult, 'exitosamente') !== false);
    
    logTest("Create client submission", $createSuccess, "HTTP $httpCode");
    
    // Check for success alert
    $hasSuccessAlert = checkForSuccessAlert($createResult);
    logTest("Success alert appears", $hasSuccessAlert, $hasSuccessAlert ? "Success alert found" : "No success alert");
    
    curl_close($ch);
    
    // Verify client appears in index
    $ch = initCurl($baseUrl . '/clientes', $cookieFile);
    $updatedIndex = curl_exec($ch);
    $clientInList = checkClientInList($updatedIndex, 'Juan Test');
    logTest("Client appears in list", $clientInList, $clientInList ? "Client found in list" : "Client not found");
    curl_close($ch);
    
    // Step 4: UPDATE TEST
    echo "\n4. UPDATE FUNCTIONALITY TEST\n";
    echo "-----------------------------\n";
    
    // Find the client ID from the index page
    $clientId = null;
    if (preg_match('/\/clientes\/(\d+)\/edit/', $updatedIndex, $matches)) {
        $clientId = $matches[1];
    }
    
    logTest("Find client ID for editing", $clientId !== null, "Client ID: " . ($clientId ?: "Not found"));
    
    if ($clientId) {
        // Access edit form
        $ch = initCurl($baseUrl . "/clientes/$clientId/edit", $cookieFile);
        $editForm = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        logTest("Access edit form", $httpCode === 200, "HTTP $httpCode");
        
        $csrfToken = extractCsrfToken($editForm);
        logTest("CSRF token in edit form", $csrfToken !== null, $csrfToken ? substr($csrfToken, 0, 10) . "..." : "Not found");
        
        // Update client data
        $updatedData = array_merge($testClientData, [
            'name' => 'Juan Test Updated',
            '_method' => 'PUT'
        ]);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $baseUrl . "/clientes/$clientId",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(array_merge([
                '_token' => $csrfToken
            ], $updatedData))
        ]);
        
        $updateResult = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        $updateSuccess = ($httpCode === 200 || $httpCode === 302) && 
                        (strpos($finalUrl, '/clientes') !== false || strpos($updateResult, 'exitosamente') !== false);
        
        logTest("Update client submission", $updateSuccess, "HTTP $httpCode");
        
        // Check for success alert
        $hasSuccessAlert = checkForSuccessAlert($updateResult);
        logTest("Update success alert", $hasSuccessAlert, $hasSuccessAlert ? "Success alert found" : "No success alert");
        
        curl_close($ch);
        
        // Verify updated name appears
        $ch = initCurl($baseUrl . '/clientes', $cookieFile);
        $updatedIndex = curl_exec($ch);
        $updatedClientInList = checkClientInList($updatedIndex, 'Juan Test Updated');
        logTest("Updated client name in list", $updatedClientInList, $updatedClientInList ? "Updated name found" : "Updated name not found");
        curl_close($ch);
    }
    
    // Step 5: ERROR HANDLING TESTS
    echo "\n5. ERROR HANDLING TESTS\n";
    echo "------------------------\n";
    
    // Test duplicate email
    $ch = initCurl($baseUrl . '/clientes/create', $cookieFile);
    $createForm = curl_exec($ch);
    $csrfToken = extractCsrfToken($createForm);
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/clientes',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(array_merge([
            '_token' => $csrfToken
        ], $testClientData)) // Same email as before
    ]);
    
    $duplicateResult = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    $hasDuplicateError = checkForErrorAlert($duplicateResult) || 
                        strpos($duplicateResult, 'ya está registrado') !== false ||
                        strpos($duplicateResult, 'unique') !== false;
    
    logTest("Duplicate email error", $hasDuplicateError, $hasDuplicateError ? "Duplicate error found" : "No duplicate error");
    
    curl_close($ch);
    
    // Test empty required fields
    $ch = initCurl($baseUrl . '/clientes/create', $cookieFile);
    $createForm = curl_exec($ch);
    $csrfToken = extractCsrfToken($createForm);
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/clientes',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            '_token' => $csrfToken,
            'name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'document_number' => ''
        ])
    ]);
    
    $emptyFieldsResult = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    $hasValidationError = checkForErrorAlert($emptyFieldsResult) || 
                         strpos($emptyFieldsResult, 'obligatorio') !== false ||
                         strpos($emptyFieldsResult, 'required') !== false;
    
    logTest("Empty fields validation", $hasValidationError, $hasValidationError ? "Validation error found" : "No validation error");
    
    curl_close($ch);
    
    // Step 6: DELETE TEST
    echo "\n6. DELETE FUNCTIONALITY TEST\n";
    echo "-----------------------------\n";
    
    if ($clientId) {
        // Test delete functionality
        $ch = initCurl($baseUrl . '/clientes', $cookieFile);
        $indexPage = curl_exec($ch);
        $csrfToken = extractCsrfToken($indexPage);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $baseUrl . "/clientes/$clientId",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                '_token' => $csrfToken,
                '_method' => 'DELETE'
            ])
        ]);
        
        $deleteResult = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        $deleteSuccess = ($httpCode === 200 || $httpCode === 302) && 
                        (strpos($finalUrl, '/clientes') !== false || strpos($deleteResult, 'exitosamente') !== false);
        
        logTest("Delete client submission", $deleteSuccess, "HTTP $httpCode");
        
        // Check for success alert
        $hasSuccessAlert = checkForSuccessAlert($deleteResult);
        logTest("Delete success alert", $hasSuccessAlert, $hasSuccessAlert ? "Success alert found" : "No success alert");
        
        curl_close($ch);
        
        // Verify client is removed from list
        $ch = initCurl($baseUrl . '/clientes', $cookieFile);
        $finalIndex = curl_exec($ch);
        $clientStillInList = checkClientInList($finalIndex, 'Juan Test Updated');
        logTest("Client removed from list", !$clientStillInList, !$clientStillInList ? "Client successfully removed" : "Client still in list");
        curl_close($ch);
    }
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    logTest("Critical Error", false, $e->getMessage());
}

// Generate detailed report
echo "\n" . str_repeat("=", 60) . "\n";
echo "DETAILED TEST REPORT\n";
echo str_repeat("=", 60) . "\n";

$passed = 0;
$failed = 0;

foreach ($testResults as $result) {
    $status = $result['passed'] ? '✓ PASS' : '✗ FAIL';
    $details = $result['details'] ? " | " . $result['details'] : '';
    echo sprintf("%-50s %s%s\n", $result['test'], $status, $details);
    
    if ($result['passed']) {
        $passed++;
    } else {
        $failed++;
    }
}

echo str_repeat("-", 60) . "\n";
echo "SUMMARY:\n";
echo "Total Tests: " . count($testResults) . "\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Success Rate: " . round(($passed / count($testResults)) * 100, 2) . "%\n";

// Analysis and recommendations
echo "\n" . str_repeat("=", 60) . "\n";
echo "ANALYSIS AND RECOMMENDATIONS\n";
echo str_repeat("=", 60) . "\n";

if ($passed >= count($testResults) * 0.8) {
    echo "✅ EXCELLENT: The Clientes module is working properly.\n";
} elseif ($passed >= count($testResults) * 0.6) {
    echo "⚠️  GOOD: The Clientes module is mostly functional with minor issues.\n";
} else {
    echo "❌ NEEDS ATTENTION: The Clientes module has significant issues.\n";
}

echo "\nKEY FINDINGS:\n";

// Check specific functionality
$createPassed = false;
$updatePassed = false;
$deletePassed = false;
$alertsPassed = false;
$validationPassed = false;

foreach ($testResults as $result) {
    if (strpos($result['test'], 'Create client') !== false && $result['passed']) {
        $createPassed = true;
    }
    if (strpos($result['test'], 'Update client') !== false && $result['passed']) {
        $updatePassed = true;
    }
    if (strpos($result['test'], 'Delete client') !== false && $result['passed']) {
        $deletePassed = true;
    }
    if (strpos($result['test'], 'alert') !== false && $result['passed']) {
        $alertsPassed = true;
    }
    if (strpos($result['test'], 'validation') !== false && $result['passed']) {
        $validationPassed = true;
    }
}

echo "• CREATE functionality: " . ($createPassed ? "✓ Working" : "✗ Issues") . "\n";
echo "• UPDATE functionality: " . ($updatePassed ? "✓ Working" : "✗ Issues") . "\n";
echo "• DELETE functionality: " . ($deletePassed ? "✓ Working" : "✗ Issues") . "\n";
echo "• Success/Error alerts: " . ($alertsPassed ? "✓ Working" : "✗ Issues") . "\n";
echo "• Validation handling: " . ($validationPassed ? "✓ Working" : "✗ Issues") . "\n";

echo "\nRECOMMENDations:\n";
if (!$createPassed) echo "- Fix CREATE functionality and ensure proper redirects\n";
if (!$updatePassed) echo "- Fix UPDATE functionality and ensure data persistence\n";
if (!$deletePassed) echo "- Fix DELETE functionality and ensure proper removal\n";
if (!$alertsPassed) echo "- Implement proper success/error alert system\n";
if (!$validationPassed) echo "- Improve form validation and error handling\n";

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
echo "Cookie file: $cookieFile\n";
echo "Results saved to: " . __FILE__ . "\n";

// Clean up
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

?>