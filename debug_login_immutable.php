<?php
/**
 * Debug Login for Immutable Fields Test
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$baseUrl = 'http://localhost:8003';
$cookieFile = __DIR__ . '/debug_immutable_cookies.txt';

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

echo "Debug Login Process\n";
echo "===================\n\n";

// Step 1: Get login page
echo "1. Getting login page...\n";
$loginPage = makeRequest("$baseUrl/login", null, 'GET', $cookieFile);
echo "Response code: {$loginPage['code']}\n";

if ($loginPage['code'] !== 200) {
    echo "❌ Cannot access login page\n";
    exit;
}

// Step 2: Extract CSRF token
echo "\n2. Extracting CSRF token...\n";
if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $loginPage['body'], $matches)) {
    $token = $matches[1];
    echo "✅ Token found: " . substr($token, 0, 10) . "...\n";
} else {
    echo "❌ No CSRF token found\n";
    echo "Login page content (first 500 chars):\n";
    echo substr($loginPage['body'], 0, 500) . "\n";
    exit;
}

// Step 3: Attempt login
echo "\n3. Attempting login...\n";
$loginData = http_build_query([
    '_token' => $token,
    'email' => 'admin@taller.com',
    'password' => 'admin123'
]);

$loginResponse = makeRequest("$baseUrl/login", $loginData, 'POST', $cookieFile);
echo "Login response code: {$loginResponse['code']}\n";

// Step 4: Check if login was successful
echo "\n4. Checking login success...\n";
if ($loginResponse['code'] === 302) {
    echo "✅ Got redirect (302) - likely successful\n";
    
    // Try to access dashboard
    $dashboardResponse = makeRequest("$baseUrl/dashboard", null, 'GET', $cookieFile);
    echo "Dashboard access code: {$dashboardResponse['code']}\n";
    
    if ($dashboardResponse['code'] === 200 && strpos($dashboardResponse['body'], 'Dashboard') !== false) {
        echo "✅ Successfully accessed dashboard\n";
        
        // Now let's test a simple immutable field
        echo "\n5. Testing immutable field access...\n";
        
        // Get clients list
        $clientsResponse = makeRequest("$baseUrl/clientes", null, 'GET', $cookieFile);
        echo "Clients page code: {$clientsResponse['code']}\n";
        
        if ($clientsResponse['code'] === 200) {
            echo "✅ Can access clients module\n";
            
            // Try to find first client edit link
            if (preg_match('/href="[^"]*\/clientes\/(\d+)\/edit"/', $clientsResponse['body'], $matches)) {
                $clientId = $matches[1];
                echo "✅ Found client ID: $clientId\n";
                
                // Get edit form
                $editResponse = makeRequest("$baseUrl/clientes/$clientId/edit", null, 'GET', $cookieFile);
                echo "Edit form code: {$editResponse['code']}\n";
                
                if ($editResponse['code'] === 200) {
                    echo "✅ Can access edit form\n";
                    
                    // Check for ID field
                    $hasIdField = preg_match('/name=["\']id["\']/', $editResponse['body']);
                    echo "Has ID field in form: " . ($hasIdField ? "YES ❌ (security issue)" : "NO ✅ (good)") . "\n";
                    
                    // Check for created_at field
                    $hasCreatedAtField = preg_match('/name=["\']created_at["\']/', $editResponse['body']);
                    echo "Has created_at field in form: " . ($hasCreatedAtField ? "YES ❌ (security issue)" : "NO ✅ (good)") . "\n";
                    
                    // Check for document_number field
                    $hasDocumentField = preg_match('/name=["\']document_number["\']/', $editResponse['body']);
                    $documentDisabled = preg_match('/name=["\']document_number["\'][^>]*(?:disabled|readonly)/', $editResponse['body']);
                    
                    echo "Has document_number field: " . ($hasDocumentField ? "YES" : "NO") . "\n";
                    echo "Document field disabled: " . ($documentDisabled ? "YES ✅" : "NO ⚠️") . "\n";
                    
                } else {
                    echo "❌ Cannot access edit form\n";
                }
            } else {
                echo "❌ No client records found\n";
            }
        } else {
            echo "❌ Cannot access clients module\n";
        }
        
    } else {
        echo "❌ Cannot access dashboard after login\n";
        echo "Dashboard response (first 200 chars): " . substr($dashboardResponse['body'], 0, 200) . "\n";
    }
} else {
    echo "❌ Login failed - no redirect\n";
    echo "Response body (first 500 chars):\n";
    echo substr($loginResponse['body'], 0, 500) . "\n";
}

echo "\nDebug completed.\n";
?>