<?php

/**
 * Authenticated Vehicle Module Testing
 * Tests with proper authentication using admin credentials
 */

class AuthenticatedVehicleTest {
    private $baseUrl = 'http://0.0.0.0:8001';
    private $cookieFile;
    private $isAuthenticated = false;
    private $csrfToken = null;

    public function __construct() {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'auth_test_cookies');
        echo "🔐 Authenticated Vehicle Module Testing\n";
        echo "======================================\n\n";
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
            CURLOPT_USERAGENT => 'AuthenticatedVehicleTest/1.0',
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
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

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

    public function authenticate() {
        echo "🔑 Attempting authentication...\n";
        
        // First, get the login page to extract CSRF token
        $loginPage = $this->makeRequest('/login');
        
        if (!$loginPage['success']) {
            echo "❌ Failed to load login page: {$loginPage['error']}\n";
            return false;
        }

        $this->csrfToken = $this->extractCsrfToken($loginPage['body']);
        
        if (!$this->csrfToken) {
            echo "❌ Could not extract CSRF token from login page\n";
            return false;
        }

        echo "✅ CSRF Token extracted: " . substr($this->csrfToken, 0, 10) . "...\n";

        // Attempt login with admin credentials
        $loginData = http_build_query([
            '_token' => $this->csrfToken,
            'email' => 'admin@taller.com',
            'password' => 'admin123'
        ]);

        $loginResponse = $this->makeRequest('/login', 'POST', $loginData, [
            'Content-Type: application/x-www-form-urlencoded',
            'Referer: ' . $this->baseUrl . '/login'
        ]);

        echo "Login attempt: HTTP {$loginResponse['http_code']}\n";

        // Check if login was successful
        if ($loginResponse['http_code'] == 302) {
            // Check if redirected to dashboard or intended page
            if (strpos($loginResponse['headers'], 'Location: ') !== false) {
                if (preg_match('/Location: ([^\r\n]+)/', $loginResponse['headers'], $matches)) {
                    $redirectUrl = trim($matches[1]);
                    echo "✅ Redirected to: $redirectUrl\n";
                    
                    if (strpos($redirectUrl, '/login') === false) {
                        $this->isAuthenticated = true;
                        echo "✅ Authentication successful!\n";
                        return true;
                    }
                }
            }
        }

        // Check if we got an error page
        if (strpos($loginResponse['body'], 'These credentials do not match') !== false) {
            echo "❌ Invalid credentials\n";
        } else if (strpos($loginResponse['body'], 'login') !== false) {
            echo "⚠️  Still on login page - authentication may have failed\n";
        }

        return false;
    }

    public function testVehicleIndex() {
        echo "\n📋 Testing Vehicle Index Page...\n";
        
        $response = $this->makeRequest('/vehiculos');
        
        echo "HTTP Code: {$response['http_code']}\n";
        
        if ($response['success']) {
            echo "✅ Page loaded successfully\n";
            
            // Check for vehicle-specific content
            $vehicleContent = [
                'Vehículos' => strpos($response['body'], 'Vehículos') !== false,
                'Gestión de Vehículos' => strpos($response['body'], 'Gestión de Vehículos') !== false,
                'Nuevo Vehículo' => strpos($response['body'], 'Nuevo Vehículo') !== false,
                'Lista de Vehículos' => strpos($response['body'], 'Lista de Vehículos') !== false,
                'Toyota' => strpos($response['body'], 'Toyota') !== false,
                'Corolla' => strpos($response['body'], 'Corolla') !== false,
                'ABC-123' => strpos($response['body'], 'ABC-123') !== false,
            ];
            
            foreach ($vehicleContent as $content => $found) {
                if ($found) {
                    echo "✅ Found: $content\n";
                } else {
                    echo "⚠️  Missing: $content\n";
                }
            }
            
            // Check for table structure
            if (strpos($response['body'], '<table') !== false) {
                echo "✅ Table structure found\n";
                
                // Count table rows (excluding header)
                $rowCount = substr_count($response['body'], '<tr>') - 1;
                echo "📊 Table rows: $rowCount\n";
            }
            
            // Check for search functionality
            if (strpos($response['body'], 'search') !== false || strpos($response['body'], 'buscar') !== false) {
                echo "✅ Search functionality detected\n";
            }
            
            // Check for pagination
            if (strpos($response['body'], 'pagination') !== false) {
                echo "✅ Pagination detected\n";
            }
            
            // Check for action buttons
            if (strpos($response['body'], 'btn-outline-info') !== false) {
                echo "✅ View buttons found\n";
            }
            if (strpos($response['body'], 'btn-outline-warning') !== false) {
                echo "✅ Edit buttons found\n";
            }
            if (strpos($response['body'], 'btn-outline-danger') !== false) {
                echo "✅ Delete buttons found\n";
            }
            
        } else {
            echo "❌ Failed to load page\n";
            if ($response['http_code'] == 302) {
                echo "⚠️  Redirected - authentication may have expired\n";
            }
        }
    }

