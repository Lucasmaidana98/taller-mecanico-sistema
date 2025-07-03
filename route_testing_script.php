<?php
/**
 * ROUTE TESTING SCRIPT FOR LARAVEL APPLICATION
 * Tests all routes, controllers, authentication, and error handling
 * Base URL: http://localhost:8002
 */

class RouteBackendTester
{
    private $baseUrl = 'http://localhost:8002';
    private $cookieFile;
    private $testResults = [];
    private $csrfToken = '';
    private $loggedIn = false;
    
    public function __construct()
    {
        $this->cookieFile = __DIR__ . '/route_test_cookies.txt';
        // Clear previous cookies
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
        $this->testResults = [
            'authentication' => [],
            'main_modules' => [],
            'dashboard_profile' => [],
            'reportes' => [],
            'error_handling' => [],
            'route_parameters' => [],
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
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1'
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
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        curl_close($ch);
        
        return [
            'body' => $response,
            'http_code' => $httpCode,
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
    
    public function testAuthenticationRoutes()
    {
        echo "\n=== TESTING AUTHENTICATION ROUTES ===\n";
        
        // Test login GET
        echo "Testing login GET route...\n";
        $response = $this->makeRequest($this->baseUrl . '/login');
        $passed = $response['http_code'] === 200 && strpos($response['body'], 'login') !== false;
        $this->addTestResult('authentication', 'GET /login', $passed, [
            'http_code' => $response['http_code'],
            'has_login_form' => strpos($response['body'], 'login') !== false
        ]);
        
        if ($passed) {
            $this->csrfToken = $this->extractCsrfToken($response['body']);
            echo "CSRF token extracted: " . substr($this->csrfToken, 0, 10) . "...\n";
        }
        
        // Test register GET
        echo "Testing register GET route...\n";
        $response = $this->makeRequest($this->baseUrl . '/register');
        $passed = $response['http_code'] === 200 && strpos($response['body'], 'register') !== false;
        $this->addTestResult('authentication', 'GET /register', $passed, [
            'http_code' => $response['http_code'],
            'has_register_form' => strpos($response['body'], 'register') !== false
        ]);
        
        // Test forgot password GET
        echo "Testing forgot-password GET route...\n";
        $response = $this->makeRequest($this->baseUrl . '/forgot-password');
        $passed = $response['http_code'] === 200;
        $this->addTestResult('authentication', 'GET /forgot-password', $passed, [
            'http_code' => $response['http_code']
        ]);
        
        // Test login POST with invalid credentials
        echo "Testing login POST with invalid credentials...\n";
        $loginData = [
            '_token' => $this->csrfToken,
            'email' => 'invalid@test.com',
            'password' => 'wrongpassword'
        ];
        $response = $this->makeRequest($this->baseUrl . '/login', 'POST', $loginData);
        $passed = in_array($response['http_code'], [200, 302, 422]);
        $this->addTestResult('authentication', 'POST /login (invalid credentials)', $passed, [
            'http_code' => $response['http_code'],
            'expected_codes' => [200, 302, 422]
        ]);
        
        // Test register POST
        echo "Testing register POST...\n";
        $registerData = [
            '_token' => $this->csrfToken,
            'name' => 'Test User Route',
            'email' => 'testroute' . time() . '@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];
        $response = $this->makeRequest($this->baseUrl . '/register', 'POST', $registerData);
        $passed = in_array($response['http_code'], [200, 201, 302]);
        $this->addTestResult('authentication', 'POST /register', $passed, [
            'http_code' => $response['http_code'],
            'expected_codes' => [200, 201, 302]
        ]);
        
        if ($passed) {
            $this->loggedIn = true;
            echo "Successfully registered and logged in!\n";
        }
        
        // Test logout POST
        if ($this->loggedIn) {
            echo "Testing logout POST...\n";
            $logoutData = ['_token' => $this->csrfToken];
            $response = $this->makeRequest($this->baseUrl . '/logout', 'POST', $logoutData);
            $passed = in_array($response['http_code'], [200, 302]);
            $this->addTestResult('authentication', 'POST /logout', $passed, [
                'http_code' => $response['http_code']
            ]);
            $this->loggedIn = false;
        }
    }
    
    private function ensureAuthenticated()
    {
        if ($this->loggedIn) {
            return true;
        }
        
        echo "Attempting to authenticate for protected routes...\n";
        
        // Get login form
        $response = $this->makeRequest($this->baseUrl . '/login');
        if ($response['http_code'] !== 200) {
            return false;
        }
        
        $this->csrfToken = $this->extractCsrfToken($response['body']);
        
        // Try to login with test credentials
        $loginData = [
            '_token' => $this->csrfToken,
            'email' => 'admin@test.com',
            'password' => 'password'
        ];
        
        $response = $this->makeRequest($this->baseUrl . '/login', 'POST', $loginData);
        
        // Check if login was successful by trying to access dashboard
        $dashResponse = $this->makeRequest($this->baseUrl . '/dashboard');
        if ($dashResponse['http_code'] === 200 && strpos($dashResponse['body'], 'dashboard') !== false) {
            $this->loggedIn = true;
            echo "Successfully authenticated!\n";
            return true;
        }
        
        echo "Failed to authenticate. Some tests may fail.\n";
        return false;
    }
    
    public function testMainModuleRoutes()
    {
        echo "\n=== TESTING MAIN MODULE ROUTES ===\n";
        
        $this->ensureAuthenticated();
        
        $modules = [
            'clientes' => 'ClienteController',
            'vehiculos' => 'VehiculoController', 
            'servicios' => 'ServicioController',
            'empleados' => 'EmpleadoController',
            'ordenes' => 'OrdenTrabajoController'
        ];
        
        foreach ($modules as $module => $controller) {
            echo "Testing $module module routes...\n";
            
            // Test index route
            $response = $this->makeRequest($this->baseUrl . '/' . $module);
            $passed = in_array($response['http_code'], [200, 302]);
            $this->addTestResult('main_modules', "$module index", $passed, [
                'http_code' => $response['http_code'],
                'url' => $this->baseUrl . '/' . $module
            ]);
            
            // Test create route
            $response = $this->makeRequest($this->baseUrl . '/' . $module . '/create');
            $passed = in_array($response['http_code'], [200, 302, 403]);
            $this->addTestResult('main_modules', "$module create", $passed, [
                'http_code' => $response['http_code'],
                'url' => $this->baseUrl . '/' . $module . '/create'
            ]);
            
            // Test show route with ID 1
            $response = $this->makeRequest($this->baseUrl . '/' . $module . '/1');
            $passed = in_array($response['http_code'], [200, 302, 403, 404]);
            $this->addTestResult('main_modules', "$module show", $passed, [
                'http_code' => $response['http_code'],
                'url' => $this->baseUrl . '/' . $module . '/1'
            ]);
            
            // Test edit route with ID 1
            $response = $this->makeRequest($this->baseUrl . '/' . $module . '/1/edit');
            $passed = in_array($response['http_code'], [200, 302, 403, 404]);
            $this->addTestResult('main_modules', "$module edit", $passed, [
                'http_code' => $response['http_code'],
                'url' => $this->baseUrl . '/' . $module . '/1/edit'
            ]);
        }
    }
    
    public function testDashboardAndProfile()
    {
        echo "\n=== TESTING DASHBOARD AND PROFILE ROUTES ===\n";
        
        $this->ensureAuthenticated();
        
        // Test dashboard
        echo "Testing dashboard route...\n";
        $response = $this->makeRequest($this->baseUrl . '/dashboard');
        $passed = in_array($response['http_code'], [200, 302, 403]);
        $this->addTestResult('dashboard_profile', 'dashboard', $passed, [
            'http_code' => $response['http_code']
        ]);
        
        // Test profile edit
        echo "Testing profile edit route...\n";
        $response = $this->makeRequest($this->baseUrl . '/profile');
        $passed = in_array($response['http_code'], [200, 302]);
        $this->addTestResult('dashboard_profile', 'profile edit', $passed, [
            'http_code' => $response['http_code']
        ]);
    }
    
    public function testReportesRoutes()
    {
        echo "\n=== TESTING REPORTES ROUTES ===\n";
        
        $this->ensureAuthenticated();
        
        // Test reportes index
        $response = $this->makeRequest($this->baseUrl . '/reportes');
        $passed = in_array($response['http_code'], [200, 302, 403]);
        $this->addTestResult('reportes', 'reportes index', $passed, [
            'http_code' => $response['http_code']
        ]);
        
        // Test reportes generar POST
        if ($this->csrfToken) {
            $reportData = [
                '_token' => $this->csrfToken,
                'tipo_reporte' => 'clientes',
                'fecha_inicio' => '2025-01-01',
                'fecha_fin' => '2025-12-31'
            ];
            $response = $this->makeRequest($this->baseUrl . '/reportes/generar', 'POST', $reportData);
            $passed = in_array($response['http_code'], [200, 302, 403, 422]);
            $this->addTestResult('reportes', 'reportes generar', $passed, [
                'http_code' => $response['http_code']
            ]);
        }
        
        // Test reportes exportar with ID 1
        $response = $this->makeRequest($this->baseUrl . '/reportes/exportar/1');
        $passed = in_array($response['http_code'], [200, 302, 403, 404]);
        $this->addTestResult('reportes', 'reportes exportar', $passed, [
            'http_code' => $response['http_code']
        ]);
    }
    
    public function testErrorHandling()
    {
        echo "\n=== TESTING ERROR HANDLING ===\n";
        
        // Test 404 for non-existent route
        $response = $this->makeRequest($this->baseUrl . '/non-existent-route');
        $passed = $response['http_code'] === 404;
        $this->addTestResult('error_handling', '404 for non-existent route', $passed, [
            'http_code' => $response['http_code']
        ]);
        
        // Test method not allowed
        $response = $this->makeRequest($this->baseUrl . '/login', 'DELETE');
        $passed = in_array($response['http_code'], [405, 302, 419]);
        $this->addTestResult('error_handling', 'Method not allowed', $passed, [
            'http_code' => $response['http_code']
        ]);
    }
    
    public function testRouteParameters()
    {
        echo "\n=== TESTING ROUTE PARAMETERS ===\n";
        
        $this->ensureAuthenticated();
        
        $modules = ['clientes', 'vehiculos', 'servicios', 'empleados'];
        
        foreach ($modules as $module) {
            // Test with invalid ID (non-numeric)
            $response = $this->makeRequest($this->baseUrl . '/' . $module . '/invalid-id');
            $passed = in_array($response['http_code'], [404, 302, 403]);
            $this->addTestResult('route_parameters', "$module invalid ID", $passed, [
                'http_code' => $response['http_code'],
                'url' => $this->baseUrl . '/' . $module . '/invalid-id'
            ]);
            
            // Test with very large ID
            $response = $this->makeRequest($this->baseUrl . '/' . $module . '/999999');
            $passed = in_array($response['http_code'], [200, 302, 403, 404]);
            $this->addTestResult('route_parameters', "$module large ID", $passed, [
                'http_code' => $response['http_code'],
                'url' => $this->baseUrl . '/' . $module . '/999999'
            ]);
        }
    }
    
    public function testMiddlewareProtection()
    {
        echo "\n=== TESTING MIDDLEWARE PROTECTION ===\n";
        
        // Clear cookies to test unauthenticated access
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
        $this->loggedIn = false;
        
        $protectedRoutes = [
            '/dashboard',
            '/profile',
            '/vehiculos',
            '/servicios',
            '/empleados',
            '/ordenes',
            '/reportes'
        ];
        
        foreach ($protectedRoutes as $route) {
            $response = $this->makeRequest($this->baseUrl . $route);
            // Should redirect to login (302) or return 401/403
            $passed = in_array($response['http_code'], [302, 401, 403]);
            $this->addTestResult('error_handling', "Middleware protection for $route", $passed, [
                'http_code' => $response['http_code'],
                'expected_redirect_or_forbidden' => true
            ]);
        }
    }
    
    public function runAllTests()
    {
        echo "STARTING COMPREHENSIVE ROUTE TESTING...\n";
        echo "Base URL: " . $this->baseUrl . "\n";
        echo "Cookie file: " . $this->cookieFile . "\n\n";
        
        $this->testAuthenticationRoutes();
        $this->testMainModuleRoutes();
        $this->testDashboardAndProfile();
        $this->testReportesRoutes();
        $this->testErrorHandling();
        $this->testRouteParameters();
        $this->testMiddlewareProtection();
        
        $this->generateReport();
    }
    
    private function generateReport()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "COMPREHENSIVE ROUTE TEST REPORT\n";
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
            echo str_repeat("-", 40) . "\n";
            
            foreach ($tests as $test) {
                $status = $test['passed'] ? '✓ PASS' : '✗ FAIL';
                echo sprintf("%-40s %s\n", $test['test'], $status);
                if (!$test['passed'] && !empty($test['details'])) {
                    echo "  Details: " . json_encode($test['details']) . "\n";
                }
            }
            echo "\n";
        }
        
        if (!empty($summary['errors'])) {
            echo "FAILED TESTS DETAILS:\n";
            echo str_repeat("-", 40) . "\n";
            foreach ($summary['errors'] as $error) {
                echo "• " . $error . "\n";
            }
        }
        
        // Save results to JSON file
        $reportFile = __DIR__ . '/route_test_report.json';
        file_put_contents($reportFile, json_encode($this->testResults, JSON_PRETTY_PRINT));
        echo "\nDetailed report saved to: $reportFile\n";
    }
}

// Run the tests
echo "Laravel Route Testing Script\n";
echo "============================\n";

$tester = new RouteBackendTester();
$tester->runAllTests();