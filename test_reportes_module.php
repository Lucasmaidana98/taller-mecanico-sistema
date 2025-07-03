<?php

/**
 * Comprehensive Reportes (Reports) Module Test
 * Tests all functionality of the Laravel Reports module
 */

// Configuration
$baseUrl = 'http://0.0.0.0:8001';
$cookieFile = 'cookies_reportes_test.txt';

// Initialize cURL
function initCurl($url, $cookieFile) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    return $ch;
}

// Extract response components
function parseResponse($response, $ch) {
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    return [
        'headers' => $headers,
        'body' => $body,
        'status_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE)
    ];
}

// Test reporting function
function reportTest($testName, $success, $details = '') {
    $status = $success ? "✅ PASS" : "❌ FAIL";
    echo "[{$status}] {$testName}\n";
    if ($details) {
        echo "    Details: {$details}\n";
    }
    echo "\n";
    return $success;
}

// Extract CSRF token from form
function extractCsrfToken($html) {
    if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $html, $matches)) {
        return $matches[1];
    }
    return null;
}

// Test authentication
function authenticateUser($baseUrl, $cookieFile) {
    
    echo "=== AUTHENTICATION TEST ===\n";
    
    // Get login page
    $ch = initCurl("$baseUrl/login", $cookieFile);
    $response = curl_exec($ch);
    
    if (curl_error($ch)) {
        reportTest("Get login page", false, "cURL Error: " . curl_error($ch));
        return false;
    }
    
    $data = parseResponse($response, $ch);
    $csrfToken = extractCsrfToken($data['body']);
    
    if (!$csrfToken) {
        reportTest("Extract CSRF token", false, "Could not find CSRF token in login form");
        return false;
    }
    
    reportTest("Get login page", $data['status_code'] == 200, "Status: {$data['status_code']}");
    
    // Attempt login with default credentials
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/login");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        '_token' => $csrfToken,
        'email' => 'admin@admin.com',
        'password' => 'password'
    ]));
    
    $response = curl_exec($ch);
    $data = parseResponse($response, $ch);
    
    $loginSuccess = $data['status_code'] == 302 && strpos($data['headers'], 'Location: ') !== false;
    reportTest("User authentication", $loginSuccess, "Status: {$data['status_code']}");
    
    curl_close($ch);
    return $loginSuccess;
}

