<?php

/**
 * Debug script to investigate Clientes UPDATE functionality issues
 */

$baseUrl = 'http://localhost:8001';
$email = 'admin@taller.com';
$password = 'admin123';
$cookieFile = __DIR__ . '/debug_clientes_cookies.txt';

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

function extractCsrfToken($html) {
    if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $html, $matches)) {
        return $matches[1];
    }
    return null;
}

// Clear previous cookies
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "=== DEBUGGING CLIENTES UPDATE FUNCTIONALITY ===\n\n";

try {
    // Login first
    $ch = initCurl($baseUrl . '/login', $cookieFile);
    $loginPage = curl_exec($ch);
    $csrfToken = extractCsrfToken($loginPage);
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/login',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            '_token' => $csrfToken,
            'email' => $email,
            'password' => $password
        ])
    ]);
    
    curl_exec($ch);
    curl_close($ch);
    
    echo "✓ Logged in successfully\n\n";
    
    // Create a test client first
    echo "1. Creating test client...\n";
    $ch = initCurl($baseUrl . '/clientes/create', $cookieFile);
    $createForm = curl_exec($ch);
    $csrfToken = extractCsrfToken($createForm);
    
    $testData = [
        'name' => 'Debug Test Client',
        'email' => 'debug.test@example.com',
        'phone' => '555-9999',
        'address' => 'Debug Address',
        'document_number' => '99999999',
        'status' => '1'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/clientes',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(array_merge(['_token' => $csrfToken], $testData))
    ]);
    
    $createResult = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "Create result HTTP: $httpCode\n";
    
    curl_close($ch);
    
    // Get the client list to find the ID
    echo "\n2. Finding created client ID...\n";
    $ch = initCurl($baseUrl . '/clientes', $cookieFile);
    $indexPage = curl_exec($ch);
    curl_close($ch);
    
    $clientId = null;
    if (preg_match_all('/\/clientes\/(\d+)\/edit/', $indexPage, $matches)) {
        // Get the last client ID (newest one)
        $clientId = end($matches[1]);
    }
    
    if (!$clientId) {
        echo "❌ Could not find client ID\n";
        exit;
    }
    
    echo "✓ Found client ID: $clientId\n\n";
    
    // Now test the update functionality in detail
    echo "3. Testing UPDATE functionality...\n";
    
    // Access edit form
    $ch = initCurl($baseUrl . "/clientes/$clientId/edit", $cookieFile);
    $editForm = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "Edit form access HTTP: $httpCode\n";
    
    if ($httpCode !== 200) {
        echo "❌ Cannot access edit form\n";
        echo "Response preview: " . substr($editForm, 0, 500) . "...\n";
        exit;
    }
    
    $csrfToken = extractCsrfToken($editForm);
    echo "CSRF token: " . substr($csrfToken, 0, 10) . "...\n";
    
    // Extract current form values
    preg_match('/name="name"[^>]*value="([^"]*)"/', $editForm, $nameMatch);
    preg_match('/name="email"[^>]*value="([^"]*)"/', $editForm, $emailMatch);
    preg_match('/name="phone"[^>]*value="([^"]*)"/', $editForm, $phoneMatch);
    preg_match('/name="document_number"[^>]*value="([^"]*)"/', $editForm, $docMatch);
    preg_match('/<textarea[^>]*name="address"[^>]*>([^<]*)<\/textarea>/', $editForm, $addressMatch);
    
    echo "Current form values:\n";
    echo "- Name: " . ($nameMatch[1] ?? 'Not found') . "\n";
    echo "- Email: " . ($emailMatch[1] ?? 'Not found') . "\n";
    echo "- Phone: " . ($phoneMatch[1] ?? 'Not found') . "\n";
    echo "- Document: " . ($docMatch[1] ?? 'Not found') . "\n";
    echo "- Address: " . ($addressMatch[1] ?? 'Not found') . "\n";
    
    curl_close($ch);
    
    // Prepare update data
    $updateData = [
        '_token' => $csrfToken,
        '_method' => 'PUT',
        'name' => 'Debug Test Client UPDATED',
        'email' => $emailMatch[1] ?? 'debug.test@example.com',
        'phone' => $phoneMatch[1] ?? '555-9999',
        'address' => $addressMatch[1] ?? 'Debug Address',
        'document_number' => $docMatch[1] ?? '99999999',
        'status' => '1'
    ];
    
    echo "\nUpdate data to send:\n";
    foreach ($updateData as $key => $value) {
        if ($key !== '_token') {
            echo "- $key: $value\n";
        }
    }
    
    // Submit update
    echo "\n4. Submitting update...\n";
    $ch = initCurl($baseUrl . "/clientes/$clientId", $cookieFile);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($updateData),
        CURLOPT_HEADER => true
    ]);
    
    $updateResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    
    echo "Update HTTP Code: $httpCode\n";
    echo "Content Type: $contentType\n";
    
    // Split headers and body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($updateResponse, 0, $headerSize);
    $body = substr($updateResponse, $headerSize);
    
    echo "\nResponse Headers:\n";
    echo $headers . "\n";
    
    if ($httpCode >= 400) {
        echo "\n❌ Update failed with HTTP $httpCode\n";
        echo "Response body preview:\n";
        echo substr($body, 0, 1000) . "\n";
        
        // Look for specific error messages
        if (strpos($body, 'error') !== false || strpos($body, 'exception') !== false) {
            echo "\nLooking for error details...\n";
            if (preg_match('/class="[^"]*error[^"]*"[^>]*>([^<]+)/', $body, $errorMatch)) {
                echo "Error message: " . $errorMatch[1] . "\n";
            }
        }
    } else {
        echo "✓ Update submitted successfully\n";
        
        // Check if redirected back to index
        if (strpos($headers, 'Location:') !== false) {
            preg_match('/Location:\s*([^\r\n]+)/', $headers, $locationMatch);
            echo "Redirected to: " . trim($locationMatch[1] ?? 'Unknown') . "\n";
        }
    }
    
    curl_close($ch);
    
    // Verify the update worked
    echo "\n5. Verifying update worked...\n";
    $ch = initCurl($baseUrl . '/clientes', $cookieFile);
    $finalIndex = curl_exec($ch);
    curl_close($ch);
    
    $updatedNameFound = strpos($finalIndex, 'Debug Test Client UPDATED') !== false;
    echo "Updated name found in list: " . ($updatedNameFound ? "✓ YES" : "❌ NO") . "\n";
    
    if (!$updatedNameFound) {
        echo "Checking if original name still exists...\n";
        $originalNameFound = strpos($finalIndex, 'Debug Test Client') !== false;
        echo "Original name still in list: " . ($originalNameFound ? "✓ YES" : "❌ NO") . "\n";
    }
    
    // Clean up - delete the test client
    echo "\n6. Cleaning up test client...\n";
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
    echo "Delete HTTP: $httpCode\n";
    
    curl_close($ch);
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

// Clean up
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "\nDebug complete.\n";

?>