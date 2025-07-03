<?php

/**
 * Comprehensive Testing Script for Servicios and Empleados Modules
 * Laravel Taller Management System
 */

echo "=== TESTING SERVICIOS AND EMPLEADOS MODULES ===\n\n";

$baseUrl = 'http://0.0.0.0:8001';
$testResults = [
    'servicios' => [],
    'empleados' => []
];

// Test configuration
$timeout = 30;
$userAgent = 'Mozilla/5.0 (compatible; Testing-Script/1.0)';

/**
 * Function to make HTTP requests with proper error handling
 */
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    global $timeout, $userAgent;
    
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", array_merge([
                'User-Agent: ' . $userAgent,
                'Accept: text/html,application/json,*/*',
                'Connection: close'
            ], $headers)),
            'content' => $data ? http_build_query($data) : null,
            'timeout' => $timeout,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    $httpCode = 200;
    
    if (isset($http_response_header)) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $httpCode = isset($matches[1]) ? (int)$matches[1] : 200;
    }
    
    return [
        'success' => $response !== false,
        'data' => $response,
        'http_code' => $httpCode,
        'headers' => $http_response_header ?? [],
        'error' => $response === false ? error_get_last()['message'] ?? 'Unknown error' : null
    ];
}

/**
 * Function to analyze HTML response for common issues
 */
function analyzeHtmlResponse($html, $testName) {
    $analysis = [
        'test_name' => $testName,
        'has_errors' => false,
        'errors' => [],
        'warnings' => [],
        'ui_elements' => []
    ];
    
    if (!$html) {
        $analysis['has_errors'] = true;
        $analysis['errors'][] = 'Empty response received';
        return $analysis;
    }
    
    // Check for Laravel errors
    if (strpos($html, 'Whoops\IgnoreErrorHandler') !== false || 
        strpos($html, 'ErrorException') !== false ||
        strpos($html, 'FatalErrorException') !== false) {
        $analysis['has_errors'] = true;
        $analysis['errors'][] = 'Laravel application error detected';
    }
    
    // Check for 404 errors
    if (strpos($html, '404') !== false && strpos($html, 'Not Found') !== false) {
        $analysis['has_errors'] = true;
        $analysis['errors'][] = '404 Page Not Found';
    }
    
    // Check for authentication redirects
    if (strpos($html, 'login') !== false && strpos($html, 'redirect') !== false) {
        $analysis['warnings'][] = 'Authentication may be required';
    }
    
    // Check for permission denied
    if (strpos($html, '403') !== false || strpos($html, 'Forbidden') !== false) {
        $analysis['has_errors'] = true;
        $analysis['errors'][] = '403 Forbidden - Permission denied';
    }
    
    // Check for forms
    if (preg_match_all('/<form[^>]*>/i', $html, $matches)) {
        $analysis['ui_elements']['forms'] = count($matches[0]);
    }
    
    // Check for tables
    if (preg_match_all('/<table[^>]*>/i', $html, $matches)) {
        $analysis['ui_elements']['tables'] = count($matches[0]);
    }
    
    // Check for buttons
    if (preg_match_all('/<button[^>]*>|<input[^>]*type=["\']button["\'][^>]*>/i', $html, $matches)) {
        $analysis['ui_elements']['buttons'] = count($matches[0]);
    }
    
    // Check for JavaScript errors (basic check)
    if (strpos($html, 'JavaScript') !== false && strpos($html, 'error') !== false) {
        $analysis['warnings'][] = 'Possible JavaScript error detected';
    }
    
    // Check for DataTables (specific to this application)
    if (strpos($html, 'dataTables') !== false || strpos($html, 'DataTable') !== false) {
        $analysis['ui_elements']['datatables'] = true;
    }
    
    // Check for Bootstrap components
    if (strpos($html, 'bootstrap') !== false) {
        $analysis['ui_elements']['bootstrap'] = true;
    }
    
    return $analysis;
}

/**
 * Test function for each endpoint
 */
function testEndpoint($url, $testName, $expectedElements = []) {
    echo "Testing: $testName\n";
    echo "URL: $url\n";
    
    $response = makeRequest($url);
    
    if (!$response['success']) {
        echo "❌ FAILED - Network error: " . $response['error'] . "\n";
        return ['success' => false, 'error' => $response['error'], 'test_name' => $testName];
    }
    
    echo "HTTP Status: " . $response['http_code'] . "\n";
    
    // Analyze the response
    $analysis = analyzeHtmlResponse($response['data'], $testName);
    
    if ($analysis['has_errors']) {
        echo "❌ FAILED - Errors found:\n";
        foreach ($analysis['errors'] as $error) {
            echo "   - $error\n";
        }
    } else {
        echo "✅ SUCCESS - Page loaded without errors\n";
    }
    
    if (!empty($analysis['warnings'])) {
        echo "⚠️  WARNINGS:\n";
        foreach ($analysis['warnings'] as $warning) {
            echo "   - $warning\n";
        }
    }
    
    if (!empty($analysis['ui_elements'])) {
        echo "📋 UI Elements detected:\n";
        foreach ($analysis['ui_elements'] as $element => $count) {
            if (is_bool($count)) {
                echo "   - $element: " . ($count ? "Yes" : "No") . "\n";
            } else {
                echo "   - $element: $count\n";
            }
        }
    }
    
    echo "\n" . str_repeat("-", 60) . "\n\n";
    
    return array_merge($analysis, [
        'success' => !$analysis['has_errors'],
        'http_code' => $response['http_code'],
        'response_size' => strlen($response['data'])
    ]);
}