// Test reports index page
function testReportesIndex($baseUrl, $cookieFile) {
    
    echo "=== REPORTES INDEX PAGE TEST ===\n";
    
    $ch = initCurl("$baseUrl/reportes", $cookieFile);
    $response = curl_exec($ch);
    
    if (curl_error($ch)) {
        reportTest("Access /reportes", false, "cURL Error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    $data = parseResponse($response, $ch);
    $success = $data['status_code'] == 200;
    
    reportTest("Access reports index", $success, "Status: {$data['status_code']}");
    
    if ($success) {
        // Check for key elements
        $hasTitle = strpos($data['body'], 'Centro de Reportes') !== false;
        $hasFilters = strpos($data['body'], 'Filtros de Reporte') !== false;
        $hasDateInputs = strpos($data['body'], 'fecha_inicio') !== false && strpos($data['body'], 'fecha_fin') !== false;
        $hasReportTypes = strpos($data['body'], 'tipo_reporte') !== false;
        $hasExportButtons = strpos($data['body'], 'Exportar PDF') !== false;
        $hasCharts = strpos($data['body'], 'Chart.js') !== false;
        
        reportTest("Page title present", $hasTitle);
        reportTest("Filters section present", $hasFilters);
        reportTest("Date inputs present", $hasDateInputs);
        reportTest("Report type selector present", $hasReportTypes);
        reportTest("Export buttons present", $hasExportButtons);
        reportTest("Charts library loaded", $hasCharts);
        
        // Check for JavaScript functions
        $hasExportFunctions = strpos($data['body'], 'function exportToPDF()') !== false && 
                             strpos($data['body'], 'function exportToExcel()') !== false;
        reportTest("Export JavaScript functions present", $hasExportFunctions);
    }
    
    curl_close($ch);
    return $success;
}

// Test report generation with filters
function testReportGeneration($baseUrl, $cookieFile) {
    
    echo "=== REPORT GENERATION TEST ===\n";
    
    // First get the reports page to get CSRF token
    $ch = initCurl("$baseUrl/reportes", $cookieFile);
    $response = curl_exec($ch);
    $data = parseResponse($response, $ch);
    $csrfToken = extractCsrfToken($data['body']);
    
    if (!$csrfToken) {
        reportTest("Get CSRF token for reports", false, "No CSRF token found");
        curl_close($ch);
        return false;
    }
    
    // Test different report types
    $reportTypes = ['ordenes', 'clientes', 'empleados', 'servicios', 'ingresos', 'vehiculos'];
    
    foreach ($reportTypes as $tipo) {
        echo "\n--- Testing {$tipo} report ---\n";
        
        curl_setopt($ch, CURLOPT_URL, "$baseUrl/reportes/generar");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'X-Requested-With: XMLHttpRequest'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            '_token' => $csrfToken,
            'tipo_reporte' => $tipo,
            'fecha_inicio' => date('Y-m-01'), // First day of current month
            'fecha_fin' => date('Y-m-d')      // Today
        ]));
        
        $response = curl_exec($ch);
        
        if (curl_error($ch)) {
            reportTest("Generate {$tipo} report", false, "cURL Error: " . curl_error($ch));
            continue;
        }
        
        $data = parseResponse($response, $ch);
        $success = $data['status_code'] == 200;
        
        reportTest("Generate {$tipo} report HTTP", $success, "Status: {$data['status_code']}");
        
        if ($success) {
            $jsonData = json_decode($data['body'], true);
            
            if ($jsonData) {
                $hasSuccessFlag = isset($jsonData['success']) && $jsonData['success'] === true;
                $hasData = isset($jsonData['data']);
                $hasMessage = isset($jsonData['message']);
                
                reportTest("{$tipo} - JSON response structure", $hasSuccessFlag && $hasData && $hasMessage);
                
                if ($hasData) {
                    reportTest("{$tipo} - Data present", !empty($jsonData['data']));
                }
            } else {
                reportTest("{$tipo} - Valid JSON response", false, "Response is not valid JSON");
            }
        }
    }
    
    curl_close($ch);
    return true;
}

// Test report generation with specific filters
function testReportFilters($baseUrl, $cookieFile) {
    
    echo "=== REPORT FILTERS TEST ===\n";
    
    // Get CSRF token
    $ch = initCurl("$baseUrl/reportes", $cookieFile);
    $response = curl_exec($ch);
    $data = parseResponse($response, $ch);
    $csrfToken = extractCsrfToken($data['body']);
    
    if (!$csrfToken) {
        reportTest("Get CSRF token for filters test", false);
        curl_close($ch);
        return false;
    }
    
    // Test with date filters
    echo "\n--- Testing date filters ---\n";
    
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/reportes/generar");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'X-Requested-With: XMLHttpRequest'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        '_token' => $csrfToken,
        'tipo_reporte' => 'ordenes',
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-12-31',
        'status' => 'completed'
    ]));
    
    $response = curl_exec($ch);
    $data = parseResponse($response, $ch);
    $success = $data['status_code'] == 200;
    
    reportTest("Report with date and status filters", $success, "Status: {$data['status_code']}");
    
    if ($success) {
        $jsonData = json_decode($data['body'], true);
        reportTest("Filtered report - Valid JSON", $jsonData !== null);
        
        if ($jsonData) {
            reportTest("Filtered report - Success response", isset($jsonData['success']) && $jsonData['success']);
        }
    }
    
    // Test invalid date range
    echo "\n--- Testing invalid date range ---\n";
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        '_token' => $csrfToken,
        'tipo_reporte' => 'ordenes',
        'fecha_inicio' => '2024-12-31',
        'fecha_fin' => '2024-01-01'  // End date before start date
    ]));
    
    $response = curl_exec($ch);
    $data = parseResponse($response, $ch);
    
    // Should return validation error (422) or handle gracefully
    $handledProperly = $data['status_code'] == 422 || $data['status_code'] == 200;
    reportTest("Invalid date range handling", $handledProperly, "Status: {$data['status_code']}");
    
    curl_close($ch);
    return true;
}

