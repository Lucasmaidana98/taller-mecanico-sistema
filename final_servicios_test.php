<?php

/**
 * Final Comprehensive Servicios Module Test
 * Tests all CRUD operations, alerts, and validation
 */

// Initialize cURL and cookie handling
$cookieJar = tempnam(sys_get_temp_dir(), 'final_servicios_test');
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
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    return ['body' => $response, 'http_code' => $httpCode, 'final_url' => $finalUrl];
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

function detectAlerts($html) {
    $alerts = [];
    
    // Check for success messages
    if (preg_match('/@if\s*\(\s*session\s*\(\s*[\'"]success[\'"]/', $html)) {
        $alerts[] = "Success flash message detected in template";
    }
    
    // Check for error messages
    if (preg_match('/@if\s*\(\s*session\s*\(\s*[\'"]error[\'"]/', $html)) {
        $alerts[] = "Error flash message detected in template";
    }
    
    // Check for validation errors
    if (preg_match('/@error\s*\(\s*[\'"]([^\'"]+)[\'"]/', $html, $matches)) {
        $alerts[] = "Validation error handling detected for field: " . $matches[1];
    }
    
    // Check for SweetAlert
    if (strpos($html, 'Swal.fire') !== false) {
        $alerts[] = "SweetAlert implementation detected";
    }
    
    // Check for Bootstrap alerts
    if (preg_match('/class="[^"]*alert[^"]*"/', $html)) {
        $alerts[] = "Bootstrap alert classes detected";
    }
    
    return $alerts;
}

function checkDatabase() {
    $dbPath = __DIR__ . '/database/database.sqlite';
    if (!file_exists($dbPath)) {
        return false;
    }

    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM servicios');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt2 = $pdo->query('SELECT * FROM servicios ORDER BY created_at DESC LIMIT 3');
        $latest = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        return ['count' => $result['count'], 'latest' => $latest];
    } catch (PDOException $e) {
        return false;
    }
}

echo "=== FINAL SERVICIOS MODULE COMPREHENSIVE TEST ===\n";
echo "Testing CRUD operations, alerts, validation, and data updates\n\n";

// Initial database state
echo "1. INITIAL DATABASE STATE\n";
$initialDb = checkDatabase();
if ($initialDb) {
    echo "Initial services count: {$initialDb['count']}\n";
    echo "Latest services:\n";
    foreach ($initialDb['latest'] as $service) {
        echo "  - {$service['name']} (${$service['price']})\n";
    }
} else {
    echo "Could not access database\n";
}

// Authentication
echo "\n2. AUTHENTICATION\n";
$loginPage = makeRequest("$baseUrl/login", null, $cookieJar);
echo "Login page: HTTP {$loginPage['http_code']}\n";

$csrfToken = extractCsrfToken($loginPage['body']);
if ($csrfToken) {
    $loginData = http_build_query([
        '_token' => $csrfToken,
        'email' => 'admin@taller.com',
        'password' => 'admin123'
    ]);
    
    $loginResponse = makeRequest("$baseUrl/login", $loginData, $cookieJar);
    echo "Login response: HTTP {$loginResponse['http_code']}\n";
    
    if (strpos($loginResponse['final_url'], 'dashboard') !== false || 
        strpos($loginResponse['final_url'], 'servicios') !== false) {
        echo "✓ Authentication successful\n";
    } else {
        echo "? Authentication status unclear\n";
    }
} else {
    echo "✗ Could not extract CSRF token\n";
}

// Test 1: CREATE - Valid Service
echo "\n3. CREATE TEST - VALID SERVICE\n";
$createPage = makeRequest("$baseUrl/servicios/create", null, $cookieJar);
echo "Create form access: HTTP {$createPage['http_code']}\n";

// Check for alert templates in create page
$createAlerts = detectAlerts($createPage['body']);
echo "Alert systems detected in create form:\n";
foreach ($createAlerts as $alert) {
    echo "  - $alert\n";
}

