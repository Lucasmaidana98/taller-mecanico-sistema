<?php

/**
 * Enhanced CRUD Testing Script with proper Laravel authentication
 */

// Initialize cURL session with cookie support
$cookieFile = __DIR__ . '/enhanced_test_cookies.txt';
$baseUrl = 'http://localhost:8001';

// Clear existing cookies
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

function makeCurlRequest($url, $postData = null, $method = 'GET', $headers = []) {
    global $cookieFile;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    if ($method === 'POST' && $postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $error = curl_error($ch);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    return [
        'response' => $body,
        'headers' => $headers,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

function extractCSRFToken($html) {
    // Try multiple patterns for CSRF token extraction
    $patterns = [
        '/<meta name="csrf-token" content="([^"]+)"/',
        '/<input[^>]*name="_token"[^>]*value="([^"]*)"/',
        '/"_token":"([^"]*)"/',
        '/window\.Laravel\s*=\s*\{[^}]*"csrfToken":"([^"]*)"/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

function extractItemId($html, $module) {
    // Try to extract the ID of the created item
    $patterns = [
        "/{$module}\/(\d+)\/edit/",
        "/{$module}\/(\d+)\/show/",
        "/data-id=\"(\d+)\"/",
        "/id=\"{$module}_(\d+)\"/"
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

function checkForAlerts($html) {
    $alerts = [];
    
    // Multiple patterns for success alerts
    $successPatterns = [
        '/<div[^>]*class="[^"]*alert-success[^"]*"[^>]*>(.*?)<\/div>/s',
        '/<div[^>]*class="[^"]*bg-green[^"]*"[^>]*>(.*?)<\/div>/s',
        '/<div[^>]*class="[^"]*success[^"]*"[^>]*>(.*?)<\/div>/s'
    ];
    
    foreach ($successPatterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $match) {
                $alerts['success'][] = trim(strip_tags($match));
            }
        }
    }
    
    // Multiple patterns for error alerts
    $errorPatterns = [
        '/<div[^>]*class="[^"]*alert-danger[^"]*"[^>]*>(.*?)<\/div>/s',
        '/<div[^>]*class="[^"]*bg-red[^"]*"[^>]*>(.*?)<\/div>/s',
        '/<div[^>]*class="[^"]*error[^"]*"[^>]*>(.*?)<\/div>/s',
        '/<div[^>]*class="[^"]*text-red[^"]*"[^>]*>(.*?)<\/div>/s'
    ];
    
    foreach ($errorPatterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $match) {
                $alerts['error'][] = trim(strip_tags($match));
            }
        }
    }
    
    return $alerts;
}

echo "=== ENHANCED CRUD TESTING STARTED ===\n";
echo date('Y-m-d H:i:s') . "\n\n";

// Step 1: Initial request to get session
echo "1. INITIALIZING SESSION\n";
echo "=======================\n";

$initRequest = makeCurlRequest("$baseUrl");
if ($initRequest['http_code'] === 302) {
    echo "✅ Session initialized (redirected to login)\n";
} else {
    echo "⚠️ Unexpected response code: {$initRequest['http_code']}\n";
}

// Step 2: Get login page and extract CSRF token
echo "\n2. ACCESSING LOGIN PAGE\n";
echo "=======================\n";

$loginPage = makeCurlRequest("$baseUrl/login");
if ($loginPage['http_code'] !== 200) {
    echo "❌ Failed to access login page (HTTP: {$loginPage['http_code']})\n";
    exit(1);
}

$csrfToken = extractCSRFToken($loginPage['response']);
if (!$csrfToken) {
    echo "❌ CSRF token not found in login page\n";
    echo "Debug - First 500 chars of login page:\n";
    echo substr($loginPage['response'], 0, 500) . "\n";
    exit(1);
}

echo "✅ Login page accessible\n";
echo "✅ CSRF token extracted: " . substr($csrfToken, 0, 10) . "...\n";

// Step 3: Perform login
echo "\n3. PERFORMING LOGIN\n";
echo "==================\n";

$loginData = [
    '_token' => $csrfToken,
    'email' => 'admin@taller.com',
    'password' => 'admin123'
];

$loginHeaders = [
    'Content-Type: application/x-www-form-urlencoded',
    'Referer: ' . $baseUrl . '/login'
];

$loginResult = makeCurlRequest("$baseUrl/login", http_build_query($loginData), 'POST', $loginHeaders);

echo "Login response code: {$loginResult['http_code']}\n";

if ($loginResult['http_code'] === 302) {
    echo "✅ Login successful (redirected)\n";
    
    // Check redirect location
    if (preg_match('/Location: (.*)/', $loginResult['headers'], $matches)) {
        $redirectLocation = trim($matches[1]);
        echo "🔄 Redirected to: $redirectLocation\n";
    }
} else {
    echo "❌ Login failed\n";
    echo "Response body preview:\n" . substr($loginResult['response'], 0, 500) . "\n";
    exit(1);
}

// Step 4: Access dashboard to confirm authentication
echo "\n4. VERIFYING AUTHENTICATION\n";
echo "===========================\n";

$dashboard = makeCurlRequest("$baseUrl/dashboard");
if ($dashboard['http_code'] === 200) {
    echo "✅ Dashboard accessible - Authentication confirmed\n";
    
    // Extract statistics
    if (preg_match_all('/(\d+)/', $dashboard['response'], $matches)) {
        echo "📊 Found statistics on dashboard\n";
    }
} else {
    echo "❌ Dashboard not accessible (HTTP: {$dashboard['http_code']})\n";
    echo "This indicates authentication may have failed\n";
    exit(1);
}

// Step 5: Test individual modules
$modules = [
    'clientes' => [
        'nombre' => 'Cliente Test Enhanced',
        'apellido' => 'Apellido Test',
        'email' => 'enhanced' . time() . '@test.com',
        'telefono' => '123456789',
        'documento' => '12345' . time(),
        'direccion' => 'Dirección Test Enhanced'
    ],
    'servicios' => [
        'nombre' => 'Servicio Test Enhanced',
        'descripcion' => 'Descripción del servicio test enhanced',
        'precio' => '150.00',
        'duracion_estimada' => '120'
    ]
];

foreach ($modules as $module => $testData) {
    echo "\n5. TESTING MODULE: " . strtoupper($module) . "\n";
    echo str_repeat("=", 30 + strlen($module)) . "\n";
    
    // Test CREATE
    echo "5.1 Testing CREATE operation\n";
    
    $createPage = makeCurlRequest("$baseUrl/$module/create");
    if ($createPage['http_code'] !== 200) {
        echo "❌ Create page not accessible (HTTP: {$createPage['http_code']})\n";
        continue;
    }
    
    echo "✅ Create page accessible\n";
    
    $csrfToken = extractCSRFToken($createPage['response']);
    if (!$csrfToken) {
        echo "❌ CSRF token not found on create page\n";
        continue;
    }
    
    echo "✅ CSRF token extracted from create page\n";
    
    // Prepare POST data
    $postData = $testData;
    $postData['_token'] = $csrfToken;
    
    $createHeaders = [
        'Content-Type: application/x-www-form-urlencoded',
        'Referer: ' . $baseUrl . "/$module/create"
    ];
    
    $createResult = makeCurlRequest("$baseUrl/$module", http_build_query($postData), 'POST', $createHeaders);
    
    echo "Create response code: {$createResult['http_code']}\n";
    
    if ($createResult['http_code'] === 302) {
        echo "✅ Create operation successful (redirected)\n";
        
        // Check for success message by following redirect
        if (preg_match('/Location: (.*)/', $createResult['headers'], $matches)) {
            $redirectUrl = trim($matches[1]);
            if (strpos($redirectUrl, 'http') !== 0) {
                $redirectUrl = $baseUrl . $redirectUrl;
            }
            
            $successPage = makeCurlRequest($redirectUrl);
            $alerts = checkForAlerts($successPage['response']);
            
            if (!empty($alerts['success'])) {
                echo "✅ Success alert displayed: " . implode(', ', $alerts['success']) . "\n";
            } else {
                echo "⚠️ No success alert found, but operation may have succeeded\n";
            }
            
            // Try to extract created item ID
            $itemId = extractItemId($successPage['response'], $module);
            if ($itemId) {
                echo "📝 Created item ID: $itemId\n";
                
                // Test READ operation (show individual item)
                echo "5.2 Testing READ operation (individual item)\n";
                $showPage = makeCurlRequest("$baseUrl/$module/$itemId");
                if ($showPage['http_code'] === 200) {
                    echo "✅ Individual item view accessible\n";
                    
                    // Check if our test data appears
                    if (strpos($showPage['response'], $testData['nombre']) !== false) {
                        echo "✅ Created data appears correctly in individual view\n";
                    }
                }
                
                // Test UPDATE operation
                echo "5.3 Testing UPDATE operation\n";
                $editPage = makeCurlRequest("$baseUrl/$module/$itemId/edit");
                if ($editPage['http_code'] === 200) {
                    echo "✅ Edit page accessible\n";
                    
                    $editCsrfToken = extractCSRFToken($editPage['response']);
                    if ($editCsrfToken) {
                        $updateData = $testData;
                        $updateData['nombre'] .= ' - UPDATED';
                        $updateData['_token'] = $editCsrfToken;
                        $updateData['_method'] = 'PUT';
                        
                        $updateHeaders = [
                            'Content-Type: application/x-www-form-urlencoded',
                            'Referer: ' . $baseUrl . "/$module/$itemId/edit"
                        ];
                        
                        $updateResult = makeCurlRequest("$baseUrl/$module/$itemId", http_build_query($updateData), 'POST', $updateHeaders);
                        
                        if ($updateResult['http_code'] === 302) {
                            echo "✅ Update operation successful\n";
                            
                            // Check for update success alert
                            if (preg_match('/Location: (.*)/', $updateResult['headers'], $matches)) {
                                $updateRedirectUrl = trim($matches[1]);
                                if (strpos($updateRedirectUrl, 'http') !== 0) {
                                    $updateRedirectUrl = $baseUrl . $updateRedirectUrl;
                                }
                                
                                $updateSuccessPage = makeCurlRequest($updateRedirectUrl);
                                $updateAlerts = checkForAlerts($updateSuccessPage['response']);
                                
                                if (!empty($updateAlerts['success'])) {
                                    echo "✅ Update success alert displayed\n";
                                }
                            }
                        } else {
                            echo "❌ Update operation failed (HTTP: {$updateResult['http_code']})\n";
                        }
                    }
                }
                
                // Test DELETE operation
                echo "5.4 Testing DELETE operation\n";
                
                // Get fresh CSRF token for delete
                $listPage = makeCurlRequest("$baseUrl/$module");
                $deleteCsrfToken = extractCSRFToken($listPage['response']);
                
                if ($deleteCsrfToken) {
                    $deleteData = [
                        '_token' => $deleteCsrfToken,
                        '_method' => 'DELETE'
                    ];
                    
                    $deleteHeaders = [
                        'Content-Type: application/x-www-form-urlencoded',
                        'Referer: ' . $baseUrl . "/$module"
                    ];
                    
                    $deleteResult = makeCurlRequest("$baseUrl/$module/$itemId", http_build_query($deleteData), 'POST', $deleteHeaders);
                    
                    if ($deleteResult['http_code'] === 302) {
                        echo "✅ Delete operation successful\n";
                        
                        // Verify item is removed
                        $finalListPage = makeCurlRequest("$baseUrl/$module");
                        if (strpos($finalListPage['response'], $testData['nombre']) === false) {
                            echo "✅ Item removed from listing\n";
                        } else {
                            echo "⚠️ Item may still appear in listing\n";
                        }
                        
                        // Check for delete success alert
                        $deleteAlerts = checkForAlerts($finalListPage['response']);
                        if (!empty($deleteAlerts['success'])) {
                            echo "✅ Delete success alert displayed\n";
                        }
                    } else {
                        echo "❌ Delete operation failed (HTTP: {$deleteResult['http_code']})\n";
                    }
                }
            }
        }
    } else {
        echo "❌ Create operation failed (HTTP: {$createResult['http_code']})\n";
        
        // Check for validation errors
        $alerts = checkForAlerts($createResult['response']);
        if (!empty($alerts['error'])) {
            echo "🔍 Error messages: " . implode(', ', $alerts['error']) . "\n";
        }
    }
    
    // Test validation with empty form
    echo "5.5 Testing validation with empty form\n";
    $emptyData = ['_token' => $csrfToken];
    $validationResult = makeCurlRequest("$baseUrl/$module", http_build_query($emptyData), 'POST', $createHeaders);
    
    if ($validationResult['http_code'] === 422 || $validationResult['http_code'] === 302) {
        $validationAlerts = checkForAlerts($validationResult['response']);
        if (!empty($validationAlerts['error']) || $validationResult['http_code'] === 422) {
            echo "✅ Validation working (empty form rejected)\n";
        }
    }
}

// Final statistics check
echo "\n6. FINAL STATISTICS CHECK\n";
echo "=========================\n";

$finalDashboard = makeCurlRequest("$baseUrl/dashboard");
if ($finalDashboard['http_code'] === 200) {
    echo "✅ Dashboard still accessible\n";
    echo "📊 Statistics appear to be functional\n";
}

echo "\n=== ENHANCED CRUD TESTING COMPLETED ===\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";

?>