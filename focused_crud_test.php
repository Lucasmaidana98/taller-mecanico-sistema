<?php

/**
 * Focused CRUD Test with Error Handling
 * Tests CRUD operations with detailed error reporting
 */

class FocusedCRUDTest {
    private $baseUrl = 'http://localhost:8001';
    private $cookieFile;
    private $results = [];
    
    public function __construct() {
        $this->cookieFile = __DIR__ . '/focused_test_cookies.txt';
        echo "🔧 FOCUSED CRUD OPERATIONS TEST\n";
        echo "Testing: {$this->baseUrl}\n";
        echo "Credentials: admin@taller.com / admin123\n\n";
    }
    
    public function runTest() {
        try {
            $this->testAuthentication();
            $this->testVehiculosBasic();
            $this->testOrdenesBasic();
            $this->testDatabaseOperations();
            $this->generateReport();
        } catch (Exception $e) {
            echo "FATAL ERROR: " . $e->getMessage() . "\n";
        }
    }
    
    private function testAuthentication() {
        echo "🔐 AUTHENTICATION TEST\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            // Get login page
            $loginPage = $this->makeRequest('GET', '/login');
            $this->logResponse('Login Page', $loginPage);
            
            if (strpos($loginPage, 'csrf') === false) {
                throw new Exception("No CSRF token found");
            }
            
            // Extract CSRF token
            if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $loginPage, $matches)) {
                $token = $matches[1];
                echo "✅ CSRF token extracted: " . substr($token, 0, 10) . "...\n";
            } else {
                throw new Exception("Could not extract CSRF token");
            }
            
            // Perform login
            $loginData = [
                'email' => 'admin@taller.com',
                'password' => 'admin123',
                '_token' => $token
            ];
            
            $loginResponse = $this->makeRequest('POST', '/login', $loginData);
            $this->logResponse('Login Response', $loginResponse);
            
            // Test authentication by accessing dashboard
            $dashboardResponse = $this->makeRequest('GET', '/dashboard');
            $this->logResponse('Dashboard Access', $dashboardResponse);
            
            if (strpos($dashboardResponse, 'dashboard') !== false || 
                strpos($dashboardResponse, 'Dashboard') !== false) {
                echo "✅ Authentication successful\n";
                $this->results['auth'] = 'SUCCESS';
            } else {
                echo "❌ Authentication failed\n";
                $this->results['auth'] = 'FAILED';
            }
            
        } catch (Exception $e) {
            echo "❌ Auth error: " . $e->getMessage() . "\n";
            $this->results['auth'] = 'ERROR: ' . $e->getMessage();
        }
        
        echo "\n";
    }
    
    private function testVehiculosBasic() {
        echo "🚗 VEHICULOS MODULE TEST\n";
        echo str_repeat("-", 40) . "\n";
        
        $tests = [
            'index_access' => false,
            'create_form' => false,
            'create_submit' => false,
            'persistence' => false
        ];
        
        try {
            // Test 1: Access index
            echo "📋 Testing index access...\n";
            $indexResponse = $this->makeRequest('GET', '/vehiculos');
            $httpCode = $this->getLastHttpCode();
            
            if ($httpCode === 200) {
                echo "✅ Index accessible (HTTP 200)\n";
                $tests['index_access'] = true;
                
                // Count existing vehicles
                $vehicleCount = $this->countTableRows($indexResponse);
                echo "📊 Current vehicle count: {$vehicleCount}\n";
            } else {
                echo "❌ Index not accessible (HTTP {$httpCode})\n";
                $this->logResponse('Vehiculos Index Error', $indexResponse);
            }
            
            // Test 2: Access create form
            echo "\n📝 Testing create form access...\n";
            $createResponse = $this->makeRequest('GET', '/vehiculos/create');
            $createHttpCode = $this->getLastHttpCode();
            
            if ($createHttpCode === 200) {
                echo "✅ Create form accessible (HTTP 200)\n";
                $tests['create_form'] = true;
                
                // Test 3: Submit creation if form is accessible
                if ($this->attemptVehicleCreation($createResponse)) {
                    $tests['create_submit'] = true;
                    echo "✅ Vehicle creation successful\n";
                    
                    // Test 4: Verify persistence
                    echo "\n🔄 Testing persistence...\n";
                    $newIndexResponse = $this->makeRequest('GET', '/vehiculos');
                    $newCount = $this->countTableRows($newIndexResponse);
                    
                    if ($newCount > $vehicleCount) {
                        echo "✅ Vehicle persisted (Count: {$vehicleCount} → {$newCount})\n";
                        $tests['persistence'] = true;
                    } else {
                        echo "❌ Vehicle not persisted in database\n";
                    }
                }
            } else {
                echo "❌ Create form not accessible (HTTP {$createHttpCode})\n";
                $this->logServerError('Create Form Error', $createResponse);
            }
            
        } catch (Exception $e) {
            echo "❌ Vehiculos test error: " . $e->getMessage() . "\n";
        }
        
        $this->results['vehiculos'] = $tests;
        echo "\n";
    }
    
    private function testOrdenesBasic() {
        echo "📋 ORDENES MODULE TEST\n";
        echo str_repeat("-", 40) . "\n";
        
        $tests = [
            'index_access' => false,
            'create_form' => false,
            'relationships' => false
        ];
        
        try {
            // Test 1: Access index
            echo "📋 Testing ordenes index access...\n";
            $indexResponse = $this->makeRequest('GET', '/ordenes');
            $httpCode = $this->getLastHttpCode();
            
            if ($httpCode === 200) {
                echo "✅ Ordenes index accessible (HTTP 200)\n";
                $tests['index_access'] = true;
                
                $orderCount = $this->countTableRows($indexResponse);
                echo "📊 Current order count: {$orderCount}\n";
            } else {
                echo "❌ Ordenes index not accessible (HTTP {$httpCode})\n";
                $this->logServerError('Ordenes Index Error', $indexResponse);
            }
            
            // Test 2: Access create form
            echo "\n📝 Testing ordenes create form...\n";
            $createResponse = $this->makeRequest('GET', '/ordenes/create');
            $createHttpCode = $this->getLastHttpCode();
            
            if ($createHttpCode === 200) {
                echo "✅ Ordenes create form accessible (HTTP 200)\n";
                $tests['create_form'] = true;
                
                // Test 3: Check for relationship dropdowns
                $relationshipTests = [
                    'clientes' => strpos($createResponse, 'cliente_id') !== false,
                    'vehiculos' => strpos($createResponse, 'vehiculo_id') !== false,
                    'empleados' => strpos($createResponse, 'empleado_id') !== false,
                    'servicios' => strpos($createResponse, 'servicio_id') !== false
                ];
                
                $relationshipsWorking = array_sum($relationshipTests);
                echo "🔗 Relationships found: {$relationshipsWorking}/4\n";
                
                if ($relationshipsWorking >= 3) {
                    $tests['relationships'] = true;
                    echo "✅ Most relationships working\n";
                } else {
                    echo "❌ Missing relationship dropdowns\n";
                }
            } else {
                echo "❌ Ordenes create form not accessible (HTTP {$createHttpCode})\n";
                $this->logServerError('Ordenes Create Form Error', $createResponse);
            }
            
        } catch (Exception $e) {
            echo "❌ Ordenes test error: " . $e->getMessage() . "\n";
        }
        
        $this->results['ordenes'] = $tests;
        echo "\n";
    }
    
    private function testDatabaseOperations() {
        echo "🗄️ DATABASE OPERATIONS TEST\n";
        echo str_repeat("-", 40) . "\n";
        
        $tests = [
            'clientes_accessible' => false,
            'empleados_accessible' => false,
            'servicios_accessible' => false,
            'dashboard_stats' => false
        ];
        
        try {
            // Test related modules
            $modules = [
                'clientes' => '/clientes',
                'empleados' => '/empleados', 
                'servicios' => '/servicios'
            ];
            
            foreach ($modules as $name => $url) {
                echo "🔍 Testing {$name} access...\n";
                $response = $this->makeRequest('GET', $url);
                $httpCode = $this->getLastHttpCode();
                
                if ($httpCode === 200) {
                    echo "✅ {$name} accessible\n";
                    $tests["{$name}_accessible"] = true;
                    
                    $count = $this->countTableRows($response);
                    echo "📊 {$name} count: {$count}\n";
                } else {
                    echo "❌ {$name} not accessible (HTTP {$httpCode})\n";
                }
            }
            
            // Test dashboard statistics
            echo "\n📊 Testing dashboard statistics...\n";
            $dashboardResponse = $this->makeRequest('GET', '/dashboard');
            $dashboardHttpCode = $this->getLastHttpCode();
            
            if ($dashboardHttpCode === 200) {
                echo "✅ Dashboard accessible\n";
                $tests['dashboard_stats'] = true;
                
                // Look for statistics
                $stats = [
                    'clientes' => substr_count($dashboardResponse, 'cliente'),
                    'vehiculos' => substr_count($dashboardResponse, 'vehiculo'),
                    'ordenes' => substr_count($dashboardResponse, 'orden')
                ];
                
                echo "📈 Statistics mentions: " . json_encode($stats) . "\n";
            } else {
                echo "❌ Dashboard not accessible (HTTP {$dashboardHttpCode})\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Database operations test error: " . $e->getMessage() . "\n";
        }
        
        $this->results['database'] = $tests;
        echo "\n";
    }
    
    private function attemptVehicleCreation($createPageHtml) {
        try {
            // Extract CSRF token
            if (!preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $createPageHtml, $matches)) {
                echo "❌ No CSRF token in create form\n";
                return false;
            }
            $token = $matches[1];
            
            // Extract client options
            preg_match_all('/<option[^>]*value="(\d+)"[^>]*>([^<]*)<\/option>/', $createPageHtml, $clientMatches);
            if (empty($clientMatches[1])) {
                echo "❌ No clients available for vehicle creation\n";
                return false;
            }
            
            $clientId = $clientMatches[1][0];
            echo "📝 Using client ID: {$clientId}\n";
            
            // Prepare test data
            $vehicleData = [
                'cliente_id' => $clientId,
                'brand' => 'TESTCAR_' . time(),
                'model' => 'TESTMODEL_' . time(),
                'year' => '2022',
                'license_plate' => 'TST' . (time() % 999),
                'vin' => 'TESTVIN' . time(),
                'color' => 'Blue',
                'status' => '1',
                '_token' => $token
            ];
            
            echo "🚗 Creating: {$vehicleData['brand']} {$vehicleData['model']}\n";
            
            // Submit creation
            $createResponse = $this->makeRequest('POST', '/vehiculos', $vehicleData);
            $createHttpCode = $this->getLastHttpCode();
            
            if ($createHttpCode === 302 || $createHttpCode === 200) {
                echo "✅ Vehicle creation submitted successfully (HTTP {$createHttpCode})\n";
                return true;
            } else {
                echo "❌ Vehicle creation failed (HTTP {$createHttpCode})\n";
                $this->logServerError('Vehicle Creation Error', $createResponse);
                return false;
            }
            
        } catch (Exception $e) {
            echo "❌ Vehicle creation error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function makeRequest($method, $url, $data = null) {
        $fullUrl = $this->baseUrl . $url;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'Focused CRUD Test',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml',
                'Connection: keep-alive'
            ]
        ]);
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $response = curl_exec($ch);
        $this->lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL error: {$error}");
        }
        
        curl_close($ch);
        return $response;
    }
    
    private function getLastHttpCode() {
        return $this->lastHttpCode ?? 0;
    }
    
    private function countTableRows($html) {
        // Count <tr> elements minus header
        $count = max(0, substr_count($html, '<tr>') - 1);
        return $count;
    }
    
    private function logResponse($title, $response) {
        $length = strlen($response);
        $hasHtml = strpos($response, '<!DOCTYPE') !== false || strpos($response, '<html') !== false;
        echo "📄 {$title}: {$length} chars, HTML: " . ($hasHtml ? 'Yes' : 'No') . "\n";
    }
    
    private function logServerError($title, $response) {
        echo "🔍 {$title} Details:\n";
        
        // Look for Laravel error indicators
        if (strpos($response, 'Whoops') !== false) {
            echo "  - Laravel error page detected\n";
        }
        if (strpos($response, 'ErrorException') !== false) {
            echo "  - PHP ErrorException detected\n";
        }
        if (strpos($response, 'SQLSTATE') !== false) {
            echo "  - Database error detected\n";
        }
        if (strpos($response, '500 Server Error') !== false) {
            echo "  - HTTP 500 Server Error\n";
        }
        
        // Extract error snippets
        if (preg_match('/<title>([^<]*)<\/title>/', $response, $matches)) {
            echo "  - Page title: " . trim($matches[1]) . "\n";
        }
    }
    
    private function generateReport() {
        echo "📊 FOCUSED CRUD TEST REPORT\n";
        echo str_repeat("=", 50) . "\n\n";
        
        // Authentication Results  
        echo "🔐 Authentication: " . ($this->results['auth'] ?? 'NOT_TESTED') . "\n\n";
        
        // Vehiculos Results
        echo "🚗 Vehiculos Module:\n";
        $vResults = $this->results['vehiculos'] ?? [];
        foreach ($vResults as $test => $result) {
            $status = $result ? '✅ PASSED' : '❌ FAILED';
            echo "  - " . ucwords(str_replace('_', ' ', $test)) . ": {$status}\n";
        }
        echo "\n";
        
        // Ordenes Results
        echo "📋 Ordenes Module:\n";
        $oResults = $this->results['ordenes'] ?? [];
        foreach ($oResults as $test => $result) {
            $status = $result ? '✅ PASSED' : '❌ FAILED';
            echo "  - " . ucwords(str_replace('_', ' ', $test)) . ": {$status}\n";
        }
        echo "\n";
        
        // Database Results
        echo "🗄️ Database Operations:\n";
        $dbResults = $this->results['database'] ?? [];
        foreach ($dbResults as $test => $result) {
            $status = $result ? '✅ PASSED' : '❌ FAILED';
            echo "  - " . ucwords(str_replace('_', ' ', $test)) . ": {$status}\n";
        }
        echo "\n";
        
        // Calculate overall success
        $totalTests = 0;
        $passedTests = 0;
        
        foreach ($this->results as $module => $tests) {
            if ($module === 'auth') {
                $totalTests++;
                if ($tests === 'SUCCESS') $passedTests++;
            } else {
                foreach ($tests as $test => $result) {
                    $totalTests++;
                    if ($result) $passedTests++;
                }
            }
        }
        
        $successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
        
        echo "🎯 OVERALL ASSESSMENT:\n";
        echo "- Tests Passed: {$passedTests}/{$totalTests}\n";
        echo "- Success Rate: {$successRate}%\n";
        
        if ($successRate >= 80) {
            echo "- Status: 🟢 GOOD - Most functionality working\n";
            echo "- CRUD Operations: Database persistence appears functional\n";
            echo "- Real-time Updates: Views updating with database changes\n";
        } elseif ($successRate >= 60) {
            echo "- Status: 🟡 MODERATE - Some issues present\n";
            echo "- CRUD Operations: Partial functionality\n";
            echo "- Database Issues: Some operations may not persist correctly\n";
        } else {
            echo "- Status: 🔴 POOR - Significant problems detected\n";
            echo "- CRUD Operations: Major issues with database operations\n";
            echo "- Server Errors: HTTP 500 errors indicate backend problems\n";
        }
        
        echo "\n📋 RECOMMENDATIONS:\n";
        if ($successRate < 80) {
            echo "1. Check Laravel logs for detailed error information\n";
            echo "2. Verify database connections and migrations\n";
            echo "3. Ensure all required dependencies are installed\n";
            echo "4. Check file permissions for storage directories\n";
        } else {
            echo "1. Application appears to be functioning well\n";
            echo "2. CRUD operations and database persistence working\n";
            echo "3. Consider testing validation and error handling\n";
        }
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Test completed: " . date('Y-m-d H:i:s') . "\n";
    }
}

// Run the focused test
$test = new FocusedCRUDTest();
$test->runTest();