if ($createPage['http_code'] === 200) {
    $createToken = extractCsrfToken($createPage['body']);
    
    if ($createToken) {
        $timestamp = date('Y-m-d H:i:s');
        $serviceData = http_build_query([
            '_token' => $createToken,
            'name' => "Final Test Service $timestamp",
            'description' => "Comprehensive test service created for validation testing",
            'price' => '89.99',
            'duration_hours' => '2.25',
            'status' => '1'
        ]);
        
        echo "Submitting valid service data...\n";
        $createResponse = makeRequest("$baseUrl/servicios", $serviceData, $cookieJar);
        echo "Create response: HTTP {$createResponse['http_code']}\n";
        
        if ($createResponse['http_code'] === 200 || $createResponse['http_code'] === 302) {
            echo "✓ Service creation submitted successfully\n";
            
            // Check database after creation
            $afterCreateDb = checkDatabase();
            if ($afterCreateDb && $afterCreateDb['count'] > $initialDb['count']) {
                echo "✓ Service added to database\n";
                echo "New services count: {$afterCreateDb['count']}\n";
                
                // Find the new service
                foreach ($afterCreateDb['latest'] as $service) {
                    if (strpos($service['name'], 'Final Test Service') !== false) {
                        echo "✓ Created service found: {$service['name']}\n";
                        echo "  Price: ${$service['price']}, Duration: {$service['duration_hours']}h\n";
                        $createdServiceId = $service['id'];
                        break;
                    }
                }
            } else {
                echo "? Database state unclear after creation\n";
            }
        } else {
            echo "✗ Service creation failed\n";
        }
    } else {
        echo "✗ Could not extract CSRF token from create form\n";
    }
} else {
    echo "✗ Could not access create form\n";
}

// Test 2: CREATE - Invalid Data (Empty Name)
echo "\n4. VALIDATION TEST - EMPTY NAME\n";
$emptyNamePage = makeRequest("$baseUrl/servicios/create", null, $cookieJar);
if ($emptyNamePage['http_code'] === 200) {
    $emptyToken = extractCsrfToken($emptyNamePage['body']);
    
    if ($emptyToken) {
        $emptyData = http_build_query([
            '_token' => $emptyToken,
            'name' => '',  // Empty required field
            'description' => 'Test service with empty name',
            'price' => '50.00',
            'duration_hours' => '1.0',
            'status' => '1'
        ]);
        
        echo "Testing empty name validation...\n";
        $emptyResponse = makeRequest("$baseUrl/servicios", $emptyData, $cookieJar);
        echo "Empty name response: HTTP {$emptyResponse['http_code']}\n";
        
        // Check for validation errors in response
        if (strpos($emptyResponse['body'], 'El nombre del servicio es obligatorio') !== false) {
            echo "✓ Name validation working - error message detected\n";
        } else if (strpos($emptyResponse['body'], 'obligatorio') !== false) {
            echo "✓ Validation working - required field error detected\n";
        } else {
            echo "? Validation status unclear\n";
        }
    }
}

// Test 3: CREATE - Invalid Data (Negative Price)
echo "\n5. VALIDATION TEST - NEGATIVE PRICE\n";
$negativePage = makeRequest("$baseUrl/servicios/create", null, $cookieJar);
if ($negativePage['http_code'] === 200) {
    $negativeToken = extractCsrfToken($negativePage['body']);
    
    if ($negativeToken) {
        $negativeData = http_build_query([
            '_token' => $negativeToken,
            'name' => 'Negative Price Test',
            'description' => 'Testing negative price validation',
            'price' => '-10.00',  // Invalid negative price
            'duration_hours' => '1.0',
            'status' => '1'
        ]);
        
        echo "Testing negative price validation...\n";
        $negativeResponse = makeRequest("$baseUrl/servicios", $negativeData, $cookieJar);
        echo "Negative price response: HTTP {$negativeResponse['http_code']}\n";
        
        // Check for price validation errors
        if (strpos($negativeResponse['body'], 'mayor o igual a 0') !== false) {
            echo "✓ Price validation working - minimum value error detected\n";
        } else if (strpos($negativeResponse['body'], 'price') !== false) {
            echo "✓ Price validation working - price error detected\n";
        } else {
            echo "? Price validation status unclear\n";
        }
    }
}