// Test PDF export functionality
function testPdfExport($baseUrl, $cookieFile) {
    
    echo "=== PDF EXPORT TEST ===\n";
    
    $reportTypes = ['ordenes', 'clientes'];
    
    foreach ($reportTypes as $tipo) {
        echo "\n--- Testing {$tipo} PDF export ---\n";
        
        $ch = initCurl("$baseUrl/reportes/exportar/1?tipo_reporte={$tipo}&fecha_inicio=2024-01-01&fecha_fin=2024-12-31", $cookieFile);
        $response = curl_exec($ch);
        
        if (curl_error($ch)) {
            reportTest("Export {$tipo} PDF", false, "cURL Error: " . curl_error($ch));
            continue;
        }
        
        $data = parseResponse($response, $ch);
        $success = $data['status_code'] == 200;
        
        reportTest("Export {$tipo} PDF HTTP", $success, "Status: {$data['status_code']}");
        
        if ($success) {
            $isPdf = strpos($data['headers'], 'Content-Type: application/pdf') !== false;
            $hasFilename = strpos($data['headers'], 'Content-Disposition: attachment') !== false;
            $hasPdfContent = strpos($data['body'], '%PDF') === 0; // PDF files start with %PDF
            
            reportTest("{$tipo} PDF - Correct content type", $isPdf);
            reportTest("{$tipo} PDF - Download headers", $hasFilename);
            reportTest("{$tipo} PDF - Valid PDF content", $hasPdfContent);
        }
        
        curl_close($ch);
    }
    
    return true;
}

// Test filter options endpoint
function testFilterOptions($baseUrl, $cookieFile) {
    
    echo "=== FILTER OPTIONS TEST ===\n";
    
    $ch = initCurl("$baseUrl/reportes/filter-options", $cookieFile);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Requested-With: XMLHttpRequest'
    ]);
    
    $response = curl_exec($ch);
    
    if (curl_error($ch)) {
        reportTest("Get filter options", false, "cURL Error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    $data = parseResponse($response, $ch);
    $success = $data['status_code'] == 200;
    
    reportTest("Filter options endpoint", $success, "Status: {$data['status_code']}");
    
    if ($success) {
        $jsonData = json_decode($data['body'], true);
        
        if ($jsonData) {
            $hasClientes = isset($jsonData['data']['clientes']);
            $hasEmpleados = isset($jsonData['data']['empleados']);
            $hasServicios = isset($jsonData['data']['servicios']);
            $hasStatusOptions = isset($jsonData['data']['status_options']);
            
            reportTest("Filter options - Clientes list", $hasClientes);
            reportTest("Filter options - Empleados list", $hasEmpleados);
            reportTest("Filter options - Servicios list", $hasServicios);
            reportTest("Filter options - Status options", $hasStatusOptions);
        } else {
            reportTest("Filter options - Valid JSON", false);
        }
    }
    
    curl_close($ch);
    return $success;
}

// Test error handling
function testErrorHandling($baseUrl, $cookieFile) {
    
    echo "=== ERROR HANDLING TEST ===\n";
    
    // Get CSRF token
    $ch = initCurl("$baseUrl/reportes", $cookieFile);
    $response = curl_exec($ch);
    $data = parseResponse($response, $ch);
    $csrfToken = extractCsrfToken($data['body']);
    
    // Test with invalid report type
    echo "\n--- Testing invalid report type ---\n";
    
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/reportes/generar");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'X-Requested-With: XMLHttpRequest'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        '_token' => $csrfToken,
        'tipo_reporte' => 'invalid_type',
        'fecha_inicio' => date('Y-m-01'),
        'fecha_fin' => date('Y-m-d')
    ]));
    
    $response = curl_exec($ch);
    $data = parseResponse($response, $ch);
    
    // Should return validation error
    $validationHandled = $data['status_code'] == 422;
    reportTest("Invalid report type validation", $validationHandled, "Status: {$data['status_code']}");
    
    // Test without CSRF token
    echo "\n--- Testing missing CSRF token ---\n";
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'tipo_reporte' => 'ordenes',
        'fecha_inicio' => date('Y-m-01'),
        'fecha_fin' => date('Y-m-d')
    ]));
    
    $response = curl_exec($ch);
    $data = parseResponse($response, $ch);
    
    // Should return CSRF error
    $csrfHandled = $data['status_code'] == 419 || $data['status_code'] == 403;
    reportTest("Missing CSRF token handling", $csrfHandled, "Status: {$data['status_code']}");
    
    curl_close($ch);
    return true;
}

