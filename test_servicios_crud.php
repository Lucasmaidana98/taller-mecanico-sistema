<?php

/**
 * Comprehensive Servicios Module CRUD Test
 * Tests create, read, update, delete operations with alerts and validations
 */

// Initialize cURL and cookie jar
$cookieJar = tempnam(sys_get_temp_dir(), 'servicios_test_cookies');
$baseUrl = 'http://localhost:8001';

function makeRequest($url, $postData = null, $cookieJar = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['body' => $response, 'http_code' => $httpCode];
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

function checkForAlerts($html) {
    $alerts = [];
    
    // Check for success alerts
    if (preg_match_all('/<div[^>]*class="[^"]*alert[^"]*success[^"]*"[^>]*>(.*?)<\/div>/is', $html, $matches)) {
        foreach ($matches[1] as $match) {
            $alerts[] = ['type' => 'success', 'message' => strip_tags($match)];
        }
    }
    
    // Check for error alerts
    if (preg_match_all('/<div[^>]*class="[^"]*alert[^"]*danger[^"]*"[^>]*>(.*?)<\/div>/is', $html, $matches)) {
        foreach ($matches[1] as $match) {
            $alerts[] = ['type' => 'error', 'message' => strip_tags($match)];
        }
    }
    
    // Check for validation errors
    if (preg_match_all('/<div[^>]*class="[^"]*text-red[^"]*"[^>]*>(.*?)<\/div>/is', $html, $matches)) {
        foreach ($matches[1] as $match) {
            $alerts[] = ['type' => 'validation', 'message' => strip_tags($match)];
        }
    }
    
    return $alerts;
}

function extractServiceData($html) {
    $services = [];
    
    // Extract service rows from table
    if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $rows)) {
        foreach ($rows[1] as $row) {
            if (preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $row, $cells)) {
                if (count($cells[1]) >= 5) {
                    $service = [
                        'name' => strip_tags($cells[1][0]),
                        'description' => strip_tags($cells[1][1]),
                        'price' => strip_tags($cells[1][2]),
                        'duration' => strip_tags($cells[1][3]),
                        'status' => strip_tags($cells[1][4])
                    ];
                    if (!empty($service['name']) && $service['name'] !== 'Nombre') {
                        $services[] = $service;
                    }
                }
            }
        }
    }
    
    return $services;
}

function extractStatistics($html) {
    $stats = [];
    
    // Extract statistics cards
    if (preg_match_all('/<div[^>]*class="[^"]*bg-white[^"]*"[^>]*>.*?<h3[^>]*>([\d,]+)<\/h3>.*?<p[^>]*>(.*?)<\/p>/is', $html, $matches)) {
        for ($i = 0; $i < count($matches[1]); $i++) {
            $stats[trim(strip_tags($matches[2][$i]))] = trim($matches[1][$i]);
        }
    }
    
    return $stats;
}

echo "=== SERVICIOS MODULE CRUD TESTING ===\n";
echo "Testing comprehensive CRUD operations with alerts and validation\n\n";

// Step 1: Login
echo "1. LOGGING IN...\n";
$loginPage = makeRequest("$baseUrl/login", null, $cookieJar);
if ($loginPage['http_code'] !== 200) {
    die("Failed to access login page. HTTP Code: " . $loginPage['http_code'] . "\n");
}

$csrfToken = extractCsrfToken($loginPage['body']);
if (!$csrfToken) {
    die("Could not extract CSRF token from login page\n");
}

$loginData = [
    '_token' => $csrfToken,
    'email' => 'admin@taller.com',
    'password' => 'admin123'
];

$loginResponse = makeRequest("$baseUrl/login", http_build_query($loginData), $cookieJar);
echo "Login response code: " . $loginResponse['http_code'] . "\n";

// Step 2: Access Servicios Index (baseline)
echo "\n2. ACCESSING SERVICIOS INDEX (BASELINE)...\n";
$indexResponse = makeRequest("$baseUrl/servicios", null, $cookieJar);
if ($indexResponse['http_code'] !== 200) {
    die("Failed to access servicios index. HTTP Code: " . $indexResponse['http_code'] . "\n");
}

$baselineServices = extractServiceData($indexResponse['body']);
$baselineStats = extractStatistics($indexResponse['body']);

