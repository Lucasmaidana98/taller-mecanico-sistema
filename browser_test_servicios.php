<?php

/**
 * Browser-based testing for Servicios module
 * This test simulates user interaction through a web browser
 */

// Initialize cURL and cookie handling
$cookieJar = tempnam(sys_get_temp_dir(), 'browser_servicios_test');
$baseUrl = 'http://localhost:8001';

function makeBrowserRequest($url, $postData = null, $cookieJar = null, $followRedirects = true) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirects);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
    ]);
    
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
            curl_getopt($ch, CURLOPT_HTTPHEADER),
            ['Content-Type: application/x-www-form-urlencoded']
        ));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    return ['body' => $response, 'http_code' => $httpCode, 'final_url' => $finalUrl];
}

function extractToken($html, $tokenName = '_token') {
    if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    if (preg_match('/<input[^>]+name="' . $tokenName . '"[^>]+value="([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    return null;
}

function extractAlerts($html) {
    $alerts = [];
    
    // Laravel success/error session flashes
    if (preg_match('/<div[^>]*class="[^"]*alert[^"]*alert-success[^"]*"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
        $alerts[] = ['type' => 'success', 'message' => strip_tags($matches[1])];
    }
    
    if (preg_match('/<div[^>]*class="[^"]*alert[^"]*alert-danger[^"]*"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
        $alerts[] = ['type' => 'error', 'message' => strip_tags($matches[1])];
    }
    
    // Bootstrap validation errors
    if (preg_match_all('/<div[^>]*class="[^"]*invalid-feedback[^"]*"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
        foreach ($matches[1] as $match) {
            $alerts[] = ['type' => 'validation', 'message' => strip_tags($match)];
        }
    }
    
    // SweetAlert scripts
    if (preg_match('/Swal\.fire\s*\(\s*{([^}]+)}/s', $html, $matches)) {
        $alerts[] = ['type' => 'swal', 'content' => $matches[1]];
    }
    
    return $alerts;
}

function extractServicesList($html) {
    $services = [];
    
    // Extract table rows
    if (preg_match('/<tbody[^>]*>(.*?)<\/tbody>/s', $html, $tbodyMatch)) {
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $tbodyMatch[1], $rowMatches)) {
            foreach ($rowMatches[1] as $row) {
                if (preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $row, $cellMatches)) {
                    if (count($cellMatches[1]) >= 4) {
                        $services[] = [
                            'name' => trim(strip_tags($cellMatches[1][0])),
                            'description' => trim(strip_tags($cellMatches[1][1])),
                            'price' => trim(strip_tags($cellMatches[1][2])),
                            'duration' => trim(strip_tags($cellMatches[1][3])),
                        ];
                    }
                }
            }
        }
    }
    
    return $services;
}

echo "=== BROWSER-BASED SERVICIOS MODULE TESTING ===\n";
echo "Simulating real user interaction with the web interface\n\n";

// Step 1: Authentication
echo "1. AUTHENTICATION PROCESS\n";
echo "Loading login page...\n";
$loginPage = makeBrowserRequest("$baseUrl/login", null, $cookieJar);
echo "Login page loaded: HTTP {$loginPage['http_code']}\n";

if ($loginPage['http_code'] === 200) {
    $csrfToken = extractToken($loginPage['body']);
    if ($csrfToken) {
        echo "CSRF token extracted: " . substr($csrfToken, 0, 10) . "...\n";
        
        $loginData = http_build_query([
            '_token' => $csrfToken,
            'email' => 'admin@taller.com',
            'password' => 'admin123'
        ]);
        
        echo "Submitting login credentials...\n";
        $loginResponse = makeBrowserRequest("$baseUrl/login", $loginData, $cookieJar);
        echo "Login response: HTTP {$loginResponse['http_code']}\n";
        echo "Redirected to: {$loginResponse['final_url']}\n";
        
        if (strpos($loginResponse['final_url'], 'dashboard') !== false) {
            echo "✓ Login successful - redirected to dashboard\n";
        } else {
            echo "✗ Login may have failed\n";
        }
    } else {
        echo "✗ Could not extract CSRF token from login page\n";
    }
} else {
    echo "✗ Failed to load login page\n";
}

// Step 2: Access Servicios Module
echo "\n2. ACCESSING SERVICIOS MODULE\n";
$serviciosIndex = makeBrowserRequest("$baseUrl/servicios", null, $cookieJar);
echo "Servicios index page: HTTP {$serviciosIndex['http_code']}\n";

if ($serviciosIndex['http_code'] === 200) {
    echo "✓ Successfully accessed Servicios module\n";
    
    // Extract current services list
    $currentServices = extractServicesList($serviciosIndex['body']);
    echo "Current services found: " . count($currentServices) . "\n";
    
    foreach ($currentServices as $i => $service) {
        if ($i < 3) { // Show first few services
            echo "  - {$service['name']} (Price: {$service['price']})\n";
        }
    }
    if (count($currentServices) > 3) {
        echo "  ... and " . (count($currentServices) - 3) . " more\n";
    }
} else {
    echo "✗ Failed to access Servicios module\n";
}

// Step 3: Test Service Creation
echo "\n3. SERVICE CREATION TEST\n";
$createPage = makeBrowserRequest("$baseUrl/servicios/create", null, $cookieJar);
echo "Create form page: HTTP {$createPage['http_code']}\n";

if ($createPage['http_code'] === 200) {
    echo "✓ Create form loaded successfully\n";
    
    $createToken = extractToken($createPage['body']);
    if ($createToken) {
        echo "CSRF token for creation: " . substr($createToken, 0, 10) . "...\n";
        
        $serviceData = http_build_query([
            '_token' => $createToken,
            'name' => 'Browser Test Service ' . date('H:i:s'),
            'description' => 'This service was created through browser-based testing to verify CRUD operations and alert functionality.',
            'price' => '75.00',
            'duration_hours' => '1.5',
            'status' => '1'
        ]);
        
        echo "Submitting new service data...\n";
        $createResponse = makeBrowserRequest("$baseUrl/servicios", $serviceData, $cookieJar);
        echo "Create response: HTTP {$createResponse['http_code']}\n";
        echo "Final URL: {$createResponse['final_url']}\n";
        
        // Check for alerts in the response
        $alerts = extractAlerts($createResponse['body']);
        echo "Alerts detected:\n";
        foreach ($alerts as $alert) {
            echo "  - {$alert['type']}: " . substr($alert['message'], 0, 100) . "\n";
        }
        
        if ($createResponse['http_code'] === 200 && strpos($createResponse['final_url'], 'servicios') !== false) {
            echo "✓ Service creation form submitted successfully\n";
        }
    } else {
        echo "✗ Could not extract CSRF token from create form\n";
    }
} else {
    echo "✗ Failed to load create form\n";
}

// Step 4: Verify Service in List
echo "\n4. VERIFICATION OF CREATED SERVICE\n";
$updatedIndex = makeBrowserRequest("$baseUrl/servicios", null, $cookieJar);
echo "Updated index page: HTTP {$updatedIndex['http_code']}\n";

if ($updatedIndex['http_code'] === 200) {
    $updatedServices = extractServicesList($updatedIndex['body']);
    echo "Services after creation: " . count($updatedServices) . "\n";
    
    // Check for alerts on the index page (success messages)
    $indexAlerts = extractAlerts($updatedIndex['body']);
    echo "Index page alerts:\n";
    foreach ($indexAlerts as $alert) {
        echo "  - {$alert['type']}: " . substr($alert['message'], 0, 100) . "\n";
    }
    
    // Look for the newly created service
    $newServiceFound = false;
    foreach ($updatedServices as $service) {
        if (strpos($service['name'], 'Browser Test Service') !== false) {
            $newServiceFound = true;
            echo "✓ New service found in list: {$service['name']}\n";
            echo "  Price: {$service['price']}, Duration: {$service['duration']}\n";
            break;
        }
    }
    
    if (!$newServiceFound) {
        echo "✗ New service not found in the list\n";
        echo "Recent services:\n";
        foreach (array_slice($updatedServices, 0, 3) as $service) {
            echo "  - {$service['name']}\n";
        }
    }
}

// Step 5: Test Validation with Invalid Data
echo "\n5. FORM VALIDATION TESTING\n";
$validationTestPage = makeBrowserRequest("$baseUrl/servicios/create", null, $cookieJar);

if ($validationTestPage['http_code'] === 200) {
    $validationToken = extractToken($validationTestPage['body']);
    
    if ($validationToken) {
        echo "Testing validation with empty required fields...\n";
        $invalidData = http_build_query([
            '_token' => $validationToken,
            'name' => '',  // Empty required field
            'description' => 'Test description',
            'price' => '50.00',
            'duration_hours' => '1.0',
            'status' => '1'
        ]);
        
        $validationResponse = makeBrowserRequest("$baseUrl/servicios", $invalidData, $cookieJar);
        echo "Validation test response: HTTP {$validationResponse['http_code']}\n";
        
        $validationAlerts = extractAlerts($validationResponse['body']);
        echo "Validation alerts:\n";
        foreach ($validationAlerts as $alert) {
            echo "  - {$alert['type']}: " . substr($alert['message'], 0, 150) . "\n";
        }
        
        if (count($validationAlerts) > 0) {
            echo "✓ Validation working - errors detected for invalid data\n";
        } else {
            echo "? No validation alerts detected (may need different detection method)\n";
        }
    }
}

// Step 6: Test Negative Price Validation
echo "\n6. NEGATIVE PRICE VALIDATION TEST\n";
$negativeTestPage = makeBrowserRequest("$baseUrl/servicios/create", null, $cookieJar);

if ($negativeTestPage['http_code'] === 200) {
    $negativeToken = extractToken($negativeTestPage['body']);
    
    if ($negativeToken) {
        echo "Testing with negative price...\n";
        $negativeData = http_build_query([
            '_token' => $negativeToken,
            'name' => 'Negative Price Test',
            'description' => 'Testing negative price validation',
            'price' => '-25.00',  // Invalid negative price
            'duration_hours' => '1.0',
            'status' => '1'
        ]);
        
        $negativeResponse = makeBrowserRequest("$baseUrl/servicios", $negativeData, $cookieJar);
        echo "Negative price test response: HTTP {$negativeResponse['http_code']}\n";
        
        $negativeAlerts = extractAlerts($negativeResponse['body']);
        echo "Negative price validation alerts:\n";
        foreach ($negativeAlerts as $alert) {
            echo "  - {$alert['type']}: " . substr($alert['message'], 0, 150) . "\n";
        }
        
        if (count($negativeAlerts) > 0) {
            echo "✓ Price validation working - errors detected for negative price\n";
        }
    }
}

// Clean up
unlink($cookieJar);

echo "\n=== BROWSER-BASED TEST COMPLETED ===\n";
echo "This test simulated real user interactions with the Servicios module.\n";
echo "Check the results above for alert system functionality and form validation.\n";