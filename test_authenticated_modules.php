<?php

/**
 * Authenticated Testing Script for Servicios and Empleados Modules
 * Laravel Taller Management System
 */

echo "=== AUTHENTICATED TESTING OF SERVICIOS AND EMPLEADOS MODULES ===\n\n";

$baseUrl = 'http://0.0.0.0:8001';
$cookieJar = '/tmp/cookies.txt';

// Test credentials from seeders
$credentials = [
    'admin' => ['email' => 'admin@taller.com', 'password' => 'admin123'],
    'mecanico' => ['email' => 'mecanico@taller.com', 'password' => 'mecanico123'],
    'recepcionista' => ['email' => 'recepcion@taller.com', 'password' => 'recepcion123']
];

$testResults = [
    'authentication' => [],
    'servicios' => [],
    'empleados' => []
];

/**
 * Initialize curl with common options
 */
function initCurl($url) {
    global $cookieJar;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Testing-Script/1.0)',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.8',
            'Connection: keep-alive',
            'Cache-Control: no-cache'
        ]
    ]);
    
    return $ch;
}

/**
 * Extract CSRF token from HTML
 */
function extractCsrfToken($html) {
    if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * Login function
 */
function login($userType) {
    global $baseUrl, $credentials, $cookieJar;
    
    echo "🔐 Attempting login as $userType...\n";
    
    // Clear cookies
    if (file_exists($cookieJar)) {
        unlink($cookieJar);
    }
    
    // Get login page and CSRF token
    $ch = initCurl($baseUrl . '/login');
    $loginPage = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "❌ Failed to access login page (HTTP: $httpCode)\n";
        return false;
    }
    
    $csrfToken = extractCsrfToken($loginPage);
    if (!$csrfToken) {
        echo "❌ Could not extract CSRF token\n";
        return false;
    }
    
    // Perform login
    $ch = initCurl($baseUrl . '/login');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            '_token' => $csrfToken,
            'email' => $credentials[$userType]['email'],
            'password' => $credentials[$userType]['password'],
            'remember' => 'on'
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Referer: ' . $baseUrl . '/login'
        ]
    ]);
    
    $loginResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    // Check if login was successful (should redirect to dashboard)
    if (strpos($finalUrl, 'dashboard') !== false || $httpCode === 200) {
        echo "✅ Login successful as $userType\n";
        return true;
    } else {
        echo "❌ Login failed as $userType (HTTP: $httpCode, Final URL: $finalUrl)\n";
        return false;
    }
}

/**
 * Test endpoint with authentication
 */