echo "Baseline services count: " . count($baselineServices) . "\n";
echo "Baseline statistics:\n";
foreach ($baselineStats as $label => $value) {
    echo "  - $label: $value\n";
}

// Step 3: CREATE TEST
echo "\n3. CREATE TEST...\n";
echo "Accessing create form...\n";
$createPage = makeRequest("$baseUrl/servicios/create", null, $cookieJar);
if ($createPage['http_code'] !== 200) {
    die("Failed to access create form. HTTP Code: " . $createPage['http_code'] . "\n");
}

$csrfToken = extractCsrfToken($createPage['body']);
if (!$csrfToken) {
    die("Could not extract CSRF token from create form\n");
}

echo "Creating service: 'Test Service'...\n";
$createData = [
    '_token' => $csrfToken,
    'name' => 'Test Service',
    'description' => 'Test Description',
    'price' => '100.00',
    'duration_hours' => '2.5',
    'status' => '1'
];

$createResponse = makeRequest("$baseUrl/servicios", http_build_query($createData), $cookieJar);
echo "Create response code: " . $createResponse['http_code'] . "\n";

// Check for success alert
$createAlerts = checkForAlerts($createResponse['body']);
echo "Alerts after creation:\n";
foreach ($createAlerts as $alert) {
    echo "  - {$alert['type']}: {$alert['message']}\n";
}

// Verify service appears in index
echo "Verifying service appears in index...\n";
$indexAfterCreate = makeRequest("$baseUrl/servicios", null, $cookieJar);
$servicesAfterCreate = extractServiceData($indexAfterCreate['body']);
$statsAfterCreate = extractStatistics($indexAfterCreate['body']);

$testServiceFound = false;
foreach ($servicesAfterCreate as $service) {
    if (strpos($service['name'], 'Test Service') !== false) {
        $testServiceFound = true;
        echo "✓ Test Service found in list:\n";
        echo "  - Name: {$service['name']}\n";
        echo "  - Description: {$service['description']}\n";
        echo "  - Price: {$service['price']}\n";
        echo "  - Duration: {$service['duration']}\n";
        echo "  - Status: {$service['status']}\n";
        break;
    }
}

if (!$testServiceFound) {
    echo "✗ Test Service not found in list\n";
}

echo "Statistics after creation:\n";
foreach ($statsAfterCreate as $label => $value) {
    echo "  - $label: $value\n";
}

// Step 4: UPDATE TEST
echo "\n4. UPDATE TEST...\n";

// First, get the service ID from the index page
$serviceId = null;
if (preg_match('/href="[^"]*\/servicios\/(\d+)\/edit"/', $indexAfterCreate['body'], $matches)) {
    $serviceId = $matches[1];
    echo "Found service ID: $serviceId\n";
} else {
    // Try to find the edit link in a different way
    if (preg_match_all('/href="([^"]*\/servicios\/\d+\/edit)"/', $indexAfterCreate['body'], $matches)) {
        foreach ($matches[1] as $editUrl) {
            if (preg_match('/\/servicios\/(\d+)\/edit/', $editUrl, $idMatch)) {
                $serviceId = $idMatch[1];
                echo "Found service ID from edit URL: $serviceId\n";
                break;
            }
        }
    }
}

if (!$serviceId) {
    echo "Could not find service ID, trying to extract from last service...\n";
    // Try to get the last service ID
    if (preg_match_all('/servicios\/(\d+)/', $indexAfterCreate['body'], $matches)) {
        $serviceId = end($matches[1]);
        echo "Using last found service ID: $serviceId\n";
    }
}

