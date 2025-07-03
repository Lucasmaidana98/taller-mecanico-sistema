<?php

/**
 * Method Investigation Test
 * Focuses on HTTP method routing issues
 */

set_time_limit(300);

class MethodInvestigationTester {
    private $baseUrl = 'http://localhost:8003';
    private $cookieFile;
    private $csrfToken = null;

    public function __construct() {
        $this->cookieFile = __DIR__ . '/method_test_cookies.txt';
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    private function makeRequest($url, $options = []) {
        $ch = curl_init();
        
        // Default options
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Method Test Bot');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, true);

        // Apply additional options
        foreach ($options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        
        curl_close($ch);

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        return [
            'success' => !$error,
            'response' => $body,
            'headers' => $headers,
            'http_code' => $httpCode,
            'error' => $error
        ];
    }

    private function extractCsrfToken($html) {
        if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }
        if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function authenticate() {
        echo "=== AUTHENTICATION ===\n";
        
        $result = $this->makeRequest('/login');
        if (!$result['success'] || $result['http_code'] !== 200) {
            echo "Login page failed\n";
            return false;
        }

        $this->csrfToken = $this->extractCsrfToken($result['response']);
        if (!$this->csrfToken) {
            echo "No CSRF token found\n";
            return false;
        }

        $loginData = http_build_query([
            '_token' => $this->csrfToken,
            'email' => 'admin@taller.com',
            'password' => 'admin123'
        ]);

        $loginResult = $this->makeRequest('/login', [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $loginData,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);

        if ($loginResult['http_code'] === 302) {
            echo "Authentication successful\n";
            $dashboardResult = $this->makeRequest('/dashboard');
            $newToken = $this->extractCsrfToken($dashboardResult['response']);
            if ($newToken) {
                $this->csrfToken = $newToken;
            }
            return true;
        }

        echo "Authentication failed\n";
        return false;
    }

    public function testHttpMethods() {
        echo "\n=== TESTING HTTP METHODS ===\n";
        
        // First create a test client
        $createFormResult = $this->makeRequest('/clientes/create');
        $createCsrfToken = $this->extractCsrfToken($createFormResult['response']);
        
        if (!$createCsrfToken) {
            echo "Cannot get CSRF token for create\n";
            return;
        }

        echo "Creating test client...\n";
        $createData = http_build_query([
            '_token' => $createCsrfToken,
            'name' => 'HTTP Method Test Client ' . time(),
            'email' => 'method.test.' . time() . '@example.com',
            'phone' => '555-METHOD-TEST',
            'document_number' => 'METHOD' . time(),
            'address' => 'Method Test Address',
            'status' => '1'
        ]);

        $createResult = $this->makeRequest('/clientes', [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $createData,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);

        echo "Create Result: HTTP {$createResult['http_code']}\n";

        // Get the created client ID
        $indexResult = $this->makeRequest('/clientes');
        preg_match_all('/clientes\/(\d+)\/edit/', $indexResult['response'], $matches);
        
        if (empty($matches[1])) {
            echo "No clients found for method testing\n";
            return;
        }

        $clienteId = end($matches[1]); // Get the newest one
        echo "Using cliente ID: $clienteId for method testing\n";

        // Test different HTTP methods
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        
        foreach ($methods as $method) {
            echo "\nTesting $method /clientes/$clienteId\n";
            
            $options = [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => [
                    'X-CSRF-TOKEN: ' . $this->csrfToken,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]
            ];

            if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $testData = json_encode([
                    'name' => "Test $method Update",
                    'email' => 'method.test@example.com',
                    'phone' => '555-000-0000',
                    'document_number' => 'TEST123',
                    'address' => 'Test Address',
                    'status' => 1
                ]);
                $options[CURLOPT_POSTFIELDS] = $testData;
            }

            $result = $this->makeRequest("/clientes/$clienteId", $options);
            echo "$method Result: HTTP {$result['http_code']}\n";
            
            if ($result['http_code'] >= 400) {
                echo "Error response: " . substr($result['response'], 0, 200) . "\n";
            }
        }
    }

    public function testPutMethodWithFormData() {
        echo "\n=== TESTING PUT WITH TRADITIONAL FORM DATA ===\n";
        
        // Get a client to update
        $indexResult = $this->makeRequest('/clientes');
        preg_match_all('/clientes\/(\d+)\/edit/', $indexResult['response'], $matches);
        
        if (empty($matches[1])) {
            echo "No clients found\n";
            return;
        }

        $clienteId = $matches[1][0];
        echo "Testing PUT for cliente ID: $clienteId\n";

        // Get edit form
        $editResult = $this->makeRequest("/clientes/$clienteId/edit");
        $editCsrfToken = $this->extractCsrfToken($editResult['response']);
        
        if (!$editCsrfToken) {
            echo "No CSRF token in edit form\n";
            return;
        }

        // Method 1: POST with _method=PUT (Laravel's method spoofing)
        echo "\nMethod 1: POST with _method=PUT\n";
        $updateData1 = http_build_query([
            '_token' => $editCsrfToken,
            '_method' => 'PUT',
            'name' => 'PUT Test 1 - ' . time(),
            'email' => 'put.test1.' . time() . '@example.com',
            'phone' => '555-PUT-TEST1',
            'document_number' => 'PUT1' . time(),
            'address' => 'PUT Test Address 1',
            'status' => '1'
        ]);

        $result1 = $this->makeRequest("/clientes/$clienteId", [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $updateData1,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'X-CSRF-TOKEN: ' . $editCsrfToken
            ]
        ]);

        echo "Method 1 Result: HTTP {$result1['http_code']}\n";
        if ($result1['http_code'] >= 400) {
            echo "Error: " . substr($result1['response'], 0, 500) . "\n";
        }

        // Method 2: Actual PUT request
        echo "\nMethod 2: Actual PUT request\n";
        $updateData2 = http_build_query([
            '_token' => $editCsrfToken,
            'name' => 'PUT Test 2 - ' . time(),
            'email' => 'put.test2.' . time() . '@example.com',
            'phone' => '555-PUT-TEST2',
            'document_number' => 'PUT2' . time(),
            'address' => 'PUT Test Address 2',
            'status' => '1'
        ]);

        $result2 = $this->makeRequest("/clientes/$clienteId", [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $updateData2,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'X-CSRF-TOKEN: ' . $editCsrfToken
            ]
        ]);

        echo "Method 2 Result: HTTP {$result2['http_code']}\n";
        if ($result2['http_code'] >= 400) {
            echo "Error: " . substr($result2['response'], 0, 500) . "\n";
        }

        // Method 3: PUT with JSON
        echo "\nMethod 3: PUT with JSON\n";
        $updateData3 = json_encode([
            'name' => 'PUT Test 3 - ' . time(),
            'email' => 'put.test3.' . time() . '@example.com',
            'phone' => '555-PUT-TEST3',
            'document_number' => 'PUT3' . time(),
            'address' => 'PUT Test Address 3',
            'status' => 1
        ]);

        $result3 = $this->makeRequest("/clientes/$clienteId", [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $updateData3,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-CSRF-TOKEN: ' . $editCsrfToken,
                'Accept: application/json'
            ]
        ]);

        echo "Method 3 Result: HTTP {$result3['http_code']}\n";
        echo "Response: " . substr($result3['response'], 0, 300) . "\n";
    }