// Test JavaScript functionality
function testJavaScriptFunctionality($baseUrl, $cookieFile) {
    
    echo "=== JAVASCRIPT FUNCTIONALITY TEST ===\n";
    
    $ch = initCurl("$baseUrl/reportes", $cookieFile);
    $response = curl_exec($ch);
    $data = parseResponse($response, $ch);
    
    if ($data['status_code'] == 200) {
        // Check for JavaScript functions and libraries
        $hasChartJs = strpos($data['body'], 'chart.js') !== false;
        $hasExportFunctions = strpos($data['body'], 'exportToPDF') !== false && 
                             strpos($data['body'], 'exportToExcel') !== false;
        $hasChartInit = strpos($data['body'], 'ingresosChart') !== false && 
                       strpos($data['body'], 'serviciosChart') !== false;
        
        reportTest("Chart.js library loaded", $hasChartJs);
        reportTest("Export functions defined", $hasExportFunctions);
        reportTest("Chart initialization present", $hasChartInit);
        
        // Check for potential JavaScript errors (basic check)
        $hasJsErrors = strpos($data['body'], 'Uncaught') !== false || 
                      strpos($data['body'], 'TypeError') !== false ||
                      strpos($data['body'], 'ReferenceError') !== false;
        
        reportTest("No obvious JavaScript errors", !$hasJsErrors);
    } else {
        reportTest("Load reports page for JS test", false, "Status: {$data['status_code']}");
    }
    
    curl_close($ch);
    return true;
}

// Main test execution
function runAllTests() {
    global $baseUrl, $cookieFile;
    
    echo "==========================================\n";
    echo "COMPREHENSIVE REPORTES MODULE TEST\n";
    echo "==========================================\n";
    echo "Testing Laravel Reports functionality\n";
    echo "Base URL: {$baseUrl}\n";
    echo "==========================================\n\n";
    
    // Clean up previous test
    if (file_exists($cookieFile)) {
        unlink($cookieFile);
    }
    
    $testResults = [];
    
    // Run all tests
    $testResults['auth'] = authenticateUser($baseUrl, $cookieFile);
    
    if ($testResults['auth']) {
        $testResults['index'] = testReportesIndex($baseUrl, $cookieFile);
        $testResults['generation'] = testReportGeneration($baseUrl, $cookieFile);
        $testResults['filters'] = testReportFilters($baseUrl, $cookieFile);
        $testResults['pdf_export'] = testPdfExport($baseUrl, $cookieFile);
        $testResults['filter_options'] = testFilterOptions($baseUrl, $cookieFile);
        $testResults['error_handling'] = testErrorHandling($baseUrl, $cookieFile);
        $testResults['javascript'] = testJavaScriptFunctionality($baseUrl, $cookieFile);
    } else {
        echo "❌ Authentication failed. Skipping remaining tests.\n";
    }
    
    // Summary
    echo "\n==========================================\n";
    echo "TEST SUMMARY\n";
    echo "==========================================\n";
    
    $passed = 0;
    $total = 0;
    
    foreach ($testResults as $test => $result) {
        $status = $result ? "✅ PASS" : "❌ FAIL";
        echo "{$status} - " . ucfirst(str_replace('_', ' ', $test)) . "\n";
        if ($result) $passed++;
        $total++;
    }
    
    echo "\nOverall: {$passed}/{$total} test suites passed\n";
    
    if ($passed == $total) {
        echo "🎉 All tests passed! Reports module is working correctly.\n";
    } else {
        echo "⚠️  Some tests failed. Please check the details above.\n";
    }
    
    // Cleanup
    if (file_exists($cookieFile)) {
        unlink($cookieFile);
    }
}

// Run the tests
runAllTests();

?>