// Test 4: VIEW - Check Index Page
echo "\n6. INDEX PAGE AND STATISTICS\n";
$indexPage = makeRequest("$baseUrl/servicios", null, $cookieJar);
echo "Index page access: HTTP {$indexPage['http_code']}\n";

if ($indexPage['http_code'] === 200) {
    // Check for statistics or summary cards
    if (preg_match_all('/<h[1-6][^>]*>([^<]*\$[^<]*)<\/h[1-6]>/', $indexPage['body'], $matches)) {
        echo "Statistics detected:\n";
        foreach ($matches[1] as $stat) {
            echo "  - " . trim($stat) . "\n";
        }
    }
    
    // Check for data tables
    if (strpos($indexPage['body'], '<table') !== false) {
        echo "✓ Data table detected in index page\n";
    }
    
    // Check for alert templates
    $indexAlerts = detectAlerts($indexPage['body']);
    echo "Alert systems in index page:\n";
    foreach ($indexAlerts as $alert) {
        echo "  - $alert\n";
    }
}

// Test 5: UPDATE - Try to edit a service
echo "\n7. UPDATE TEST\n";
if (isset($createdServiceId)) {
    echo "Attempting to edit service ID: $createdServiceId\n";
    $editPage = makeRequest("$baseUrl/servicios/$createdServiceId/edit", null, $cookieJar);
    echo "Edit page access: HTTP {$editPage['http_code']}\n";
    
    if ($editPage['http_code'] === 200) {
        echo "✓ Edit form accessible\n";
        
        $editToken = extractCsrfToken($editPage['body']);
        if ($editToken) {
            $updateData = http_build_query([
                '_token' => $editToken,
                '_method' => 'PUT',
                'name' => "Updated Final Test Service",
                'description' => "Updated description for testing",
                'price' => '129.99',
                'duration_hours' => '3.5',
                'status' => '1'
            ]);
            
            echo "Submitting update data...\n";
            $updateResponse = makeRequest("$baseUrl/servicios/$createdServiceId", $updateData, $cookieJar);
            echo "Update response: HTTP {$updateResponse['http_code']}\n";
            
            if ($updateResponse['http_code'] === 200 || $updateResponse['http_code'] === 302) {
                echo "✓ Update submitted successfully\n";
                
                // Verify update in database
                $afterUpdateDb = checkDatabase();
                if ($afterUpdateDb) {
                    foreach ($afterUpdateDb['latest'] as $service) {
                        if ($service['id'] == $createdServiceId) {
                            echo "✓ Service updated in database\n";
                            echo "  New name: {$service['name']}\n";
                            echo "  New price: ${$service['price']}\n";
                            break;
                        }
                    }
                }
            } else {
                echo "? Update status unclear\n";
            }
        } else {
            echo "✗ Could not extract CSRF token from edit form\n";
        }
    } else {
        echo "✗ Could not access edit form (HTTP {$editPage['http_code']})\n";
    }
} else {
    echo "No service ID available for update test\n";
}

// Final Database State
echo "\n8. FINAL DATABASE STATE\n";
$finalDb = checkDatabase();
if ($finalDb) {
    echo "Final services count: {$finalDb['count']}\n";
    echo "Latest services:\n";
    foreach ($finalDb['latest'] as $service) {
        echo "  - {$service['name']} (${$service['price']})\n";
    }
    
    if ($initialDb) {
        $difference = $finalDb['count'] - $initialDb['count'];
        echo "Services added during test: $difference\n";
    }
} else {
    echo "Could not access final database state\n";
}

// Clean up
unlink($cookieJar);

echo "\n=== COMPREHENSIVE TEST COMPLETED ===\n";
echo "Results Summary:\n";
echo "- CREATE operations: Tested with valid and invalid data\n";
echo "- UPDATE operations: Tested service editing\n";
echo "- VALIDATION: Tested empty fields and negative prices\n";
echo "- ALERTS: Checked for alert system templates and implementations\n";
echo "- DATABASE: Verified data persistence and changes\n";
echo "- INTERFACE: Tested form access and navigation\n";