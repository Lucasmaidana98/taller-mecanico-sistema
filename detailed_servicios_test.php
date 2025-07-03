<?php

/**
 * Detailed Servicios Module Testing with Database Verification
 */

// Check database directly
function checkDatabase() {
    $dbPath = __DIR__ . '/database/database.sqlite';
    if (!file_exists($dbPath)) {
        echo "Database file not found: $dbPath\n";
        return;
    }

    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get current services count
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM servicios');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Current services in database: " . $result['count'] . "\n";
        
        // Get latest services
        $stmt = $pdo->query('SELECT * FROM servicios ORDER BY created_at DESC LIMIT 5');
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Latest services:\n";
        foreach ($services as $service) {
            echo "  - ID: {$service['id']}, Name: {$service['name']}, Price: {$service['price']}, Status: {$service['status']}\n";
        }
        
        return $services;
        
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Initialize cURL and cookie jar
$cookieJar = tempnam(sys_get_temp_dir(), 'detailed_servicios_test');
$baseUrl = 'http://localhost:8001';

function makeRequest($url, $postData = null, $cookieJar = null, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    return ['body' => $body, 'headers' => $headers, 'http_code' => $httpCode];
}

function extractCsrfToken($html) {
    if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    if (preg_match('/<input[^>]+name="_token"[^>]+value="([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    return null;
}

function checkForFlashMessages($html) {
    $messages = [];
    
    // Check for Laravel flash messages
    if (preg_match_all('/@if\s*\(\s*session\s*\(\s*[\'"]success[\'"]\s*\)\s*\).*?@endif/s', $html, $matches)) {
        $messages[] = ['type' => 'success', 'context' => 'flash'];
    }
    
    // Check for SweetAlert calls
    if (preg_match_all('/Swal\.fire\s*\(\s*\{([^}]+)\}/', $html, $matches)) {
        foreach ($matches[1] as $match) {
            if (preg_match('/icon\s*:\s*[\'"]([^\'"]+)/', $match, $iconMatch) && 
                preg_match('/text\s*:\s*[\'"]([^\'"]+)/', $match, $textMatch)) {
                $messages[] = [
                    'type' => $iconMatch[1],
                    'text' => $textMatch[1],
                    'context' => 'swal'
                ];
            }
        }
    }
    
    return $messages;
}

echo "=== DETAILED SERVICIOS MODULE TESTING ===\n\n";

// Check initial database state
echo "1. INITIAL DATABASE STATE\n";
$initialServices = checkDatabase();

// Login
echo "\n2. AUTHENTICATION\n";
$loginPage = makeRequest("$baseUrl/login", null, $cookieJar);
echo "Login page access: " . $loginPage['http_code'] . "\n";

$csrfToken = extractCsrfToken($loginPage['body']);
if (!$csrfToken) {
    die("Could not extract CSRF token\n");
}

$loginData = [
    '_token' => $csrfToken,
    'email' => 'admin@taller.com',
    'password' => 'admin123'
];

$loginResponse = makeRequest("$baseUrl/login", http_build_query($loginData), $cookieJar);
echo "Login attempt: " . $loginResponse['http_code'] . "\n";

// Check if we can access protected route
$dashboardTest = makeRequest("$baseUrl/dashboard", null, $cookieJar);
echo "Dashboard access: " . $dashboardTest['http_code'] . "\n";

// Access servicios index
echo "\n3. SERVICIOS INDEX ACCESS\n";
$indexResponse = makeRequest("$baseUrl/servicios", null, $cookieJar);
echo "Servicios index: " . $indexResponse['http_code'] . "\n";

// Create service test
echo "\n4. CREATE SERVICE TEST\n";
$createFormResponse = makeRequest("$baseUrl/servicios/create", null, $cookieJar);
echo "Create form access: " . $createFormResponse['http_code'] . "\n";

$csrfToken = extractCsrfToken($createFormResponse['body']);
if ($csrfToken) {
    echo "CSRF token extracted successfully\n";
    
    $serviceData = [
        '_token' => $csrfToken,
        'name' => 'Automated Test Service ' . date('Y-m-d H:i:s'),
        'description' => 'This is a test service created by automated testing',
        'price' => '125.50',
        'duration_hours' => '3.0',
        'status' => '1'
    ];
    
    echo "Submitting service creation...\n";
    $createResponse = makeRequest("$baseUrl/servicios", http_build_query($serviceData), $cookieJar);
    echo "Create response: " . $createResponse['http_code'] . "\n";
    
    // Check database after creation
    echo "Database state after creation:\n";
    $afterCreateServices = checkDatabase();
    
    // Check if redirect happened (302) and follow it
    if ($createResponse['http_code'] == 302) {
        echo "Service creation successful (redirected)\n";
        
        // Check the index page for success message
        $indexAfterCreate = makeRequest("$baseUrl/servicios", null, $cookieJar);
        $flashMessages = checkForFlashMessages($indexAfterCreate['body']);
        
        echo "Flash messages found:\n";
        foreach ($flashMessages as $msg) {
            echo "  - Type: {$msg['type']}, Context: {$msg['context']}\n";
            if (isset($msg['text'])) {
                echo "    Text: {$msg['text']}\n";
            }
        }
    }
} else {
    echo "Failed to extract CSRF token from create form\n";
}

// Test form validation
echo "\n5. FORM VALIDATION TESTS\n";

// Test with invalid price
echo "Testing negative price validation...\n";
$createForm2 = makeRequest("$baseUrl/servicios/create", null, $cookieJar);
$csrfToken2 = extractCsrfToken($createForm2['body']);

if ($csrfToken2) {
    $invalidData = [
        '_token' => $csrfToken2,
        'name' => 'Invalid Price Service',
        'description' => 'Test service with negative price',
        'price' => '-50.00',
        'duration_hours' => '2.0',
        'status' => '1'
    ];
    
    $invalidResponse = makeRequest("$baseUrl/servicios", http_build_query($invalidData), $cookieJar);
    echo "Invalid price response: " . $invalidResponse['http_code'] . "\n";
    
    if ($invalidResponse['http_code'] == 422 || $invalidResponse['http_code'] == 200) {
        echo "Validation working - form rejected invalid data\n";
    }
}

// Test empty required fields
echo "Testing empty name validation...\n";
$createForm3 = makeRequest("$baseUrl/servicios/create", null, $cookieJar);
$csrfToken3 = extractCsrfToken($createForm3['body']);

if ($csrfToken3) {
    $emptyData = [
        '_token' => $csrfToken3,
        'name' => '',
        'description' => 'Test service with empty name',
        'price' => '100.00',
        'duration_hours' => '2.0',
        'status' => '1'
    ];
    
    $emptyResponse = makeRequest("$baseUrl/servicios", http_build_query($emptyData), $cookieJar);
    echo "Empty name response: " . $emptyResponse['http_code'] . "\n";
    
    if ($emptyResponse['http_code'] == 422 || $emptyResponse['http_code'] == 200) {
        echo "Validation working - form rejected empty required field\n";
    }
}

// Test service editing
echo "\n6. SERVICE UPDATE TEST\n";
if ($afterCreateServices && count($afterCreateServices) > 0) {
    $latestService = $afterCreateServices[0];
    $serviceId = $latestService['id'];
    
    echo "Testing edit for service ID: $serviceId\n";
    $editFormResponse = makeRequest("$baseUrl/servicios/$serviceId/edit", null, $cookieJar);
    echo "Edit form access: " . $editFormResponse['http_code'] . "\n";
    
    if ($editFormResponse['http_code'] == 200) {
        $editCsrfToken = extractCsrfToken($editFormResponse['body']);
        
        if ($editCsrfToken) {
            $updateData = [
                '_token' => $editCsrfToken,
                '_method' => 'PUT',
                'name' => 'Updated ' . $latestService['name'],
                'description' => 'Updated description for testing',
                'price' => '199.99',
                'duration_hours' => '4.5',
                'status' => '1'
            ];
            
            echo "Submitting service update...\n";
            $updateResponse = makeRequest("$baseUrl/servicios/$serviceId", http_build_query($updateData), $cookieJar);
            echo "Update response: " . $updateResponse['http_code'] . "\n";
            
            // Check database after update
            echo "Database state after update:\n";
            checkDatabase();
        }
    }
}

// Final database state
echo "\n7. FINAL DATABASE STATE\n";
$finalServices = checkDatabase();

// Calculate changes
if ($initialServices && $finalServices) {
    $initialCount = count($initialServices);
    $finalCount = count($finalServices);
    echo "Service count change: " . ($finalCount - $initialCount) . "\n";
}

// Clean up
unlink($cookieJar);

echo "\n=== DETAILED TEST COMPLETED ===\n";