    public function testVehicleCreate() {
        echo "\n➕ Testing Vehicle Create Form...\n";
        
        $response = $this->makeRequest('/vehiculos/create');
        
        echo "HTTP Code: {$response['http_code']}\n";
        
        if ($response['success']) {
            echo "✅ Create form loaded successfully\n";
            
            // Check for form elements
            $formElements = [
                'cliente_id' => preg_match('/name=["\']cliente_id["\']/', $response['body']),
                'brand' => preg_match('/name=["\']brand["\']/', $response['body']),
                'model' => preg_match('/name=["\']model["\']/', $response['body']),
                'year' => preg_match('/name=["\']year["\']/', $response['body']),
                'license_plate' => preg_match('/name=["\']license_plate["\']/', $response['body']),
                'vin' => preg_match('/name=["\']vin["\']/', $response['body']),
                'color' => preg_match('/name=["\']color["\']/', $response['body']),
            ];
            
            foreach ($formElements as $element => $found) {
                if ($found) {
                    echo "✅ Form field found: $element\n";
                } else {
                    echo "❌ Missing form field: $element\n";
                }
            }
            
            // Check for client dropdown options
            if (strpos($response['body'], 'Carlos Rodríguez') !== false) {
                echo "✅ Client dropdown populated with data\n";
            }
            
            // Check for CSRF token
            $csrfToken = $this->extractCsrfToken($response['body']);
            if ($csrfToken) {
                echo "✅ CSRF token present\n";
            }
            
        } else {
            echo "❌ Failed to load create form\n";
        }
    }

    public function testVehicleShow() {
        echo "\n👁️  Testing Vehicle Show Page (ID: 1)...\n";
        
        $response = $this->makeRequest('/vehiculos/1');
        
        echo "HTTP Code: {$response['http_code']}\n";
        
        if ($response['success']) {
            echo "✅ Show page loaded successfully\n";
            
            // Check for vehicle details
            $vehicleDetails = [
                'Toyota' => strpos($response['body'], 'Toyota') !== false,
                'Corolla' => strpos($response['body'], 'Corolla') !== false,
                'ABC-123' => strpos($response['body'], 'ABC-123') !== false,
                'Carlos Rodríguez' => strpos($response['body'], 'Carlos Rodríguez') !== false,
            ];
            
            foreach ($vehicleDetails as $detail => $found) {
                if ($found) {
                    echo "✅ Vehicle detail found: $detail\n";
                } else {
                    echo "⚠️  Missing detail: $detail\n";
                }
            }
            
            // Check for action buttons
            if (strpos($response['body'], 'Editar') !== false) {
                echo "✅ Edit button found\n";
            }
            if (strpos($response['body'], 'Eliminar') !== false) {
                echo "✅ Delete button found\n";
            }
            
        } else {
            echo "❌ Failed to load show page\n";
        }
    }

    public function testVehicleEdit() {
        echo "\n✏️  Testing Vehicle Edit Form (ID: 1)...\n";
        
        $response = $this->makeRequest('/vehiculos/1/edit');
        
        echo "HTTP Code: {$response['http_code']}\n";
        
        if ($response['success']) {
            echo "✅ Edit form loaded successfully\n";
            
            // Check for pre-filled values
            $prefilledValues = [
                'Toyota' => strpos($response['body'], 'value="Toyota"') !== false || strpos($response['body'], '>Toyota<') !== false,
                'Corolla' => strpos($response['body'], 'value="Corolla"') !== false || strpos($response['body'], '>Corolla<') !== false,
                'ABC-123' => strpos($response['body'], 'value="ABC-123"') !== false,
            ];
            
            foreach ($prefilledValues as $value => $found) {
                if ($found) {
                    echo "✅ Pre-filled value found: $value\n";
                } else {
                    echo "⚠️  Missing pre-filled value: $value\n";
                }
            }
            
            // Check for method spoofing
            if (strpos($response['body'], '_method') !== false && strpos($response['body'], 'PUT') !== false) {
                echo "✅ Method spoofing for PUT detected\n";
            }
            
        } else {
            echo "❌ Failed to load edit form\n";
        }
    }

    public function testSearchFunctionality() {
        echo "\n🔍 Testing Search and Filter Functionality...\n";
        
        // Test search for Toyota
        $searchResponse = $this->makeRequest('/vehiculos?search=Toyota');
        echo "Search 'Toyota': HTTP {$searchResponse['http_code']}\n";
        
        if ($searchResponse['success']) {
            if (strpos($searchResponse['body'], 'Toyota') !== false) {
                echo "✅ Search results contain 'Toyota'\n";
            }
            
            // Check if search term is preserved
            if (strpos($searchResponse['body'], 'value="Toyota"') !== false) {
                echo "✅ Search term preserved in form\n";
            }
        }
        
        // Test brand filter
        $brandResponse = $this->makeRequest('/vehiculos?brand=Toyota');
        echo "Brand filter 'Toyota': HTTP {$brandResponse['http_code']}\n";
        
        if ($brandResponse['success'] && strpos($brandResponse['body'], 'Toyota') !== false) {
            echo "✅ Brand filter working\n";
        }
        
        // Test status filter
        $statusResponse = $this->makeRequest('/vehiculos?status=1');
        echo "Status filter 'Active': HTTP {$statusResponse['http_code']}\n";
        
        if ($statusResponse['success']) {
            echo "✅ Status filter request successful\n";
        }
    }