function testAuthenticatedEndpoint($url, $testName, $expectedElements = []) {
    global $baseUrl;
    
    echo "Testing: $testName\n";
    echo "URL: $url\n";
    
    $ch = initCurl($url);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    $result = [
        'test_name' => $testName,
        'success' => false,
        'http_code' => $httpCode,
        'errors' => [],
        'warnings' => [],
        'ui_elements' => [],
        'response_size' => strlen($response)
    ];
    
    echo "HTTP Status: $httpCode\n";
    echo "Final URL: $finalUrl\n";
    
    if ($httpCode !== 200) {
        $result['errors'][] = "HTTP error: $httpCode";
        echo "❌ FAILED - HTTP error: $httpCode\n";
    } elseif (strpos($finalUrl, 'login') !== false) {
        $result['errors'][] = "Redirected to login - authentication failed";
        echo "❌ FAILED - Redirected to login\n";
    } elseif (!$response) {
        $result['errors'][] = "Empty response";
        echo "❌ FAILED - Empty response\n";
    } else {
        $result['success'] = true;
        echo "✅ SUCCESS - Page loaded successfully\n";
        
        // Analyze UI elements
        if (preg_match_all('/<form[^>]*>/i', $response, $matches)) {
            $result['ui_elements']['forms'] = count($matches[0]);
        }
        if (preg_match_all('/<table[^>]*>/i', $response, $matches)) {
            $result['ui_elements']['tables'] = count($matches[0]);
        }
        if (preg_match_all('/<button[^>]*>|<input[^>]*type=["\']button["\'][^>]*>/i', $response, $matches)) {
            $result['ui_elements']['buttons'] = count($matches[0]);
        }
        if (preg_match_all('/<a[^>]*class="[^"]*btn[^"]*"[^>]*>/i', $response, $matches)) {
            $result['ui_elements']['button_links'] = count($matches[0]);
        }
        
        // Check for specific elements
        if (strpos($response, 'DataTable') !== false || strpos($response, 'dataTables') !== false) {
            $result['ui_elements']['datatables'] = true;
        }
        if (strpos($response, 'alert-success') !== false) {
            $result['ui_elements']['success_messages'] = true;
        }
        if (strpos($response, 'alert-danger') !== false || strpos($response, 'alert-error') !== false) {
            $result['ui_elements']['error_messages'] = true;
        }
        
        // Check for pagination
        if (strpos($response, 'pagination') !== false) {
            $result['ui_elements']['pagination'] = true;
        }
        
        // Look for validation classes
        if (strpos($response, 'is-invalid') !== false) {
            $result['warnings'][] = 'Form validation errors detected';
        }
        
        // Check for JavaScript errors in console (basic check)
        if (preg_match_all('/<script[^>]*>.*?console\.(error|warn).*?<\/script>/is', $response, $matches)) {
            $result['warnings'][] = 'Potential JavaScript console errors detected';
        }
    }
    
    if (!empty($result['ui_elements'])) {
        echo "📋 UI Elements detected:\n";
        foreach ($result['ui_elements'] as $element => $count) {
            if (is_bool($count)) {
                echo "   - $element: " . ($count ? "Yes" : "No") . "\n";
            } else {
                echo "   - $element: $count\n";
            }
        }
    }
    
    if (!empty($result['warnings'])) {
        echo "⚠️  WARNINGS:\n";
        foreach ($result['warnings'] as $warning) {
            echo "   - $warning\n";
        }
    }
    
    echo "\n" . str_repeat("-", 60) . "\n\n";
    
    return $result;
}

/**
 * Test form submission
 */
