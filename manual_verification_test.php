<?php

/**
 * Manual Verification Test - Browser-like Behavior Analysis
 * Tests actual UI behavior and alert persistence
 */

class ManualVerificationTest {
    private $baseUrl = 'http://localhost:8002';
    private $cookieFile = 'manual_verification_cookies.txt';
    
    public function __construct() {
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
        touch($this->cookieFile);
    }
    
    private function makeRequest($url, $data = null, $method = 'GET') {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true, // Follow redirects like a browser
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        return [
            'response' => $response,
            'http_code' => $httpCode,
            'final_url' => $finalUrl,
            'error' => $error
        ];
    }
    
    private function authenticate() {
        echo "=== AUTHENTICATION ===\n";
        
        $loginPage = $this->makeRequest('/login');
        if (preg_match('/<meta name="csrf-token" content="([^"]*)"/', $loginPage['response'], $matches)) {
            $csrfToken = $matches[1];
        } else {
            throw new Exception('CSRF token not found');
        }
        
        $loginData = http_build_query([
            '_token' => $csrfToken,
            'email' => 'admin@taller.com',
            'password' => 'admin123'
        ]);
        
        $loginResult = $this->makeRequest('/login', $loginData, 'POST');
        
        if ($loginResult['http_code'] === 200 && strpos($loginResult['final_url'], '/dashboard') !== false) {
            echo "✓ Authentication successful\n";
            return true;
        } else {
            throw new Exception('Authentication failed');
        }
    }
    
    private function getCSRFToken($url) {
        $response = $this->makeRequest($url);
        if (preg_match('/<meta name="csrf-token" content="([^"]*)"/', $response['response'], $matches)) {
            return $matches[1];
        }
        throw new Exception('CSRF token not found');
    }
    
    public function testCompleteClienteWorkflow() {
        echo "\n=== COMPLETE CLIENTE WORKFLOW TEST ===\n";
        
        // 1. Test CREATE with alert observation
        echo "Step 1: Creating new cliente...\n";
        $csrfToken = $this->getCSRFToken('/clientes/create');
        
        $clienteData = http_build_query([
            '_token' => $csrfToken,
            'name' => 'Manual Test Cliente',
            'email' => 'manual@test.com',
            'phone' => '(555) 111-2222',
            'document_number' => 'MT123456',
            'address' => 'Manual Test Address',
            'status' => '1'
        ]);
        
        $createResult = $this->makeRequest('/clientes', $clienteData, 'POST');
        echo "Create result - HTTP Code: {$createResult['http_code']}\n";
        echo "Final URL after create: {$createResult['final_url']}\n";
        
        // Check for success alerts in response
        if (strpos($createResult['response'], 'alert-success') !== false) {
            echo "✓ Success alert found in response\n";
        } elseif (strpos($createResult['response'], 'success') !== false) {
            echo "✓ Success message found in response\n";
        } else {
            echo "ℹ No obvious success alert in response\n";
        }
        
        // Check if record appears in index
        if (strpos($createResult['response'], 'Manual Test Cliente') !== false) {
            echo "✓ New record appears in current page\n";
        } else {
            // Maybe redirected to index, check again
            $indexCheck = $this->makeRequest('/clientes');
            if (strpos($indexCheck['response'], 'Manual Test Cliente') !== false) {
                echo "✓ New record appears in index page\n";
                
                // Extract ID for further testing
                if (preg_match('/\/clientes\/(\d+)/', $indexCheck['response'], $matches)) {
                    $clienteId = $matches[1];
                    echo "Cliente ID: {$clienteId}\n";
                    
                    // 2. Test UPDATE
                    echo "\nStep 2: Testing UPDATE...\n";
                    $this->testClienteUpdate($clienteId);
                    
                    // 3. Test DELETE  
                    echo "\nStep 3: Testing DELETE...\n";
                    $this->testClienteDelete($clienteId);
                }
            } else {
                echo "✗ New record NOT found in index page\n";
            }
        }
    }
    
    private function testClienteUpdate($clienteId) {
        $editPage = $this->makeRequest("/clientes/{$clienteId}/edit");
        
        if ($editPage['http_code'] === 200) {
            echo "✓ Edit form accessible\n";
            
            // Check if form is prepopulated
            if (strpos($editPage['response'], 'Manual Test Cliente') !== false) {
                echo "✓ Form is prepopulated with existing data\n";
            } else {
                echo "ℹ Form prepopulation not detected\n";
            }
            
            $updateToken = $this->getCSRFToken("/clientes/{$clienteId}/edit");
            
            $updateData = http_build_query([
                '_token' => $updateToken,
                '_method' => 'PUT',
                'name' => 'Manual Test Cliente UPDATED',
                'email' => 'manual@test.com',
                'phone' => '(555) 333-4444',
                'document_number' => 'MT123456',
                'address' => 'Updated Manual Test Address',
                'status' => '1'
            ]);
            
            $updateResult = $this->makeRequest("/clientes/{$clienteId}", $updateData, 'POST');
            echo "Update result - HTTP Code: {$updateResult['http_code']}\n";
            echo "Final URL after update: {$updateResult['final_url']}\n";
            
            // Check for update success
            if (strpos($updateResult['response'], 'Manual Test Cliente UPDATED') !== false) {
                echo "✓ Updated record visible immediately\n";
            } else {
                $indexCheck = $this->makeRequest('/clientes');
                if (strpos($indexCheck['response'], 'Manual Test Cliente UPDATED') !== false) {
                    echo "✓ Updated record appears in index page\n";
                } else {
                    echo "✗ Updated record not found\n";
                }
            }
        } else {
            echo "✗ Edit form not accessible\n";
        }
    }
    
