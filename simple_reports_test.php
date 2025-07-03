<?php

/**
 * Simple Reports Module Test
 * Direct testing of reports functionality
 */

$baseUrl = 'http://0.0.0.0:8001';
$cookieFile = 'simple_test_cookies.txt';

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

function reportTest($testName, $success, $details = '') {
    $status = $success ? "✅ PASS" : "❌ FAIL";
    echo "[{$status}] {$testName}\n";
    if ($details) {
        echo "    Details: {$details}\n";
    }
    echo "\n";
    return $success;
}

// Clean up
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "==========================================\n";
echo "SIMPLE REPORTS MODULE TEST\n";
echo "==========================================\n\n";

// Step 1: Authenticate
echo "=== AUTHENTICATION ===\n";

$ch = initCurl("$baseUrl/login", $cookieFile);
$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($response, $headerSize);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

reportTest("Get login page", $status == 200, "Status: $status");

// Extract CSRF token
if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $body, $matches)) {
    $csrfToken = $matches[1];
    reportTest("Extract CSRF token", true);
} else {
    reportTest("Extract CSRF token", false);
    exit;
}

curl_close($ch);

// Login
$ch = initCurl("$baseUrl/login", $cookieFile);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'email' => 'admin@admin.com',
    'password' => 'password'
]));

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

reportTest("User login", $status == 302, "Status: $status");

// Step 2: Test Reports Index
echo "=== REPORTS INDEX ===\n";

$ch = initCurl("$baseUrl/reportes", $cookieFile);
$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($response, $headerSize);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

reportTest("Access /reportes", $status == 200, "Status: $status");

if ($status == 200) {
    $hasTitle = strpos($body, 'Centro de Reportes') !== false;
    $hasFilters = strpos($body, 'Filtros de Reporte') !== false;
    $hasDateInputs = strpos($body, 'fecha_inicio') !== false && strpos($body, 'fecha_fin') !== false;
    $hasReportTypes = strpos($body, 'tipo_reporte') !== false;
    $hasExportButtons = strpos($body, 'Exportar PDF') !== false;
    $hasCharts = strpos($body, 'Chart.js') !== false;
    
    reportTest("Page title present", $hasTitle);
    reportTest("Filters section present", $hasFilters);
    reportTest("Date inputs present", $hasDateInputs);
    reportTest("Report type selector present", $hasReportTypes);
    reportTest("Export buttons present", $hasExportButtons);
    reportTest("Charts library loaded", $hasCharts);
    
    // Check for form submission functionality
    $hasFormAction = strpos($body, 'reportes.index') !== false;
    $hasGenerateButton = strpos($body, 'Generar Reporte') !== false;
    
    reportTest("Form action present", $hasFormAction);
    reportTest("Generate button present", $hasGenerateButton);
}

curl_close($ch);

// Step 3: Test Report Generation API
echo "=== REPORT GENERATION API ===\n";

// Get CSRF token from reports page
$ch = initCurl("$baseUrl/reportes", $cookieFile);
$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($response, $headerSize);

if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $body, $matches)) {
    $csrfToken = $matches[1];
    curl_close($ch);
    
    // Test report generation
    $reportTypes = ['ordenes', 'clientes', 'empleados', 'servicios', 'vehiculos'];
    
    foreach ($reportTypes as $tipo) {
        $ch = initCurl("$baseUrl/reportes/generar", $cookieFile);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'X-Requested-With: XMLHttpRequest'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            '_token' => $csrfToken,
            'tipo_reporte' => $tipo,
            'fecha_inicio' => date('Y-m-01'),
            'fecha_fin' => date('Y-m-d')
        ]));
        
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $success = $status == 200;
        reportTest("Generate {$tipo} report", $success, "Status: $status");
        
        if ($success) {
            $jsonData = json_decode($body, true);
            if ($jsonData) {
                $hasSuccess = isset($jsonData['success']) && $jsonData['success'] === true;
                $hasData = isset($jsonData['data']);
                reportTest("{$tipo} - JSON response valid", $hasSuccess && $hasData);
            } else {
                reportTest("{$tipo} - JSON response valid", false, "Invalid JSON");
            }
        }
        
        curl_close($ch);
    }
} else {
    reportTest("Get CSRF for API test", false);
}

// Step 4: Test PDF Export
echo "=== PDF EXPORT ===\n";

$testTypes = ['ordenes', 'clientes'];

foreach ($testTypes as $tipo) {
    $exportUrl = "$baseUrl/reportes/exportar/1?" . http_build_query([
        'tipo_reporte' => $tipo,
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-12-31'
    ]);
    
    $ch = initCurl($exportUrl, $cookieFile);
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    $success = $status == 200;
    reportTest("Export {$tipo} PDF", $success, "Status: $status");
    
    if ($success) {
        $isPdf = strpos($headers, 'Content-Type: application/pdf') !== false;
        $hasFilename = strpos($headers, 'Content-Disposition: attachment') !== false;
        $hasPdfContent = strpos($body, '%PDF') === 0;
        
        reportTest("{$tipo} - PDF content type", $isPdf);
        reportTest("{$tipo} - Download headers", $hasFilename);
        reportTest("{$tipo} - Valid PDF content", $hasPdfContent);
    }
    
    curl_close($ch);
}

// Step 5: Test Form Elements
echo "=== FORM FUNCTIONALITY ===\n";

$ch = initCurl("$baseUrl/reportes", $cookieFile);
$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($response, $headerSize);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($status == 200) {
    // Check form elements
    $hasDateStart = strpos($body, 'id="fecha_inicio"') !== false;
    $hasDateEnd = strpos($body, 'id="fecha_fin"') !== false;
    $hasReportType = strpos($body, 'id="tipo_reporte"') !== false;
    $hasSubmitButton = strpos($body, 'type="submit"') !== false;
    
    reportTest("Date start input", $hasDateStart);
    reportTest("Date end input", $hasDateEnd);
    reportTest("Report type selector", $hasReportType);
    reportTest("Submit button", $hasSubmitButton);
    
    // Check for report type options
    $hasOrdersOption = strpos($body, 'value="general"') !== false;
    $hasServicesOption = strpos($body, 'value="servicios"') !== false;
    $hasEmployeesOption = strpos($body, 'value="empleados"') !== false;
    $hasClientsOption = strpos($body, 'value="clientes"') !== false;
    $hasVehiclesOption = strpos($body, 'value="vehiculos"') !== false;
    
    reportTest("General report option", $hasOrdersOption);
    reportTest("Services report option", $hasServicesOption);
    reportTest("Employees report option", $hasEmployeesOption);
    reportTest("Clients report option", $hasClientsOption);
    reportTest("Vehicles report option", $hasVehiclesOption);
    
    // Check JavaScript functions
    $hasExportToPDF = strpos($body, 'function exportToPDF()') !== false;
    $hasExportToExcel = strpos($body, 'function exportToExcel()') !== false;
    
    reportTest("Export to PDF function", $hasExportToPDF);
    reportTest("Export to Excel function", $hasExportToExcel);
}

curl_close($ch);

echo "==========================================\n";
echo "TEST COMPLETED\n";
echo "==========================================\n";

// Cleanup
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

?>