function testFormSubmission($url, $formData, $testName) {
    global $baseUrl;
    
    echo "Testing Form: $testName\n";
    echo "URL: $url\n";
    
    // First get the form page to get CSRF token
    $ch = initCurl($url);
    $formPage = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "❌ FAILED - Could not access form page (HTTP: $httpCode)\n";
        return ['success' => false, 'error' => "Could not access form page"];
    }
    
    $csrfToken = extractCsrfToken($formPage);
    if (!$csrfToken) {
        echo "❌ FAILED - Could not extract CSRF token\n";
        return ['success' => false, 'error' => "Could not extract CSRF token"];
    }
    
    // Add CSRF token to form data
    $formData['_token'] = $csrfToken;
    
    // Submit the form
    $ch = initCurl($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($formData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Referer: ' . $url
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    echo "HTTP Status: $httpCode\n";
    echo "Final URL: $finalUrl\n";
    
    $result = [
        'test_name' => $testName,
        'success' => false,
        'http_code' => $httpCode,
        'final_url' => $finalUrl,
        'response_size' => strlen($response)
    ];
    
    // Form submission is typically successful if:
    // 1. HTTP 302 redirect (successful creation/update)
    // 2. HTTP 200 with success message
    // 3. Redirects to index page
    
    if ($httpCode === 302 || ($httpCode === 200 && strpos($response, 'alert-success') !== false)) {
        $result['success'] = true;
        echo "✅ SUCCESS - Form submitted successfully\n";
    } elseif (strpos($response, 'is-invalid') !== false || strpos($response, 'alert-danger') !== false) {
        echo "⚠️  PARTIAL - Form validation errors detected\n";
        $result['validation_errors'] = true;
    } else {
        echo "❌ FAILED - Form submission failed\n";
    }
    
    echo "\n" . str_repeat("-", 60) . "\n\n";
    
    return $result;
}

// ============================================
// AUTHENTICATION TESTING
// ============================================

echo "🔐 TESTING AUTHENTICATION\n";
echo str_repeat("=", 60) . "\n\n";

foreach (['admin', 'mecanico', 'recepcionista'] as $userType) {
    $testResults['authentication'][$userType] = login($userType);
    echo "\n";
}

// Continue with admin user for main testing
if (!$testResults['authentication']['admin']) {
    echo "❌ Cannot continue testing without admin authentication\n";
    exit(1);
}

login('admin'); // Ensure we're logged in as admin

// ============================================
// SERVICIOS MODULE TESTING
// ============================================

echo "🔧 TESTING SERVICIOS MODULE (Authenticated)\n";
echo str_repeat("=", 60) . "\n\n";

// Test index page
$testResults['servicios']['index'] = testAuthenticatedEndpoint(
    $baseUrl . '/servicios',
    'Servicios Index Page (Authenticated)'
);

// Test create page
$testResults['servicios']['create_page'] = testAuthenticatedEndpoint(
    $baseUrl . '/servicios/create',
    'Servicios Create Form Page'
);

// Test create form submission (with sample data)
$servicioData = [
    'name' => 'Test Service - ' . date('Y-m-d H:i:s'),
    'description' => 'This is a test service created by the automated testing script.',
    'price' => '99.99',
    'duration_hours' => '2.5',
    'status' => '1'
];

$testResults['servicios']['create_form'] = testFormSubmission(
    $baseUrl . '/servicios',
    $servicioData,
    'Create New Servicio'
);

// Test show page (ID 1 should exist from seeder)
$testResults['servicios']['show'] = testAuthenticatedEndpoint(
    $baseUrl . '/servicios/1',
    'Show Servicio Details (ID: 1)'
);

// Test edit page
$testResults['servicios']['edit_page'] = testAuthenticatedEndpoint(
    $baseUrl . '/servicios/1/edit',
    'Edit Servicio Form (ID: 1)'
);

// Test edit form submission
$editServicioData = [
    'name' => 'Updated Test Service - ' . date('Y-m-d H:i:s'),
    'description' => 'This service has been updated by the automated testing script.',
    'price' => '149.99',
    'duration_hours' => '3.0',
    'status' => '1',
    '_method' => 'PUT'
];

$testResults['servicios']['edit_form'] = testFormSubmission(
    $baseUrl . '/servicios/1',
    $editServicioData,
    'Update Servicio (ID: 1)'
);

// Test search functionality
$testResults['servicios']['search'] = testAuthenticatedEndpoint(
    $baseUrl . '/servicios?search=Cambio',
    'Search Servicios'
);

// Test filters
$testResults['servicios']['filter'] = testAuthenticatedEndpoint(
    $baseUrl . '/servicios?status=1&price_min=100&price_max=500',
    'Filter Servicios by Status and Price'
);

// ============================================
// EMPLEADOS MODULE TESTING
// ============================================

echo "👨‍💼 TESTING EMPLEADOS MODULE (Authenticated)\n";
echo str_repeat("=", 60) . "\n\n";

// Test index page
$testResults['empleados']['index'] = testAuthenticatedEndpoint(
    $baseUrl . '/empleados',
    'Empleados Index Page (Authenticated)'
);

// Test create page
$testResults['empleados']['create_page'] = testAuthenticatedEndpoint(
    $baseUrl . '/empleados/create',
    'Empleados Create Form Page'
);

// Test create form submission (with sample data)
$empleadoData = [
    'name' => 'Test Employee - ' . date('Y-m-d H:i:s'),
    'email' => 'test.employee.' . time() . '@taller.com',
    'phone' => '+595-21-999999',
    'position' => 'Test Position',
    'salary' => '3500000.00',
    'hire_date' => date('Y-m-d'),
    'status' => '1'
];

$testResults['empleados']['create_form'] = testFormSubmission(
    $baseUrl . '/empleados',
    $empleadoData,
    'Create New Empleado'
);

// Test show page (ID 1 should exist from seeder)
$testResults['empleados']['show'] = testAuthenticatedEndpoint(
    $baseUrl . '/empleados/1',
    'Show Empleado Details (ID: 1)'
);

// Test edit page
$testResults['empleados']['edit_page'] = testAuthenticatedEndpoint(
    $baseUrl . '/empleados/1/edit',
    'Edit Empleado Form (ID: 1)'
);

// Test edit form submission
$editEmpleadoData = [
    'name' => 'Updated Test Employee - ' . date('Y-m-d H:i:s'),
    'email' => 'roberto.silva@taller.com', // Keep original email to avoid unique constraint
    'phone' => '+595-21-111111',
    'position' => 'Updated Test Position',
    'salary' => '4000000.00',
    'hire_date' => '2020-01-15',
    'status' => '1',
    '_method' => 'PUT'
];

$testResults['empleados']['edit_form'] = testFormSubmission(
    $baseUrl . '/empleados/1',
    $editEmpleadoData,
    'Update Empleado (ID: 1)'
);

// Test search functionality
$testResults['empleados']['search'] = testAuthenticatedEndpoint(
    $baseUrl . '/empleados?search=Roberto',
    'Search Empleados'
);

// Test filters
$testResults['empleados']['filter'] = testAuthenticatedEndpoint(
    $baseUrl . '/empleados?status=1&position=Mecánico',
    'Filter Empleados by Status and Position'
);

// ============================================
// SUMMARY REPORT
// ============================================

echo "📊 COMPREHENSIVE TESTING SUMMARY REPORT\n";
echo str_repeat("=", 60) . "\n\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

echo "AUTHENTICATION RESULTS:\n";
foreach ($testResults['authentication'] as $userType => $result) {
    $totalTests++;
    $status = $result ? '✅ PASS' : '❌ FAIL';
    echo "  $userType: $status\n";
    if ($result) $passedTests++; else $failedTests++;
}
echo "\n";

foreach (['servicios', 'empleados'] as $module) {
    echo strtoupper($module) . " MODULE RESULTS:\n";
    
    foreach ($testResults[$module] as $testName => $result) {
        $totalTests++;
        $status = $result['success'] ? '✅ PASS' : '❌ FAIL';
        
        if ($result['success']) {
            $passedTests++;
        } else {
            $failedTests++;
        }
        
        echo "  $testName: $status";
        
        if (isset($result['http_code'])) {
            echo " (HTTP: {$result['http_code']})";
        }
        
        if (isset($result['validation_errors']) && $result['validation_errors']) {
            echo " - Has validation system";
        }
        
        echo "\n";
    }
    echo "\n";
}

echo "OVERALL STATISTICS:\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests ✅\n";
echo "Failed: $failedTests ❌\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

// Detailed findings
echo "🔍 DETAILED FINDINGS:\n";
echo str_repeat("-", 40) . "\n";

// Check what UI elements were found across tests
$foundElements = [];
foreach ($testResults as $moduleTests) {
    if (is_array($moduleTests)) {
        foreach ($moduleTests as $test) {
            if (isset($test['ui_elements'])) {
                foreach ($test['ui_elements'] as $element => $count) {
                    if (!isset($foundElements[$element])) {
                        $foundElements[$element] = 0;
                    }
                    if (is_numeric($count)) {
                        $foundElements[$element] += $count;
                    } elseif ($count === true) {
                        $foundElements[$element]++;
                    }
                }
            }
        }
    }
}

if (!empty($foundElements)) {
    echo "\nUI ELEMENTS DETECTED ACROSS ALL TESTS:\n";
    foreach ($foundElements as $element => $count) {
        echo "• $element: $count occurrences\n";
    }
}

echo "\n✅ Comprehensive testing completed at " . date('Y-m-d H:i:s') . "\n";

// Clean up
if (file_exists($cookieJar)) {
    unlink($cookieJar);
}