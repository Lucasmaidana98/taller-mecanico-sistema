<?php

/**
 * Detailed Issue Investigation Test
 * Focuses on DELETE operations and 500 errors
 */

set_time_limit(300);

class DetailedIssueTester {
    private $baseUrl = 'http://localhost:8003';
    private $cookieFile;
    private $csrfToken = null;

    public function __construct() {
        $this->cookieFile = __DIR__ . '/detailed_test_cookies.txt';
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    private function makeRequest($url, $postData = null, $headers = [], $method = 'GET') {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects to see actual response
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Detailed Test Bot');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in response

        $defaultHeaders = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ];

        if ($this->csrfToken) {
            $defaultHeaders[] = 'X-CSRF-TOKEN: ' . $this->csrfToken;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));

        if ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        if ($postData !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
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

        $loginResult = $this->makeRequest('/login', $loginData, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        if ($loginResult['http_code'] === 302) {
            echo "Authentication successful\n";
            // Get new CSRF token
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

    public function investigateUpdateError() {
        echo "\n=== INVESTIGATING UPDATE ERROR ===\n";
        
        // Get a client to edit
        $indexResult = $this->makeRequest('/clientes');
        preg_match_all('/clientes\/(\d+)\/edit/', $indexResult['response'], $matches);
        
        if (empty($matches[1])) {
            echo "No clients found to edit\n";
            return;
        }

        $clienteId = $matches[1][0];
        echo "Testing UPDATE for cliente ID: $clienteId\n";

        // Get edit form
        $editResult = $this->makeRequest("/clientes/$clienteId/edit");
        if ($editResult['http_code'] !== 200) {
            echo "Edit form failed: HTTP {$editResult['http_code']}\n";
            return;
        }

        $editCsrfToken = $this->extractCsrfToken($editResult['response']);
        if (!$editCsrfToken) {
            echo "No CSRF token in edit form\n";
            return;
        }

        // Extract current client data from form
        preg_match('/name="name"[^>]*value="([^"]*)"/', $editResult['response'], $nameMatch);
        preg_match('/name="email"[^>]*value="([^"]*)"/', $editResult['response'], $emailMatch);
        preg_match('/name="phone"[^>]*value="([^"]*)"/', $editResult['response'], $phoneMatch);
        preg_match('/name="document_number"[^>]*value="([^"]*)"/', $editResult['response'], $docMatch);
        preg_match('/<textarea[^>]*name="address"[^>]*>([^<]*)<\/textarea>/', $editResult['response'], $addressMatch);

        $currentData = [
            'name' => $nameMatch[1] ?? 'Unknown',
            'email' => $emailMatch[1] ?? 'unknown@example.com',
            'phone' => $phoneMatch[1] ?? '000-000-0000',
            'document_number' => $docMatch[1] ?? 'UNKNOWN',
            'address' => $addressMatch[1] ?? 'Unknown Address'
        ];

        echo "Current client data:\n";
        foreach ($currentData as $key => $value) {
            echo "  $key: $value\n";
        }

        // Test with minimal changes to existing data
        $updateData = http_build_query([
            '_token' => $editCsrfToken,
            '_method' => 'PUT',
            'name' => $currentData['name'] . ' (Updated)',
            'email' => $currentData['email'],
            'phone' => $currentData['phone'],
            'document_number' => $currentData['document_number'],
            'address' => $currentData['address'],
            'status' => '1'
        ]);

        echo "\nAttempting UPDATE...\n";
        $updateResult = $this->makeRequest("/clientes/$clienteId", $updateData, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        echo "Update Response:\n";
        echo "HTTP Code: {$updateResult['http_code']}\n";
        echo "Headers:\n{$updateResult['headers']}\n";
        
        if ($updateResult['http_code'] === 500) {
            echo "500 Error Details:\n";
            if (strpos($updateResult['response'], 'Whoops') !== false) {
                // Extract error from Laravel error page
                if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/', $updateResult['response'], $errorMatch)) {
                    echo "Error: {$errorMatch[1]}\n";
                }
                if (preg_match('/<pre[^>]*>([^<]+)<\/pre>/', $updateResult['response'], $stackMatch)) {
                    echo "Stack trace snippet: " . substr($stackMatch[1], 0, 500) . "\n";
                }
            } else {
                echo "Response body: " . substr($updateResult['response'], 0, 1000) . "\n";
            }
        }

        // Test with AJAX request
        echo "\nTesting AJAX UPDATE...\n";
        $ajaxUpdateData = json_encode([
            'name' => $currentData['name'] . ' (AJAX Updated)',
            'email' => $currentData['email'],
            'phone' => $currentData['phone'],
            'document_number' => $currentData['document_number'],
            'address' => $currentData['address'],
            'status' => 1
        ]);

        $ajaxResult = $this->makeRequest("/clientes/$clienteId", $ajaxUpdateData, [
            'Content-Type: application/json',
            'X-Requested-With: XMLHttpRequest',
            'X-CSRF-TOKEN: ' . $editCsrfToken
        ], 'PUT');

        echo "AJAX Update Response:\n";
        echo "HTTP Code: {$ajaxResult['http_code']}\n";
        echo "Response: " . substr($ajaxResult['response'], 0, 500) . "\n";
    }

    public function testDeleteOperation() {
        echo "\n=== TESTING DELETE OPERATION ===\n";
        
        // Create a test client first
        $createFormResult = $this->makeRequest('/clientes/create');
        $createCsrfToken = $this->extractCsrfToken($createFormResult['response']);
        
        if (!$createCsrfToken) {
            echo "Cannot create test client - no CSRF token\n";
            return;
        }

        $createData = http_build_query([
            '_token' => $createCsrfToken,
            'name' => 'Test Delete Client ' . time(),
            'email' => 'delete.test.' . time() . '@example.com',
            'phone' => '555-DELETE-ME',
            'document_number' => 'DEL' . time(),
            'address' => 'Delete Test Address',
            'status' => '1'
        ]);

        $createResult = $this->makeRequest('/clientes', $createData, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        if ($createResult['http_code'] !== 200 && $createResult['http_code'] !== 302) {
            echo "Failed to create test client\n";
            return;
        }

        // Get the created client ID
        $indexResult = $this->makeRequest('/clientes');
        preg_match_all('/clientes\/(\d+).*DELETE/', $indexResult['response'], $matches);
        
        if (empty($matches[1])) {
            echo "No deletable clients found\n";
            return;
        }

        $clienteId = end($matches[1]); // Get the last one (newest)
        echo "Testing DELETE for cliente ID: $clienteId\n";

        // Test traditional form DELETE
        echo "\nTesting TRADITIONAL DELETE...\n";
        $deleteData = http_build_query([
            '_token' => $this->csrfToken,
            '_method' => 'DELETE'
        ]);

        $deleteResult = $this->makeRequest("/clientes/$clienteId", $deleteData, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        echo "Traditional Delete Response:\n";
        echo "HTTP Code: {$deleteResult['http_code']}\n";
        echo "Headers:\n{$deleteResult['headers']}\n";
        
        if ($deleteResult['http_code'] === 500) {
            echo "Delete Error Details:\n";
            echo "Response: " . substr($deleteResult['response'], 0, 1000) . "\n";
        }

        // Test AJAX DELETE
        echo "\nTesting AJAX DELETE...\n";
        $ajaxDeleteResult = $this->makeRequest("/clientes/$clienteId", '', [
            'X-Requested-With: XMLHttpRequest',
            'X-CSRF-TOKEN: ' . $this->csrfToken,
            'Content-Type: application/json'
        ], 'DELETE');

        echo "AJAX Delete Response:\n";
        echo "HTTP Code: {$ajaxDeleteResult['http_code']}\n";
        echo "Response: " . substr($ajaxDeleteResult['response'], 0, 500) . "\n";

        // Verify deletion by checking if client still exists
        echo "\nVerifying deletion...\n";
        $showResult = $this->makeRequest("/clientes/$clienteId");
        echo "Show deleted client: HTTP {$showResult['http_code']}\n";
    }

    public function testAllModulesDeleteOperations() {
        echo "\n=== TESTING DELETE OPERATIONS IN ALL MODULES ===\n";
        
        $modules = ['vehiculos', 'servicios', 'empleados'];
        
        foreach ($modules as $module) {
            echo "\nTesting $module DELETE operations...\n";
            
            // Get index to find deletable items
            $indexResult = $this->makeRequest("/$module");
            if ($indexResult['http_code'] !== 200) {
                echo "$module index not accessible\n";
                continue;
            }

            // Look for delete forms/buttons
            preg_match_all("/$module\/(\d+).*DELETE/", $indexResult['response'], $matches);
            
            if (empty($matches[1])) {
                echo "No deletable items found in $module\n";
                continue;
            }

            $itemId = $matches[1][0];
            echo "Found deletable $module ID: $itemId\n";

            // Test DELETE request
            $deleteResult = $this->makeRequest("/$module/$itemId", http_build_query([
                '_token' => $this->csrfToken,
                '_method' => 'DELETE'
            ]), [
                'Content-Type: application/x-www-form-urlencoded'
            ]);

            echo "$module DELETE result: HTTP {$deleteResult['http_code']}\n";
            
            if ($deleteResult['http_code'] === 500) {
                echo "ERROR in $module delete operation\n";
            } elseif ($deleteResult['http_code'] === 302) {
                echo "$module delete appears successful (redirected)\n";
            }
        }
    }

    public function runDetailedTests() {
        echo "Starting Detailed Issue Investigation...\n\n";
        
        if (!$this->authenticate()) {
            return;
        }

        $this->investigateUpdateError();
        $this->testDeleteOperation();
        $this->testAllModulesDeleteOperations();

        echo "\n=== INVESTIGATION COMPLETE ===\n";
    }
}

// Run the detailed test
$tester = new DetailedIssueTester();
$tester->runDetailedTests();

?>