// ============================================
// SERVICIOS MODULE TESTING
// ============================================

echo "🔧 TESTING SERVICIOS MODULE\n";
echo str_repeat("=", 60) . "\n\n";

// Test Servicios Index
$testResults['servicios']['index'] = testEndpoint(
    $baseUrl . '/servicios',
    'Servicios Index Page',
    ['tables', 'forms', 'buttons']
);

// Test Servicios Create
$testResults['servicios']['create'] = testEndpoint(
    $baseUrl . '/servicios/create',
    'Servicios Create Form',
    ['forms', 'buttons']
);

// Test Servicios with search
$testResults['servicios']['search'] = testEndpoint(
    $baseUrl . '/servicios?search=test',
    'Servicios Search Functionality',
    ['tables', 'forms']
);

// Test Servicios with filters
$testResults['servicios']['filter_active'] = testEndpoint(
    $baseUrl . '/servicios?status=1',
    'Servicios Filter by Active Status',
    ['tables']
);

$testResults['servicios']['filter_price'] = testEndpoint(
    $baseUrl . '/servicios?price_min=100&price_max=500',
    'Servicios Filter by Price Range',
    ['tables']
);

// Try to test show and edit with ID 1 (might exist if seeded)
$testResults['servicios']['show_1'] = testEndpoint(
    $baseUrl . '/servicios/1',
    'Servicios Show Details (ID: 1)',
    ['buttons']
);

$testResults['servicios']['edit_1'] = testEndpoint(
    $baseUrl . '/servicios/1/edit',
    'Servicios Edit Form (ID: 1)',
    ['forms', 'buttons']
);

// ============================================
// EMPLEADOS MODULE TESTING
// ============================================

echo "👨‍💼 TESTING EMPLEADOS MODULE\n";
echo str_repeat("=", 60) . "\n\n";

// Test Empleados Index
$testResults['empleados']['index'] = testEndpoint(
    $baseUrl . '/empleados',
    'Empleados Index Page',
    ['tables', 'forms', 'buttons']
);

// Test Empleados Create
$testResults['empleados']['create'] = testEndpoint(
    $baseUrl . '/empleados/create',
    'Empleados Create Form',
    ['forms', 'buttons']
);

// Test Empleados with search
$testResults['empleados']['search'] = testEndpoint(
    $baseUrl . '/empleados?search=test',
    'Empleados Search Functionality',
    ['tables', 'forms']
);

// Test Empleados with filters
$testResults['empleados']['filter_active'] = testEndpoint(
    $baseUrl . '/empleados?status=1',
    'Empleados Filter by Active Status',
    ['tables']
);

$testResults['empleados']['filter_position'] = testEndpoint(
    $baseUrl . '/empleados?position=mecánico',
    'Empleados Filter by Position',
    ['tables']
);

// Try to test show and edit with ID 1 (might exist if seeded)
$testResults['empleados']['show_1'] = testEndpoint(
    $baseUrl . '/empleados/1',
    'Empleados Show Details (ID: 1)',
    ['buttons']
);

$testResults['empleados']['edit_1'] = testEndpoint(
    $baseUrl . '/empleados/1/edit',
    'Empleados Edit Form (ID: 1)',
    ['forms', 'buttons']
);

// ============================================
// SUMMARY REPORT
// ============================================

echo "📊 TESTING SUMMARY REPORT\n";
echo str_repeat("=", 60) . "\n\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$warningTests = 0;

foreach ($testResults as $module => $tests) {
    echo strtoupper($module) . " MODULE RESULTS:\n";
    
    foreach ($tests as $testName => $result) {
        $totalTests++;
        $status = $result['success'] ? '✅ PASS' : '❌ FAIL';
        
        if ($result['success']) {
            $passedTests++;
        } else {
            $failedTests++;
        }
        
        if (!empty($result['warnings'])) {
            $warningTests++;
        }
        
        echo "  $testName: $status";
        
        if (isset($result['http_code'])) {
            echo " (HTTP: {$result['http_code']})";
        }
        
        if (!empty($result['errors'])) {
            echo " - " . implode(', ', $result['errors']);
        }
        
        echo "\n";
    }
    echo "\n";
}

echo "OVERALL STATISTICS:\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests ✅\n";
echo "Failed: $failedTests ❌\n";
echo "With Warnings: $warningTests ⚠️\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

// Recommendations
echo "🔍 RECOMMENDATIONS:\n";
echo str_repeat("-", 40) . "\n";

if ($failedTests > 0) {
    echo "• Check authentication and permissions configuration\n";
    echo "• Verify database seeding has created test data\n";
    echo "• Ensure all routes are properly defined\n";
    echo "• Check Laravel application logs for detailed errors\n";
}

if ($warningTests > 0) {
    echo "• Review authentication requirements for tested endpoints\n";
    echo "• Consider implementing guest access for testing purposes\n";
}

echo "\n✅ Testing completed at " . date('Y-m-d H:i:s') . "\n";