    public function testFormSubmission() {
        echo "\n💾 Testing Form Submission...\n";
        
        // Get create form first
        $createForm = $this->makeRequest('/vehiculos/create');
        
        if (!$createForm['success']) {
            echo "❌ Could not load create form for testing\n";
            return;
        }

        $csrfToken = $this->extractCsrfToken($createForm['body']);
        
        if (!$csrfToken) {
            echo "❌ Could not extract CSRF token\n";
            return;
        }

        // Test with valid data
        $testData = [
            '_token' => $csrfToken,
            'cliente_id' => '1',
            'brand' => 'Test Brand',
            'model' => 'Test Model',
            'year' => '2023',
            'license_plate' => 'TEST-' . rand(100, 999),
            'vin' => 'TEST-VIN-' . rand(10000, 99999),
            'color' => 'red',
            'status' => '1'
        ];

        $response = $this->makeRequest('/vehiculos', 'POST', http_build_query($testData), [
            'Content-Type: application/x-www-form-urlencoded',
            'Referer: ' . $this->baseUrl . '/vehiculos/create'
        ]);

        echo "Form submission: HTTP {$response['http_code']}\n";

        if ($response['http_code'] == 302) {
            echo "✅ Form submission redirected (likely successful)\n";
            
            // Check for success message in redirect location
            if (strpos($response['headers'], '/vehiculos') !== false) {
                echo "✅ Redirected to vehicles index\n";
            }
        } else if ($response['http_code'] == 422) {
            echo "⚠️  Validation errors (422)\n";
            
            // Look for validation error messages
            if (strpos($response['body'], 'error') !== false) {
                echo "⚠️  Validation error messages present\n";
            }
        } else {
            echo "⚠️  Unexpected response code\n";
        }

        // Test with empty data for validation
        echo "\n🔍 Testing Form Validation...\n";
        
        $emptyData = ['_token' => $csrfToken];
        
        $validationResponse = $this->makeRequest('/vehiculos', 'POST', http_build_query($emptyData), [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        echo "Empty form submission: HTTP {$validationResponse['http_code']}\n";

        if ($validationResponse['http_code'] == 422) {
            echo "✅ Validation working properly (422 response)\n";
        } else {
            echo "⚠️  Validation may not be working as expected\n";
        }
    }

    public function testResponsiveElements() {
        echo "\n📱 Testing Responsive Design Elements...\n";
        
        $response = $this->makeRequest('/vehiculos');
        
        if ($response['success']) {
            $responsiveElements = [
                'Bootstrap Grid' => strpos($response['body'], 'col-md-') !== false,
                'Table Responsive' => strpos($response['body'], 'table-responsive') !== false,
                'Button Groups' => strpos($response['body'], 'btn-group') !== false,
                'Card Components' => strpos($response['body'], 'card') !== false,
                'Flex Utilities' => strpos($response['body'], 'd-flex') !== false,
            ];
            
            foreach ($responsiveElements as $element => $found) {
                if ($found) {
                    echo "✅ $element detected\n";
                } else {
                    echo "⚠️  $element not found\n";
                }
            }
        }
    }

    public function generateReport() {
        echo "\n📊 Final Test Report\n";
        echo "===================\n";
        
        if ($this->isAuthenticated) {
            echo "✅ Authentication: SUCCESS\n";
        } else {
            echo "❌ Authentication: FAILED\n";
        }
        
        echo "\n🔧 Recommendations:\n";
        echo "- Verify database has sample data for comprehensive testing\n";
        echo "- Test JavaScript functionality in browser console\n";
        echo "- Verify permissions are working correctly\n";
        echo "- Test file uploads if applicable\n";
        echo "- Test with different user roles\n";
        echo "- Verify AJAX endpoints are working\n";
        echo "- Test responsive design on different screen sizes\n";
    }

    public function runAllTests() {
        if ($this->authenticate()) {
            $this->testVehicleIndex();
            $this->testVehicleCreate();
            $this->testVehicleShow();
            $this->testVehicleEdit();
            $this->testSearchFunctionality();
            $this->testFormSubmission();
            $this->testResponsiveElements();
        } else {
            echo "\n❌ Authentication failed - cannot proceed with protected route testing\n";
            echo "Please verify:\n";
            echo "- Server is running on http://0.0.0.0:8001\n";
            echo "- Database is seeded with user: admin@taller.com / password123\n";
            echo "- Laravel session/auth configuration is correct\n";
        }
        
        $this->generateReport();
        
        // Cleanup
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }
}

// Run the authenticated tests
$tester = new AuthenticatedVehicleTest();
$tester->runAllTests();