<?php

/**
 * Comprehensive Vehicle Module Testing Script
 * Tests all CRUD operations and validates functionality
 * URL: http://0.0.0.0:8001
 */

class VehicleModuleTester {
    private $baseUrl = 'http://0.0.0.0:8001';
    private $results = [];
    private $cookieFile;
    private $authCookies = '';

    public function __construct() {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'vehiculos_test_cookies');
        echo "🚗 Vehicle Module Comprehensive Testing\n";
        echo "=====================================\n\n";
        echo "Testing URL: {$this->baseUrl}\n";
        echo "Cookie file: {$this->cookieFile}\n\n";
    }

    private function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
        $ch = curl_init();
        $fullUrl = $this->baseUrl . $url;
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'VehicleModuleTester/1.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => true,
        ]);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Split headers and body
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        return [
            'url' => $fullUrl,
            'method' => $method,
            'http_code' => $httpCode,
            'headers' => $headers,
            'body' => $body,
            'error' => $error,
            'success' => !$error && $httpCode >= 200 && $httpCode < 400
        ];
    }

    private function extractCsrfToken($html) {
        if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }
        if (preg_match('/<input[^>]+name="_token"[^>]+value="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function checkForErrors($html) {
        $errors = [];
        
        // Check for Laravel error pages
        if (strpos($html, 'Whoops, looks like something went wrong') !== false) {
            $errors[] = 'Laravel error page detected';
        }
        
        // Check for validation errors
        if (preg_match_all('/<div[^>]*class="[^"]*alert[^"]*alert-danger[^"]*"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
            foreach ($matches[1] as $match) {
                $errors[] = 'Alert error: ' . strip_tags($match);
            }
        }
        
        // Check for missing elements
        if (strpos($html, 'Route [') !== false && strpos($html, '] not defined') !== false) {
            $errors[] = 'Route not defined error';
        }
        
        return $errors;
    }

    private function analyzePageContent($html, $expectedElements = []) {
        $analysis = [
            'title' => '',
            'has_nav' => false,
            'has_form' => false,
            'has_table' => false,
            'has_pagination' => false,
            'has_search' => false,
            'missing_elements' => [],
            'javascript_errors' => [],
            'validation_errors' => []
        ];

        // Extract title
        if (preg_match('/<title>(.*?)<\/title>/', $html, $matches)) {
            $analysis['title'] = trim($matches[1]);
        }

        // Check for navigation
        $analysis['has_nav'] = strpos($html, '<nav') !== false || strpos($html, 'navbar') !== false;

        // Check for forms
        $analysis['has_form'] = strpos($html, '<form') !== false;

        // Check for tables
        $analysis['has_table'] = strpos($html, '<table') !== false;

        // Check for pagination
        $analysis['has_pagination'] = strpos($html, 'pagination') !== false;

        // Check for search functionality
        $analysis['has_search'] = strpos($html, 'search') !== false || strpos($html, 'buscar') !== false;

        // Check for expected elements
        foreach ($expectedElements as $element) {
            if (strpos($html, $element) === false) {
                $analysis['missing_elements'][] = $element;
            }
        }

        // Check for validation errors in the HTML
        if (preg_match_all('/<div[^>]*class="[^"]*invalid-feedback[^"]*"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
            foreach ($matches[1] as $match) {
                $analysis['validation_errors'][] = strip_tags($match);
            }
        }

        return $analysis;
    }

    public function testAuthentication() {
        echo "🔐 Testing Authentication...\n";
        
        // Try to access vehicles without authentication
        $response = $this->makeRequest('/vehiculos');
        
        if ($response['http_code'] == 302 || strpos($response['body'], 'login') !== false) {
            echo "✅ Authentication required (as expected)\n";
            
            // Try to login (assuming default test credentials exist)
            $loginPage = $this->makeRequest('/login');
            $csrfToken = $this->extractCsrfToken($loginPage['body']);
            
            if ($csrfToken) {
                $loginData = http_build_query([
                    '_token' => $csrfToken,
                    'email' => 'admin@test.com', // Adjust as needed
                    'password' => 'password123'   // Adjust as needed
                ]);
                
                $loginResponse = $this->makeRequest('/login', 'POST', $loginData, [
                    'Content-Type: application/x-www-form-urlencoded'
                ]);
                
                if ($loginResponse['http_code'] == 302) {
                    echo "✅ Login attempt made (redirect received)\n";
                } else {
                    echo "⚠️  Login failed or no redirect\n";
                }
            } else {
                echo "❌ Could not extract CSRF token from login page\n";
            }
        } else {
            echo "⚠️  Authentication may not be required or bypassed\n";
        }
        
        echo "\n";
    }

    public function testVehicleIndex() {
        echo "📋 Testing Vehicle Index (/vehiculos)...\n";
        
        $response = $this->makeRequest('/vehiculos');
        $this->results['index'] = $response;
        
        echo "HTTP Code: {$response['http_code']}\n";
        
        if ($response['success']) {
            $analysis = $this->analyzePageContent($response['body'], [
                'Vehículos',
                'Gestión de Vehículos',
                'Nuevo Vehículo',
                'Lista de Vehículos'
            ]);
            
            echo "✅ Page loaded successfully\n";
            echo "Title: {$analysis['title']}\n";
            echo "Has Navigation: " . ($analysis['has_nav'] ? 'Yes' : 'No') . "\n";
            echo "Has Table: " . ($analysis['has_table'] ? 'Yes' : 'No') . "\n";
            echo "Has Search: " . ($analysis['has_search'] ? 'Yes' : 'No') . "\n";
            echo "Has Pagination: " . ($analysis['has_pagination'] ? 'Yes' : 'No') . "\n";
            
            if (!empty($analysis['missing_elements'])) {
                echo "⚠️  Missing elements: " . implode(', ', $analysis['missing_elements']) . "\n";
            }
            
            // Check for DataTables
            if (strpos($response['body'], 'DataTables') !== false || strpos($response['body'], 'dataTables') !== false) {
                echo "✅ DataTables integration detected\n";
            }
            
            // Check for Bootstrap
            if (strpos($response['body'], 'bootstrap') !== false) {
                echo "✅ Bootstrap styling detected\n";
            }
            
        } else {
            echo "❌ Failed to load: {$response['error']}\n";
            $errors = $this->checkForErrors($response['body']);
            foreach ($errors as $error) {
                echo "   Error: $error\n";
            }
        }
        
        echo "\n";
    }

    public function testVehicleCreate() {
        echo "➕ Testing Vehicle Create Form (/vehiculos/create)...\n";
        
        $response = $this->makeRequest('/vehiculos/create');
        $this->results['create'] = $response;
        
        echo "HTTP Code: {$response['http_code']}\n";
        
        if ($response['success']) {
            $analysis = $this->analyzePageContent($response['body'], [
                'form',
                'cliente_id',
                'brand',
                'model',
                'year',
                'license_plate',
                'vin',
                'color'
            ]);
            
            echo "✅ Create form loaded successfully\n";
            echo "Title: {$analysis['title']}\n";
            echo "Has Form: " . ($analysis['has_form'] ? 'Yes' : 'No') . "\n";
            
            // Check for required form fields
            $requiredFields = ['cliente_id', 'brand', 'model', 'year', 'license_plate', 'vin', 'color'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (strpos($response['body'], "name=\"$field\"") === false) {
                    $missingFields[] = $field;
                }
            }
            
            if (empty($missingFields)) {
                echo "✅ All required form fields present\n";
            } else {
                echo "⚠️  Missing form fields: " . implode(', ', $missingFields) . "\n";
            }
            
            // Check for client dropdown
            if (strpos($response['body'], 'select') !== false && strpos($response['body'], 'cliente_id') !== false) {
                echo "✅ Client dropdown detected\n";
            } else {
                echo "⚠️  Client dropdown not found\n";
            }
            
            // Check for CSRF token
            $csrfToken = $this->extractCsrfToken($response['body']);
            if ($csrfToken) {
                echo "✅ CSRF token present\n";
            } else {
                echo "⚠️  CSRF token not found\n";
            }
            
        } else {
            echo "❌ Failed to load: {$response['error']}\n";
            $errors = $this->checkForErrors($response['body']);
            foreach ($errors as $error) {
                echo "   Error: $error\n";
            }
        }
        
        echo "\n";
    }

    public function testVehicleShow() {
        echo "👁️  Testing Vehicle Show (/vehiculos/1)...\n";
        
        $response = $this->makeRequest('/vehiculos/1');
        $this->results['show'] = $response;
        
        echo "HTTP Code: {$response['http_code']}\n";
        
        if ($response['success']) {
            $analysis = $this->analyzePageContent($response['body'], [
                'Detalles',
                'Vehículo',
                'Cliente',
                'Marca',
                'Modelo'
            ]);
            
            echo "✅ Show page loaded successfully\n";
            echo "Title: {$analysis['title']}\n";
            
            // Check for vehicle details
            $detailElements = ['brand', 'model', 'year', 'license_plate', 'vin', 'color'];
            $foundDetails = 0;
            
            foreach ($detailElements as $element) {
                if (strpos($response['body'], $element) !== false) {
                    $foundDetails++;
                }
            }
            
            echo "Vehicle details found: $foundDetails/" . count($detailElements) . "\n";
            
            // Check for action buttons
            if (strpos($response['body'], 'Editar') !== false || strpos($response['body'], 'edit') !== false) {
                echo "✅ Edit button found\n";
            }
            
            if (strpos($response['body'], 'Eliminar') !== false || strpos($response['body'], 'delete') !== false) {
                echo "✅ Delete button found\n";
            }
            
        } else if ($response['http_code'] == 404) {
            echo "⚠️  Vehicle with ID 1 not found (may be expected if no data)\n";
        } else {
            echo "❌ Failed to load: {$response['error']}\n";
            $errors = $this->checkForErrors($response['body']);
            foreach ($errors as $error) {
                echo "   Error: $error\n";
            }
        }
        
        echo "\n";
    }

    public function testVehicleEdit() {
        echo "✏️  Testing Vehicle Edit (/vehiculos/1/edit)...\n";
        
        $response = $this->makeRequest('/vehiculos/1/edit');
        $this->results['edit'] = $response;
        
        echo "HTTP Code: {$response['http_code']}\n";
        
        if ($response['success']) {
            $analysis = $this->analyzePageContent($response['body'], [
                'form',
                'Editar',
                'Vehículo'
            ]);
            
            echo "✅ Edit form loaded successfully\n";
            echo "Title: {$analysis['title']}\n";
            echo "Has Form: " . ($analysis['has_form'] ? 'Yes' : 'No') . "\n";
            
            // Check for pre-filled form fields
            if (strpos($response['body'], 'value=') !== false) {
                echo "✅ Form appears to have pre-filled values\n";
            }
            
            // Check for method spoofing (PUT/PATCH)
            if (strpos($response['body'], '_method') !== false) {
                echo "✅ Method spoofing for PUT/PATCH detected\n";
            }
            
            // Check for CSRF token
            $csrfToken = $this->extractCsrfToken($response['body']);
            if ($csrfToken) {
                echo "✅ CSRF token present\n";
            }
            
        } else if ($response['http_code'] == 404) {
            echo "⚠️  Vehicle with ID 1 not found for editing (may be expected if no data)\n";
        } else {
            echo "❌ Failed to load: {$response['error']}\n";
            $errors = $this->checkForErrors($response['body']);
            foreach ($errors as $error) {
                echo "   Error: $error\n";
            }
        }
        
        echo "\n";
    }

    public function testVehicleStore() {
        echo "💾 Testing Vehicle Store (POST /vehiculos)...\n";
        
        // First get the create form to extract CSRF token
        $createForm = $this->makeRequest('/vehiculos/create');
        $csrfToken = $this->extractCsrfToken($createForm['body']);
        
        if (!$csrfToken) {
            echo "❌ Could not extract CSRF token for testing store\n\n";
            return;
        }
        
        // Test data
        $testData = [
            '_token' => $csrfToken,
            'cliente_id' => '1', // Assuming client with ID 1 exists
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => '2020',
            'license_plate' => 'TEST-' . rand(100, 999),
            'vin' => 'TEST-VIN-' . rand(10000, 99999),
            'color' => 'blue',
            'status' => '1'
        ];
        
        $response = $this->makeRequest('/vehiculos', 'POST', http_build_query($testData), [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $this->results['store'] = $response;
        
        echo "HTTP Code: {$response['http_code']}\n";
        
        if ($response['http_code'] == 302) {
            echo "✅ Store request redirected (likely successful)\n";
            
            // Check redirect location
            if (strpos($response['headers'], 'Location:') !== false) {
                echo "✅ Redirect location found in headers\n";
            }
            
        } else if ($response['http_code'] == 422) {
            echo "⚠️  Validation errors (422) - checking for error messages\n";
            $errors = $this->checkForErrors($response['body']);
            foreach ($errors as $error) {
                echo "   Validation error: $error\n";
            }
            
        } else {
            echo "❌ Unexpected response code\n";
            $errors = $this->checkForErrors($response['body']);
            foreach ($errors as $error) {
                echo "   Error: $error\n";
            }
        }
        
        echo "\n";
    }

    public function testSearchFunctionality() {
        echo "🔍 Testing Search Functionality...\n";
        
        // Test basic search
        $searchResponse = $this->makeRequest('/vehiculos?search=Toyota');
        echo "Search test (Toyota): HTTP {$searchResponse['http_code']}\n";
        
        if ($searchResponse['success']) {
            echo "✅ Search request successful\n";
            
            // Check if search term is preserved in form
            if (strpos($searchResponse['body'], 'value="Toyota"') !== false) {
                echo "✅ Search term preserved in form\n";
            }
        }
        
        // Test filter by brand
        $brandResponse = $this->makeRequest('/vehiculos?brand=Toyota');
        echo "Brand filter test: HTTP {$brandResponse['http_code']}\n";
        
        // Test status filter
        $statusResponse = $this->makeRequest('/vehiculos?status=1');
        echo "Status filter test: HTTP {$statusResponse['http_code']}\n";
        
        echo "\n";
    }

    public function testAjaxEndpoints() {
        echo "⚡ Testing AJAX Endpoints...\n";
        
        // Test AJAX index request
        $ajaxResponse = $this->makeRequest('/vehiculos', 'GET', null, [
            'X-Requested-With: XMLHttpRequest',
            'Accept: application/json'
        ]);
        
        echo "AJAX Index: HTTP {$ajaxResponse['http_code']}\n";
        
        if ($ajaxResponse['success']) {
            $jsonData = json_decode($ajaxResponse['body'], true);
            if ($jsonData && isset($jsonData['success'])) {
                echo "✅ AJAX response is valid JSON\n";
                echo "✅ Response has success field\n";
            } else {
                echo "⚠️  Response may not be JSON or missing success field\n";
            }
        }
        
        echo "\n";
    }

    public function testValidationErrors() {
        echo "⚠️  Testing Form Validation...\n";
        
        // Get CSRF token
        $createForm = $this->makeRequest('/vehiculos/create');
        $csrfToken = $this->extractCsrfToken($createForm['body']);
        
        if (!$csrfToken) {
            echo "❌ Could not extract CSRF token for validation testing\n\n";
            return;
        }
        
        // Test with empty data
        $emptyData = ['_token' => $csrfToken];
        
        $response = $this->makeRequest('/vehiculos', 'POST', http_build_query($emptyData), [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        echo "Empty form submission: HTTP {$response['http_code']}\n";
        
        if ($response['http_code'] == 422) {
            echo "✅ Validation working (422 status code)\n";
            
            // Check for validation error messages
            $validationFields = ['cliente_id', 'brand', 'model', 'year', 'license_plate', 'vin', 'color'];
            $foundErrors = 0;
            
            foreach ($validationFields as $field) {
                if (strpos($response['body'], $field) !== false) {
                    $foundErrors++;
                }
            }
            
            echo "Validation errors found for $foundErrors/" . count($validationFields) . " required fields\n";
            
        } else {
            echo "⚠️  Expected 422 status code for validation errors\n";
        }
        
        echo "\n";
    }

    public function testResponsiveDesign() {
        echo "📱 Testing Responsive Design Elements...\n";
        
        $response = $this->makeRequest('/vehiculos');
        
        if ($response['success']) {
            // Check for responsive classes
            $responsiveElements = [
                'col-md-', 'col-lg-', 'col-sm-',
                'table-responsive',
                'btn-group',
                'd-flex', 'd-md-', 'd-lg-',
                'responsive'
            ];
            
            $foundElements = 0;
            foreach ($responsiveElements as $element) {
                if (strpos($response['body'], $element) !== false) {
                    $foundElements++;
                }
            }
            
            echo "Responsive design elements found: $foundElements/" . count($responsiveElements) . "\n";
            
            if ($foundElements >= count($responsiveElements) * 0.6) {
                echo "✅ Good responsive design implementation\n";
            } else {
                echo "⚠️  Limited responsive design elements\n";
            }
        }
        
        echo "\n";
    }

    public function generateReport() {
        echo "📊 Test Results Summary\n";
        echo "======================\n\n";
        
        $endpoints = [
            'index' => '/vehiculos',
            'create' => '/vehiculos/create',
            'show' => '/vehiculos/1',
            'edit' => '/vehiculos/1/edit',
            'store' => 'POST /vehiculos'
        ];
        
        $successCount = 0;
        $totalCount = count($endpoints);
        
        foreach ($endpoints as $key => $endpoint) {
            if (isset($this->results[$key])) {
                $result = $this->results[$key];
                $status = $result['success'] ? '✅ PASS' : '❌ FAIL';
                $code = $result['http_code'];
                
                echo "$endpoint: $status (HTTP $code)\n";
                
                if ($result['success']) {
                    $successCount++;
                }
                
                if (!$result['success'] && !empty($result['error'])) {
                    echo "   Error: {$result['error']}\n";
                }
            } else {
                echo "$endpoint: ⚪ NOT TESTED\n";
            }
        }
        
        echo "\n";
        echo "Success Rate: $successCount/$totalCount (" . round(($successCount/$totalCount) * 100, 1) . "%)\n\n";
        
        // Recommendations
        echo "🔧 Recommendations:\n";
        echo "==================\n";
        
        if (isset($this->results['index']) && !$this->results['index']['success']) {
            echo "- Fix vehicle index page loading issues\n";
        }
        
        if (isset($this->results['create']) && !$this->results['create']['success']) {
            echo "- Fix vehicle creation form issues\n";
        }
        
        if (isset($this->results['show']) && $this->results['show']['http_code'] == 404) {
            echo "- Add sample vehicle data for testing show/edit functionality\n";
        }
        
        echo "- Ensure proper authentication is implemented\n";
        echo "- Test with actual data in the database\n";
        echo "- Verify JavaScript console for client-side errors\n";
        echo "- Test form submissions with various data combinations\n";
        echo "- Verify permission-based access control\n\n";
    }

    public function runAllTests() {
        $this->testAuthentication();
        $this->testVehicleIndex();
        $this->testVehicleCreate();
        $this->testVehicleShow();
        $this->testVehicleEdit();
        $this->testVehicleStore();
        $this->testSearchFunctionality();
        $this->testAjaxEndpoints();
        $this->testValidationErrors();
        $this->testResponsiveDesign();
        $this->generateReport();
        
        // Cleanup
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }
}

// Run the tests
$tester = new VehicleModuleTester();
$tester->runAllTests();

echo "✅ Testing completed! Check the results above for detailed findings.\n";