    private function testClienteDelete($clienteId) {
        $indexPage = $this->makeRequest('/clientes');
        
        // Confirm record exists before deletion
        if (strpos($indexPage['response'], 'Manual Test Cliente UPDATED') !== false) {
            echo "✓ Record exists before deletion\n";
            
            $deleteToken = $this->getCSRFToken('/clientes');
            
            $deleteData = http_build_query([
                '_token' => $deleteToken,
                '_method' => 'DELETE'
            ]);
            
            $deleteResult = $this->makeRequest("/clientes/{$clienteId}", $deleteData, 'POST');
            echo "Delete result - HTTP Code: {$deleteResult['http_code']}\n";
            echo "Final URL after delete: {$deleteResult['final_url']}\n";
            
            // Check if record is removed
            if (strpos($deleteResult['response'], 'Manual Test Cliente UPDATED') === false) {
                echo "✓ Record removed immediately\n";
            } else {
                $indexCheck = $this->makeRequest('/clientes');
                if (strpos($indexCheck['response'], 'Manual Test Cliente UPDATED') === false) {
                    echo "✓ Record removed from index page\n";
                } else {
                    echo "✗ Record still appears after deletion\n";
                }
            }
        } else {
            echo "ℹ Record not found before deletion test\n";
        }
    }
    
    public function testVehiculoWorkflow() {
        echo "\n=== VEHICULO WORKFLOW TEST ===\n";
        
        // First create a client
        echo "Setting up client for vehicle test...\n";
        $csrfToken = $this->getCSRFToken('/clientes/create');
        
        $clienteData = http_build_query([
            '_token' => $csrfToken,
            'name' => 'Vehicle Test Owner',
            'email' => 'vehicleowner@test.com',
            'phone' => '(555) 777-8888',
            'document_number' => 'VTO123456',
            'address' => 'Vehicle Owner Address',
            'status' => '1'
        ]);
        
        $this->makeRequest('/clientes', $clienteData, 'POST');
        
        // Get client ID from dropdown
        $createVehiclePage = $this->makeRequest('/vehiculos/create');
        if (preg_match('/<option value="(\d+)"[^>]*>Vehicle Test Owner/', $createVehiclePage['response'], $matches)) {
            $clienteId = $matches[1];
            echo "✓ Client available in vehicle form dropdown\n";
            
            $vehicleToken = $this->getCSRFToken('/vehiculos/create');
            
            $vehiculoData = http_build_query([
                '_token' => $vehicleToken,
                'cliente_id' => $clienteId,
                'brand' => 'Manual Test Brand',
                'model' => 'Manual Test Model',
                'year' => '2022',
                'license_plate' => 'MAN-123',
                'color' => 'negro',
                'vin' => 'MANUALTEST1234567',
                'status' => '1'
            ]);
            
            $vehicleResult = $this->makeRequest('/vehiculos', $vehiculoData, 'POST');
            echo "Vehicle create result - HTTP Code: {$vehicleResult['http_code']}\n";
            echo "Final URL after vehicle create: {$vehicleResult['final_url']}\n";
            
            // Check if vehicle appears
            if (strpos($vehicleResult['response'], 'MAN-123') !== false) {
                echo "✓ Vehicle record appears immediately\n";
            } else {
                $indexCheck = $this->makeRequest('/vehiculos');
                if (strpos($indexCheck['response'], 'MAN-123') !== false) {
                    echo "✓ Vehicle record appears in index\n";
                } else {
                    echo "✗ Vehicle record not found\n";
                }
            }
        } else {
            echo "✗ Client not available in vehicle dropdown\n";
        }
    }
    
    public function testValidationAndAlerts() {
        echo "\n=== VALIDATION AND ALERT BEHAVIOR TEST ===\n";
        
        // Test form validation
        echo "Testing form validation with invalid data...\n";
        
        $createPage = $this->makeRequest('/clientes/create');
        $csrfToken = $this->getCSRFToken('/clientes/create');
        
        // Submit completely invalid data
        $invalidData = http_build_query([
            '_token' => $csrfToken,
            'name' => '',
            'email' => 'not-an-email',
            'phone' => '',
            'document_number' => '',
            'address' => '',
            'status' => '1'
        ]);
        
        $validationResult = $this->makeRequest('/clientes', $invalidData, 'POST');
        echo "Validation test result - HTTP Code: {$validationResult['http_code']}\n";
        echo "Final URL after validation test: {$validationResult['final_url']}\n";
        
        // Check for validation error indicators
        if (strpos($validationResult['response'], 'is-invalid') !== false) {
            echo "✓ Form validation errors displayed\n";
        } elseif (strpos($validationResult['response'], 'alert-danger') !== false) {
            echo "✓ Error alert displayed\n";
        } elseif (strpos($validationResult['response'], 'error') !== false) {
            echo "✓ Error messages present\n";
        } else {
            echo "ℹ No obvious validation error indicators\n";
        }
        
        // Check if form retains old values
        if (strpos($validationResult['response'], 'not-an-email') !== false) {
            echo "✓ Form retains invalid input for correction\n";
        } else {
            echo "ℹ Form does not retain invalid input\n";
        }
    }
    
    public function runManualVerification() {
        try {
            echo "MANUAL VERIFICATION TEST\n";
            echo "========================\n";
            
            $this->authenticate();
            $this->testCompleteClienteWorkflow();
            $this->testVehiculoWorkflow();
            $this->testValidationAndAlerts();
            
            echo "\n=== MANUAL VERIFICATION COMPLETED ===\n";
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
}

// Run the manual verification
$tester = new ManualVerificationTest();
$tester->runManualVerification();

?>