    public function testDeleteMethods() {
        echo "\n=== TESTING DELETE METHODS ===\n";
        
        // Create a test client to delete
        $createFormResult = $this->makeRequest('/clientes/create');
        $createCsrfToken = $this->extractCsrfToken($createFormResult['response']);
        
        $createData = http_build_query([
            '_token' => $createCsrfToken,
            'name' => 'Delete Test Client ' . time(),
            'email' => 'delete.test.' . time() . '@example.com',
            'phone' => '555-DELETE-TEST',
            'document_number' => 'DEL' . time(),
            'address' => 'Delete Test Address',
            'status' => '1'
        ]);

        $createResult = $this->makeRequest('/clientes', [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $createData,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);

        if ($createResult['http_code'] !== 200 && $createResult['http_code'] !== 302) {
            echo "Failed to create test client for deletion\n";
            return;
        }

        // Get the client ID
        $indexResult = $this->makeRequest('/clientes');
        preg_match_all('/clientes\/(\d+)/', $indexResult['response'], $matches);
        
        if (empty($matches[1])) {
            echo "No clients found for delete testing\n";
            return;
        }

        $clienteId = end($matches[1]);
        echo "Testing DELETE for cliente ID: $clienteId\n";

        // Method 1: POST with _method=DELETE
        echo "\nMethod 1: POST with _method=DELETE\n";
        $deleteData1 = http_build_query([
            '_token' => $this->csrfToken,
            '_method' => 'DELETE'
        ]);

        $result1 = $this->makeRequest("/clientes/$clienteId", [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $deleteData1,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'X-CSRF-TOKEN: ' . $this->csrfToken
            ]
        ]);

        echo "Method 1 Result: HTTP {$result1['http_code']}\n";
        if ($result1['http_code'] >= 400) {
            echo "Error: " . substr($result1['response'], 0, 300) . "\n";
        }

        // Check if client was actually deleted
        echo "\nVerifying deletion (should return 404 or redirect):\n";
        $verifyResult = $this->makeRequest("/clientes/$clienteId");
        echo "Verification: HTTP {$verifyResult['http_code']}\n";
    }

    public function testRouteExistence() {
        echo "\n=== TESTING ROUTE EXISTENCE ===\n";
        
        $routes = [
            'GET /clientes' => '/clientes',
            'GET /clientes/create' => '/clientes/create',
            'POST /clientes' => '/clientes',
            'GET /clientes/1' => '/clientes/1',
            'GET /clientes/1/edit' => '/clientes/1/edit',
            'PUT /clientes/1' => '/clientes/1',
            'DELETE /clientes/1' => '/clientes/1'
        ];

        foreach ($routes as $routeName => $url) {
            echo "\nTesting $routeName\n";
            
            $method = explode(' ', $routeName)[0];
            $options = [
                CURLOPT_HTTPHEADER => [
                    'X-CSRF-TOKEN: ' . $this->csrfToken,
                    'Accept: application/json'
                ]
            ];

            if ($method !== 'GET') {
                $options[CURLOPT_CUSTOMREQUEST] = $method;
                if (in_array($method, ['POST', 'PUT'])) {
                    $options[CURLOPT_POSTFIELDS] = '{}';
                    $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
                }
            }

            $result = $this->makeRequest($url, $options);
            echo "$routeName: HTTP {$result['http_code']}\n";
        }
    }

    public function runMethodInvestigation() {
        echo "Starting HTTP Method Investigation...\n\n";
        
        if (!$this->authenticate()) {
            return;
        }

        $this->testRouteExistence();
        $this->testHttpMethods();
        $this->testPutMethodWithFormData();
        $this->testDeleteMethods();

        echo "\n=== METHOD INVESTIGATION COMPLETE ===\n";
    }
}

// Run the method investigation
$tester = new MethodInvestigationTester();
$tester->runMethodInvestigation();

?>