if ($serviceId) {
    echo "Accessing edit form for service ID: $serviceId...\n";
    $editPage = makeRequest("$baseUrl/servicios/$serviceId/edit", null, $cookieJar);
    
    if ($editPage['http_code'] === 200) {
        $csrfToken = extractCsrfToken($editPage['body']);
        
        if ($csrfToken) {
            echo "Updating service: changing price to 150.00 and name to 'Updated Test Service'...\n";
            $updateData = [
                '_token' => $csrfToken,
                '_method' => 'PUT',
                'name' => 'Updated Test Service',
                'description' => 'Test Description',
                'price' => '150.00',
                'duration_hours' => '2.5',
                'status' => '1'
            ];
            
            $updateResponse = makeRequest("$baseUrl/servicios/$serviceId", http_build_query($updateData), $cookieJar);
            echo "Update response code: " . $updateResponse['http_code'] . "\n";
            
            // Check for success alert
            $updateAlerts = checkForAlerts($updateResponse['body']);
            echo "Alerts after update:\n";
            foreach ($updateAlerts as $alert) {
                echo "  - {$alert['type']}: {$alert['message']}\n";
            }
            
            // Verify changes in index
            echo "Verifying changes are reflected in list...\n";
            $indexAfterUpdate = makeRequest("$baseUrl/servicios", null, $cookieJar);
            $servicesAfterUpdate = extractServiceData($indexAfterUpdate['body']);
            
            $updatedServiceFound = false;
            foreach ($servicesAfterUpdate as $service) {
                if (strpos($service['name'], 'Updated Test Service') !== false) {
                    $updatedServiceFound = true;
                    echo "✓ Updated Service found in list:\n";
                    echo "  - Name: {$service['name']}\n";
                    echo "  - Price: {$service['price']}\n";
                    break;
                }
            }
            
            if (!$updatedServiceFound) {
                echo "✗ Updated Service not found in list\n";
            }
        } else {
            echo "Could not extract CSRF token from edit form\n";
        }
    } else {
        echo "Failed to access edit form. HTTP Code: " . $editPage['http_code'] . "\n";
    }
} else {
    echo "Could not determine service ID for update test\n";
}

// Step 5: ERROR HANDLING TEST
echo "\n5. ERROR HANDLING TEST...\n";

// Test with invalid data (negative price)
echo "Testing with negative price...\n";
$createPageError = makeRequest("$baseUrl/servicios/create", null, $cookieJar);
$csrfToken = extractCsrfToken($createPageError['body']);

if ($csrfToken) {
    $invalidData1 = [
        '_token' => $csrfToken,
        'name' => 'Invalid Service',
        'description' => 'Test Description',
        'price' => '-50.00',
        'duration_hours' => '2.5',
        'status' => '1'
    ];
    
    $errorResponse1 = makeRequest("$baseUrl/servicios", http_build_query($invalidData1), $cookieJar);
    echo "Response code for negative price: " . $errorResponse1['http_code'] . "\n";
    
    $errorAlerts1 = checkForAlerts($errorResponse1['body']);
    echo "Alerts for negative price:\n";
    foreach ($errorAlerts1 as $alert) {
        echo "  - {$alert['type']}: {$alert['message']}\n";
    }
}

// Test with empty name
echo "Testing with empty name...\n";
$createPageError2 = makeRequest("$baseUrl/servicios/create", null, $cookieJar);
$csrfToken = extractCsrfToken($createPageError2['body']);

if ($csrfToken) {
    $invalidData2 = [
        '_token' => $csrfToken,
        'name' => '',
        'description' => 'Test Description',
        'price' => '100.00',
        'duration_hours' => '2.5',
        'status' => '1'
    ];
    
    $errorResponse2 = makeRequest("$baseUrl/servicios", http_build_query($invalidData2), $cookieJar);
    echo "Response code for empty name: " . $errorResponse2['http_code'] . "\n";
    
    $errorAlerts2 = checkForAlerts($errorResponse2['body']);
    echo "Alerts for empty name:\n";
    foreach ($errorAlerts2 as $alert) {
        echo "  - {$alert['type']}: {$alert['message']}\n";
    }
}

// Step 6: VIEW CHANGES AND STATISTICS
echo "\n6. VIEW CHANGES AND STATISTICS...\n";
$finalIndex = makeRequest("$baseUrl/servicios", null, $cookieJar);
$finalServices = extractServiceData($finalIndex['body']);
$finalStats = extractStatistics($finalIndex['body']);

echo "Final services count: " . count($finalServices) . "\n";
echo "Final statistics:\n";
foreach ($finalStats as $label => $value) {
    echo "  - $label: $value\n";
}

echo "\nComparison with baseline:\n";
echo "Services count change: " . (count($finalServices) - count($baselineServices)) . "\n";

// Clean up
unlink($cookieJar);

echo "\n=== TEST COMPLETED ===\n";