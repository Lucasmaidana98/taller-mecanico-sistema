<?php

/**
 * Detailed Form Analysis and Manual CRUD Testing
 * This script performs manual browser-like testing to understand form structures
 */

class DetailedFormAnalyzer {
    private $baseUrl = 'http://localhost:8002';
    private $cookieFile = 'detailed_form_cookies.txt';
    
    public function __construct() {
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
        touch($this->cookieFile);
    }
    
    private function makeRequest($url, $data = null, $method = 'GET', $headers = []) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false, // Don't follow redirects automatically
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        return [
            'response' => $response,
            'http_code' => $httpCode,
            'redirect_url' => $redirectUrl,
            'error' => $error
        ];
    }
    
    private function authenticate() {
        echo "=== AUTHENTICATING ===\n";
        
        // Get login page
        $loginPage = $this->makeRequest('/login');
        if (preg_match('/<meta name="csrf-token" content="([^"]*)"/', $loginPage['response'], $matches)) {
            $csrfToken = $matches[1];
        } else {
            throw new Exception('CSRF token not found');
        }
        
        // Login
        $loginData = http_build_query([
            '_token' => $csrfToken,
            'email' => 'admin@taller.com',
            'password' => 'admin123'
        ]);
        
        $loginResult = $this->makeRequest('/login', $loginData, 'POST');
        
        if ($loginResult['http_code'] === 302 && strpos($loginResult['redirect_url'], '/dashboard') !== false) {
            echo "✓ Authentication successful\n";
            return true;
        } else {
            throw new Exception('Authentication failed');
        }
    }
    
    private function analyzeClientesForm() {
        echo "\n=== ANALYZING CLIENTES FORM ===\n";
        
        $createPage = $this->makeRequest('/clientes/create');
        
        if ($createPage['http_code'] !== 200) {
            echo "✗ Cannot access clientes create form (HTTP {$createPage['http_code']})\n";
            return;
        }
        
        echo "✓ Clientes create form accessible\n";
        
        // Extract form structure
        preg_match_all('/<input[^>]*name="([^"]*)"[^>]*>/', $createPage['response'], $inputMatches);
        preg_match_all('/<textarea[^>]*name="([^"]*)"[^>]*>/', $createPage['response'], $textareaMatches);
        preg_match_all('/<select[^>]*name="([^"]*)"[^>]*>/', $createPage['response'], $selectMatches);
        
        $formFields = array_merge($inputMatches[1], $textareaMatches[1], $selectMatches[1]);
        
        echo "Form fields found: " . implode(', ', $formFields) . "\n";
        
        // Get CSRF token
        if (preg_match('/<meta name="csrf-token" content="([^"]*)"/', $createPage['response'], $matches)) {
            $csrfToken = $matches[1];
        } else {
            echo "✗ CSRF token not found\n";
            return;
        }
        
        // Test CREATE operation with complete data
        echo "\nTesting Clientes CREATE...\n";
        
        $clienteData = http_build_query([
            '_token' => $csrfToken,
            'nombre' => 'Form Analysis Test Cliente',
            'email' => 'formtest@example.com',
            'telefono' => '555000001',
            'direccion' => 'Form Analysis Test Address'
        ]);
        
        $createResult = $this->makeRequest('/clientes', $clienteData, 'POST');
        
        echo "CREATE Response HTTP Code: {$createResult['http_code']}\n";
        
        if ($createResult['http_code'] === 302) {
            echo "✓ CREATE successful - redirected to: " . $createResult['redirect_url'] . "\n";
            
            // Verify creation by checking index
            $indexPage = $this->makeRequest('/clientes');
            if (strpos($indexPage['response'], 'Form Analysis Test Cliente') !== false) {
                echo "✓ Record appears in index page\n";
                
                // Extract ID for further testing
                if (preg_match('/\/clientes\/(\d+)/', $indexPage['response'], $matches)) {
                    $clienteId = $matches[1];
                    echo "Created cliente ID: {$clienteId}\n";
                    
                    // Test UPDATE
                    $this->testClienteUpdate($clienteId);
                    
                    // Test DELETE
                    $this->testClienteDelete($clienteId);
                }
            } else {
                echo "✗ Record not found in index page\n";
            }
        } else {
            echo "✗ CREATE failed\n";
            // Show error details
            if (strpos($createResult['response'], 'errors') !== false) {
                echo "Response contains errors - possible validation failure\n";
            }
            
            // Show part of response for debugging
            echo "Response preview:\n";
            echo substr($createResult['response'], 0, 1000) . "\n...\n";
        }
    }
    
    private function testClienteUpdate($clienteId) {
        echo "\nTesting Clientes UPDATE (ID: {$clienteId})...\n";
        
        $editPage = $this->makeRequest("/clientes/{$clienteId}/edit");
        
        if ($editPage['http_code'] !== 200) {
            echo "✗ Cannot access edit form\n";
            return;
        }
        
        // Get CSRF token
        if (preg_match('/<meta name="csrf-token" content="([^"]*)"/', $editPage['response'], $matches)) {
            $csrfToken = $matches[1];
        } else {
            echo "✗ CSRF token not found in edit form\n";
            return;
        }
        
        $updateData = http_build_query([
            '_token' => $csrfToken,
            '_method' => 'PUT',
            'nombre' => 'Form Analysis Test Cliente UPDATED',
            'email' => 'formtest@example.com',
            'telefono' => '555000002',
            'direccion' => 'Updated Address'
        ]);
        
        $updateResult = $this->makeRequest("/clientes/{$clienteId}", $updateData, 'POST');
        
        echo "UPDATE Response HTTP Code: {$updateResult['http_code']}\n";
        
        if ($updateResult['http_code'] === 302) {
            echo "✓ UPDATE successful\n";
            
            // Verify update
            $indexPage = $this->makeRequest('/clientes');
            if (strpos($indexPage['response'], 'Form Analysis Test Cliente UPDATED') !== false) {
                echo "✓ Updated record appears correctly\n";
            } else {
                echo "✗ Updated record not found\n";
            }
        } else {
            echo "✗ UPDATE failed\n";
        }
    }
    
    private function testClienteDelete($clienteId) {
        echo "\nTesting Clientes DELETE (ID: {$clienteId})...\n";
        
        // Get CSRF token from index page
        $indexPage = $this->makeRequest('/clientes');
        if (preg_match('/<meta name="csrf-token" content="([^"]*)"/', $indexPage['response'], $matches)) {
            $csrfToken = $matches[1];
        } else {
            echo "✗ CSRF token not found\n";
            return;
        }
        
        $deleteData = http_build_query([
            '_token' => $csrfToken,
            '_method' => 'DELETE'
        ]);
        
        $deleteResult = $this->makeRequest("/clientes/{$clienteId}", $deleteData, 'POST');
        
        echo "DELETE Response HTTP Code: {$deleteResult['http_code']}\n";
        
        if ($deleteResult['http_code'] === 302) {
            echo "✓ DELETE successful\n";
            
            // Verify deletion
            $indexPage = $this->makeRequest('/clientes');
            if (strpos($indexPage['response'], 'Form Analysis Test Cliente UPDATED') === false) {
                echo "✓ Record successfully removed\n";
            } else {
                echo "✗ Record still appears after deletion\n";
            }
        } else {
            echo "✗ DELETE failed\n";
        }
    }
    
    private function analyzeVehiculosForm() {
        echo "\n=== ANALYZING VEHICULOS FORM ===\n";
        
        // First, ensure we have a client to link to
        $this->createTestCliente();
        
        $createPage = $this->makeRequest('/vehiculos/create');
        
        if ($createPage['http_code'] !== 200) {
            echo "✗ Cannot access vehiculos create form\n";
            return;
        }
        
        echo "✓ Vehiculos create form accessible\n";
        
        // Check for client dropdown
        if (strpos($createPage['response'], 'cliente_id') !== false) {
            echo "✓ Client dropdown found\n";
        } else {
            echo "✗ Client dropdown not found\n";
        }
        
        // Extract available clients
        preg_match_all('/<option value="(\d+)"[^>]*>([^<]+)<\/option>/', $createPage['response'], $clientMatches);
        
        if (count($clientMatches[1]) > 0) {
            $clienteId = $clientMatches[1][0];
            echo "Using client ID: {$clienteId}\n";
            
            // Test CREATE
            if (preg_match('/<meta name="csrf-token" content="([^"]*)"/', $createPage['response'], $matches)) {
                $csrfToken = $matches[1];
                
                $vehiculoData = http_build_query([
                    '_token' => $csrfToken,
                    'cliente_id' => $clienteId,
                    'marca' => 'Form Test Brand',
                    'modelo' => 'Form Test Model',
                    'año' => '2020',
                    'placa' => 'FORM123',
                    'color' => 'Form Test Color'
                ]);
                
                $createResult = $this->makeRequest('/vehiculos', $vehiculoData, 'POST');
                
                echo "CREATE Response HTTP Code: {$createResult['http_code']}\n";
                
                if ($createResult['http_code'] === 302) {
                    echo "✓ Vehiculo CREATE successful\n";
                } else {
                    echo "✗ Vehiculo CREATE failed\n";
                }
            }
        } else {
            echo "✗ No clients available for vehicle creation\n";
        }
    }
    
    private function createTestCliente() {
        $createPage = $this->makeRequest('/clientes/create');
        if (preg_match('/<meta name="csrf-token" content="([^"]*)"/', $createPage['response'], $matches)) {
            $csrfToken = $matches[1];
            
            $clienteData = http_build_query([
                '_token' => $csrfToken,
                'nombre' => 'Test Cliente for Vehicles',
                'email' => 'vehicleclient@example.com',
                'telefono' => '555999000',
                'direccion' => 'Vehicle Test Address'
            ]);
            
            $this->makeRequest('/clientes', $clienteData, 'POST');
        }
    }
    
    private function analyzeAlertBehavior() {
        echo "\n=== ANALYZING ALERT BEHAVIOR ===\n";
        
        // Test validation errors
        $createPage = $this->makeRequest('/clientes/create');
        if (preg_match('/<meta name="csrf-token" content="([^"]*)"/', $createPage['response'], $matches)) {
            $csrfToken = $matches[1];
            
            // Submit invalid data
            $invalidData = http_build_query([
                '_token' => $csrfToken,
                'nombre' => '', // Required field empty
                'email' => 'invalid-email', // Invalid email
                'telefono' => '',
                'direccion' => ''
            ]);
            
            $invalidResult = $this->makeRequest('/clientes', $invalidData, 'POST');
            
            echo "Invalid data submission HTTP Code: {$invalidResult['http_code']}\n";
            
            if ($invalidResult['http_code'] === 302) {
                echo "✓ Proper redirect after validation error\n";
            } elseif ($invalidResult['http_code'] === 422) {
                echo "✓ Validation error returned (422)\n";
            } else {
                echo "Response preview for validation analysis:\n";
                echo substr($invalidResult['response'], 0, 1000) . "\n...\n";
                
                // Check for common validation error indicators
                if (strpos($invalidResult['response'], 'error') !== false) {
                    echo "✓ Error indicators found in response\n";
                } else {
                    echo "✗ No obvious error indicators\n";
                }
            }
        }
    }
    
    public function runDetailedAnalysis() {
        try {
            echo "Starting Detailed Form Analysis\n";
            echo "==============================\n";
            
            $this->authenticate();
            $this->analyzeClientesForm();
            $this->analyzeVehiculosForm();
            $this->analyzeAlertBehavior();
            
            echo "\n=== DETAILED ANALYSIS COMPLETED ===\n";
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
}

// Run the analysis
$analyzer = new DetailedFormAnalyzer();
$analyzer->runDetailedAnalysis();

?>