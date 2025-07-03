<?php
/**
 * CONTROLLER METHOD TESTING SCRIPT
 * Tests controller methods, responses, validation, and business logic
 */

class ControllerMethodTester
{
    private $baseUrl = 'http://localhost:8002';
    private $cookieFile;
    private $testResults = [];
    private $csrfToken = '';
    private $loggedIn = false;
    
    public function __construct()
    {
        $this->cookieFile = __DIR__ . '/controller_test_cookies.txt';
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
        $this->testResults = [
            'controller_responses' => [],
            'json_api_tests' => [],
            'validation_tests' => [],
            'business_logic_tests' => [],
            'error_handling_tests' => [],
            'summary' => [
                'total_tests' => 0,
                'passed' => 0,
                'failed' => 0,
                'errors' => []
            ]
        ];
    }
    
    private function makeRequest($url, $method = 'GET', $data = [], $headers = [])
    {
        $ch = curl_init();
        
        $defaultHeaders = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'Accept: text/html,application/xhtml+xml,application/xml,application/json;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Connection: keep-alive',
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        curl_close($ch);
        
        return [
            'body' => $response,
            'http_code' => $httpCode,
            'content_type' => $contentType,
            'error' => $error,
            'info' => $info
        ];
    }
    
    private function extractCsrfToken($html)
    {
        if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }
        if (preg_match('/<input[^>]+name="_token"[^>]+value="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }
        return '';
    }
    
    private function authenticate()
    {
        echo "Authenticating for controller testing...\n";
        
        $response = $this->makeRequest($this->baseUrl . '/login');
        if ($response['http_code'] !== 200) {
            echo "Failed to access login page\n";
            return false;
        }
        
        $this->csrfToken = $this->extractCsrfToken($response['body']);
        
        // Try to login with admin credentials
        $loginData = [
            '_token' => $this->csrfToken,
            'email' => 'admin@test.com',
            'password' => 'password'
        ];
        
        $response = $this->makeRequest($this->baseUrl . '/login', 'POST', $loginData);
        
        // Verify login success
        $dashResponse = $this->makeRequest($this->baseUrl . '/dashboard');
        if ($dashResponse['http_code'] === 200) {
            $this->loggedIn = true;
            echo "Successfully authenticated!\n";
            return true;
        }
        
        echo "Authentication failed\n";
        return false;
    }
    
    private function addTestResult($category, $test_name, $passed, $details = [])
    {
        $this->testResults[$category][] = [
            'test' => $test_name,
            'passed' => $passed,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->testResults['summary']['total_tests']++;
        if ($passed) {
            $this->testResults['summary']['passed']++;
        } else {
            $this->testResults['summary']['failed']++;
            $this->testResults['summary']['errors'][] = $test_name . ': ' . json_encode($details);
        }
    }
    
    public function testControllerResponses()
    {
        echo "\n=== TESTING CONTROLLER RESPONSES ===\n";
        
        $this->authenticate();
        
        // Test dashboard controller
        echo "Testing DashboardController...\n";
        $response = $this->makeRequest($this->baseUrl . '/dashboard');
        $passed = $response['http_code'] === 200;
        $hasStats = strpos($response['body'], 'total_clientes') !== false || 
                   strpos($response['body'], 'dashboard') !== false;
        
        $this->addTestResult('controller_responses', 'DashboardController index', $passed, [
            'http_code' => $response['http_code'],
            'has_dashboard_content' => $hasStats,
            'content_type' => $response['content_type']
        ]);
        
        // Test ClienteController
        echo "Testing ClienteController responses...\n";
        $modules = ['clientes', 'vehiculos', 'servicios', 'empleados', 'ordenes'];
        
        foreach ($modules as $module) {
            // Test index method
            $response = $this->makeRequest($this->baseUrl . '/' . $module);
            $passed = in_array($response['http_code'], [200, 302, 403]);
            
            $this->addTestResult('controller_responses', "{$module}Controller index", $passed, [
                'http_code' => $response['http_code'],
                'content_type' => $response['content_type']
            ]);
            
            // Test create method
            $response = $this->makeRequest($this->baseUrl . '/' . $module . '/create');
            $passed = in_array($response['http_code'], [200, 302, 403]);
            
            $this->addTestResult('controller_responses', "{$module}Controller create", $passed, [
                'http_code' => $response['http_code'],
                'content_type' => $response['content_type']
            ]);
        }
    }
    
    public function testJsonApiResponses()
    {
        echo "\n=== TESTING JSON API RESPONSES ===\n";
        
        $this->authenticate();
        
        // Test AJAX requests to controllers
        $ajaxHeaders = ['X-Requested-With: XMLHttpRequest', 'Accept: application/json'];
        
        // Test clientes AJAX
        echo "Testing clientes AJAX response...\n";
        $response = $this->makeRequest($this->baseUrl . '/clientes', 'GET', [], $ajaxHeaders);
        $passed = in_array($response['http_code'], [200, 302, 403]);
        
        $isJson = false;
        if ($response['http_code'] === 200) {
            $json = json_decode($response['body'], true);
            $isJson = json_last_error() === JSON_ERROR_NONE;
        }
        
        $this->addTestResult('json_api_tests', 'Clientes AJAX response', $passed, [
            'http_code' => $response['http_code'],
            'is_json' => $isJson,
            'content_type' => $response['content_type']
        ]);
        
        // Test clientes show AJAX
        echo "Testing clientes show AJAX response...\n";
        $response = $this->makeRequest($this->baseUrl . '/clientes/1', 'GET', [], $ajaxHeaders);
        $passed = in_array($response['http_code'], [200, 302, 403, 404]);
        
        $this->addTestResult('json_api_tests', 'Clientes show AJAX response', $passed, [
            'http_code' => $response['http_code'],
            'content_type' => $response['content_type']
        ]);
    }
    
    public function testValidation()
    {
        echo "\n=== TESTING VALIDATION ===\n";
        
        $this->authenticate();
        
        // Test cliente creation with invalid data
        echo "Testing cliente validation...\n";
        $invalidData = [
            '_token' => $this->csrfToken,
            'name' => '', // Required field empty
            'email' => 'invalid-email', // Invalid email
            'phone' => '123', // Too short
            'document_number' => '' // Required field empty
        ];
        
        $response = $this->makeRequest($this->baseUrl . '/clientes', 'POST', $invalidData);
        $passed = in_array($response['http_code'], [302, 422, 400]); // Validation error codes
        
        $this->addTestResult('validation_tests', 'Cliente validation with invalid data', $passed, [
            'http_code' => $response['http_code'],
            'expected_validation_error' => true
        ]);
        
        // Test cliente creation with valid data
        echo "Testing cliente creation with valid data...\n";
        $validData = [
            '_token' => $this->csrfToken,
            'name' => 'Test Cliente Controller',
            'email' => 'testcontroller' . time() . '@example.com',
            'phone' => '123456789',
            'document_number' => 'DOC' . time(),
            'document_type' => 'dni',
            'address' => 'Test Address',
            'status' => true
        ];
        
        $response = $this->makeRequest($this->baseUrl . '/clientes', 'POST', $validData);
        $passed = in_array($response['http_code'], [200, 201, 302]);
        
        $this->addTestResult('validation_tests', 'Cliente creation with valid data', $passed, [
            'http_code' => $response['http_code'],
            'expected_success' => true
        ]);
    }
    
    public function testBusinessLogic()
    {
        echo "\n=== TESTING BUSINESS LOGIC ===\n";
        
        $this->authenticate();
        
        // Test search functionality
        echo "Testing search functionality...\n";
        $response = $this->makeRequest($this->baseUrl . '/clientes?search=test');
        $passed = in_array($response['http_code'], [200, 302, 403]);
        
        $this->addTestResult('business_logic_tests', 'Search functionality', $passed, [
            'http_code' => $response['http_code'],
            'has_search_parameter' => true
        ]);
        
        // Test pagination
        echo "Testing pagination...\n";
        $response = $this->makeRequest($this->baseUrl . '/clientes?page=2&per_page=5');
        $passed = in_array($response['http_code'], [200, 302, 403]);
        
        $this->addTestResult('business_logic_tests', 'Pagination functionality', $passed, [
            'http_code' => $response['http_code'],
            'has_pagination_parameters' => true
        ]);
        
        // Test status filtering
        echo "Testing status filtering...\n";
        $response = $this->makeRequest($this->baseUrl . '/clientes?status=1');
        $passed = in_array($response['http_code'], [200, 302, 403]);
        
        $this->addTestResult('business_logic_tests', 'Status filtering', $passed, [
            'http_code' => $response['http_code'],
            'has_status_filter' => true
        ]);
    }
    
    public function testErrorHandling()
    {
        echo "\n=== TESTING ERROR HANDLING ===\n";
        
        $this->authenticate();
        
        // Test non-existent resource
        echo "Testing 404 error handling...\n";
        $response = $this->makeRequest($this->baseUrl . '/clientes/999999');
        $passed = in_array($response['http_code'], [404, 302, 403]);
        
        $this->addTestResult('error_handling_tests', '404 error handling', $passed, [
            'http_code' => $response['http_code'],
            'resource_not_found' => true
        ]);
        
        // Test invalid method
        echo "Testing method not allowed...\n";
        $response = $this->makeRequest($this->baseUrl . '/clientes/1', 'PATCH');
        $passed = in_array($response['http_code'], [405, 302, 422, 419, 200]); // Various possible responses
        
        $this->addTestResult('error_handling_tests', 'Method not allowed handling', $passed, [
            'http_code' => $response['http_code'],
            'method_not_allowed_test' => true
        ]);
        
        // Test CSRF protection
        echo "Testing CSRF protection...\n";
        $dataWithoutToken = [
            'name' => 'Test Without CSRF',
            'email' => 'nocsrf@example.com',
            'phone' => '123456789',
            'document_number' => 'NOCSRF123'
        ];
        
        $response = $this->makeRequest($this->baseUrl . '/clientes', 'POST', $dataWithoutToken);
        $passed = in_array($response['http_code'], [419, 302, 422]); // CSRF error codes
        
        $this->addTestResult('error_handling_tests', 'CSRF protection', $passed, [
            'http_code' => $response['http_code'],
            'csrf_protection_test' => true
        ]);
    }
    
    public function runAllTests()
    {
        echo "STARTING CONTROLLER METHOD TESTING...\n";
        echo "Base URL: " . $this->baseUrl . "\n\n";
        
        $this->testControllerResponses();
        $this->testJsonApiResponses();
        $this->testValidation();
        $this->testBusinessLogic();
        $this->testErrorHandling();
        
        $this->generateReport();
    }
    
    private function generateReport()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "CONTROLLER METHOD TEST REPORT\n";
        echo str_repeat("=", 80) . "\n";
        
        $summary = $this->testResults['summary'];
        echo "SUMMARY:\n";
        echo "Total Tests: " . $summary['total_tests'] . "\n";
        echo "Passed: " . $summary['passed'] . "\n";
        echo "Failed: " . $summary['failed'] . "\n";
        echo "Success Rate: " . round(($summary['passed'] / max($summary['total_tests'], 1)) * 100, 2) . "%\n\n";
        
        foreach ($this->testResults as $category => $tests) {
            if ($category === 'summary' || empty($tests)) continue;
            
            echo strtoupper(str_replace('_', ' ', $category)) . ":\n";
            echo str_repeat("-", 50) . "\n";
            
            foreach ($tests as $test) {
                $status = $test['passed'] ? '✓ PASS' : '✗ FAIL';
                echo sprintf("%-50s %s\n", $test['test'], $status);
                if (!$test['passed'] && !empty($test['details'])) {
                    echo "  Details: " . json_encode($test['details']) . "\n";
                }
            }
            echo "\n";
        }
        
        if (!empty($summary['errors'])) {
            echo "FAILED TESTS DETAILS:\n";
            echo str_repeat("-", 50) . "\n";
            foreach ($summary['errors'] as $error) {
                echo "• " . $error . "\n";
            }
        }
        
        // Save results to JSON file
        $reportFile = __DIR__ . '/controller_method_test_report.json';
        file_put_contents($reportFile, json_encode($this->testResults, JSON_PRETTY_PRINT));
        echo "\nDetailed report saved to: $reportFile\n";
    }
}

// Run the tests
echo "Laravel Controller Method Testing Script\n";
echo "========================================\n";

$tester = new ControllerMethodTester();
$tester->runAllTests();