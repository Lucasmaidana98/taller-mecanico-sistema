<?php

/**
 * COMPREHENSIVE FINAL VERIFICATION TEST
 * Testing all fixes applied to the Laravel application
 * Date: 2025-07-02
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseUrl = 'http://localhost:8003';
$cookieFile = '/tmp/comprehensive_final_verification_cookies.txt';

// Test users with different roles
$testUsers = [
    'admin' => ['email' => 'admin@taller.com', 'password' => 'admin123'],
    'mecanico' => ['email' => 'mecanico@taller.com', 'password' => 'mecanico123'],
    'recepcion' => ['email' => 'recepcion@taller.com', 'password' => 'recepcion123']
];

$testResults = [
    'crud_operations' => [],
    'validation_improvements' => [],
    'permission_system' => [],
    'form_submission' => [],
    'route_protection' => [],
    'error_handling' => [],
    'summary' => []
];

function makeCurlRequest($url, $method = 'GET', $data = null, $cookieFile = null, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['response' => $response, 'http_code' => $httpCode];
}

function loginUser($email, $password, $cookieFile) {
    global $baseUrl;
    
    // Get login page with CSRF token
    $loginPage = makeCurlRequest("$baseUrl/login", 'GET', null, $cookieFile);
    if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $loginPage['response'], $matches)) {
        $csrfToken = $matches[1];
    } else {
        return false;
    }
    
    // Attempt login
    $loginData = http_build_query([
        '_token' => $csrfToken,
        'email' => $email,
        'password' => $password
    ]);
    
    $loginResponse = makeCurlRequest("$baseUrl/login", 'POST', $loginData, $cookieFile, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    return $loginResponse['http_code'] === 302; // Redirect indicates successful login
}

function extractCsrfToken($html) {
    if (preg_match('/<meta name="csrf-token" content="([^"]*)"/', $html, $matches)) {
        return $matches[1];
    }
    if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $html, $matches)) {
        return $matches[1];
    }
    return null;
}

// ============================================================================
// 1. CRUD OPERATIONS VERIFICATION
// ============================================================================

echo "========================================\n";
echo "1. CRUD OPERATIONS VERIFICATION\n";
echo "========================================\n\n";

// Test with admin user first
$adminCookieFile = '/tmp/admin_crud_cookies.txt';
if (file_exists($adminCookieFile)) unlink($adminCookieFile);

if (loginUser($testUsers['admin']['email'], $testUsers['admin']['password'], $adminCookieFile)) {
    echo "✅ Admin login successful\n";
    
    // Test Clientes UPDATE operations
    echo "\n--- Testing Clientes UPDATE operations ---\n";
    
    // Get clientes list to find a record to update
    $clientesResponse = makeCurlRequest("$baseUrl/clientes", 'GET', null, $adminCookieFile);
    if ($clientesResponse['http_code'] === 200) {
        echo "✅ Clientes index accessible\n";
        
        // Try to find a cliente ID from the response
        if (preg_match('/\/clientes\/(\d+)\/edit/', $clientesResponse['response'], $matches)) {
            $clienteId = $matches[1];
            
            // Get edit form
            $editResponse = makeCurlRequest("$baseUrl/clientes/$clienteId/edit", 'GET', null, $adminCookieFile);
            if ($editResponse['http_code'] === 200) {
                echo "✅ Cliente edit form accessible\n";
                
                $csrfToken = extractCsrfToken($editResponse['response']);
                if ($csrfToken) {
                    // Test UPDATE with valid data
                    $updateData = http_build_query([
                        '_token' => $csrfToken,
                        '_method' => 'PUT',
                        'nombre' => 'Cliente Actualizado Test',
                        'apellido' => 'Apellido Test',
                        'telefono' => '1234567890',
                        'email' => 'cliente.test@email.com',
                        'direccion' => 'Direccion Test 123'
                    ]);
                    
                    $updateResponse = makeCurlRequest("$baseUrl/clientes/$clienteId", 'POST', $updateData, $adminCookieFile, [
                        'Content-Type: application/x-www-form-urlencoded'
                    ]);
                    
                    if ($updateResponse['http_code'] === 302) {
                        echo "✅ Cliente UPDATE operation successful\n";
                        $testResults['crud_operations']['clientes_update'] = 'PASS';
                    } else {
                        echo "❌ Cliente UPDATE failed - HTTP {$updateResponse['http_code']}\n";
                        $testResults['crud_operations']['clientes_update'] = 'FAIL';
                    }
                } else {
                    echo "❌ Could not extract CSRF token from edit form\n";
                    $testResults['crud_operations']['clientes_update'] = 'FAIL';
                }
            } else {
                echo "❌ Cliente edit form not accessible\n";
                $testResults['crud_operations']['clientes_update'] = 'FAIL';
            }
        } else {
            echo "❌ No cliente found to test update\n";
            $testResults['crud_operations']['clientes_update'] = 'SKIP';
        }
    } else {
        echo "❌ Clientes index not accessible\n";
        $testResults['crud_operations']['clientes_update'] = 'FAIL';
    }
    
    // Test Clientes DELETE operations
    echo "\n--- Testing Clientes DELETE operations ---\n";
    
    // First, create a test cliente to delete
    $clientesCreateResponse = makeCurlRequest("$baseUrl/clientes/create", 'GET', null, $adminCookieFile);
    if ($clientesCreateResponse['http_code'] === 200) {
        $csrfToken = extractCsrfToken($clientesCreateResponse['response']);
        if ($csrfToken) {
            // Create a test cliente
            $createData = http_build_query([
                '_token' => $csrfToken,
                'nombre' => 'Cliente Para Eliminar',
                'apellido' => 'Test Delete',
                'telefono' => '9999999999',
                'email' => 'delete.test@email.com',
                'direccion' => 'Direccion Delete Test 123'
            ]);
            
            $createResponse = makeCurlRequest("$baseUrl/clientes", 'POST', $createData, $adminCookieFile, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);
            
            if ($createResponse['http_code'] === 302) {
                echo "✅ Test cliente created for deletion\n";
                
                // Get the new cliente's ID from the redirect location or by parsing the list again
                $clientesListResponse = makeCurlRequest("$baseUrl/clientes", 'GET', null, $adminCookieFile);
                if (preg_match_all('/\/clientes\/(\d+)\/edit/', $clientesListResponse['response'], $allMatches)) {
                    // Use the last (newest) cliente ID
                    $newClienteId = end($allMatches[1]);
                    
                    // Get CSRF token for delete
                    $deleteToken = extractCsrfToken($clientesListResponse['response']);
                    if ($deleteToken) {
                        // Test DELETE operation
                        $deleteData = http_build_query([
                            '_token' => $deleteToken,
                            '_method' => 'DELETE'
                        ]);
                        
                        $deleteResponse = makeCurlRequest("$baseUrl/clientes/$newClienteId", 'POST', $deleteData, $adminCookieFile, [
                            'Content-Type: application/x-www-form-urlencoded'
                        ]);
                        
                        if ($deleteResponse['http_code'] === 302) {
                            echo "✅ Cliente DELETE operation successful\n";
                            $testResults['crud_operations']['clientes_delete'] = 'PASS';
                        } else {
                            echo "❌ Cliente DELETE failed - HTTP {$deleteResponse['http_code']}\n";
                            $testResults['crud_operations']['clientes_delete'] = 'FAIL';
                        }
                    } else {
                        echo "❌ Could not extract CSRF token for delete\n";
                        $testResults['crud_operations']['clientes_delete'] = 'FAIL';
                    }
                } else {
                    echo "❌ Could not find created cliente for deletion\n";
                    $testResults['crud_operations']['clientes_delete'] = 'FAIL';
                }
            } else {
                echo "❌ Could not create test cliente for deletion\n";
                $testResults['crud_operations']['clientes_delete'] = 'SKIP';
            }
        }
    }
    
    // Test Vehiculos UPDATE operations (VIN validation)
    echo "\n--- Testing Vehiculos UPDATE operations ---\n";
    
    $vehiculosResponse = makeCurlRequest("$baseUrl/vehiculos", 'GET', null, $adminCookieFile);
    if ($vehiculosResponse['http_code'] === 200) {
        echo "✅ Vehiculos index accessible\n";
        
        if (preg_match('/\/vehiculos\/(\d+)\/edit/', $vehiculosResponse['response'], $matches)) {
            $vehiculoId = $matches[1];
            
            $editResponse = makeCurlRequest("$baseUrl/vehiculos/$vehiculoId/edit", 'GET', null, $adminCookieFile);
            if ($editResponse['http_code'] === 200) {
                $csrfToken = extractCsrfToken($editResponse['response']);
                if ($csrfToken) {
                    // Test UPDATE with valid VIN (17 characters)
                    $updateData = http_build_query([
                        '_token' => $csrfToken,
                        '_method' => 'PUT',
                        'cliente_id' => '1',
                        'marca' => 'Toyota Test',
                        'modelo' => 'Corolla Test',
                        'año' => '2020',
                        'vin' => '1HGBH41JXMN109186', // Valid 17-character VIN
                        'placa' => 'TEST123'
                    ]);
                    
                    $updateResponse = makeCurlRequest("$baseUrl/vehiculos/$vehiculoId", 'POST', $updateData, $adminCookieFile, [
                        'Content-Type: application/x-www-form-urlencoded'
                    ]);
                    
                    if ($updateResponse['http_code'] === 302) {
                        echo "✅ Vehiculo UPDATE operation successful (VIN validation works)\n";
                        $testResults['crud_operations']['vehiculos_update'] = 'PASS';
                    } else {
                        echo "❌ Vehiculo UPDATE failed - HTTP {$updateResponse['http_code']}\n";
                        $testResults['crud_operations']['vehiculos_update'] = 'FAIL';
                    }
                }
            }
        } else {
            echo "❌ No vehiculo found to test update\n";
            $testResults['crud_operations']['vehiculos_update'] = 'SKIP';
        }
    }
    
    // Test Empleados UPDATE operations
    echo "\n--- Testing Empleados UPDATE operations ---\n";
    
    $empleadosResponse = makeCurlRequest("$baseUrl/empleados", 'GET', null, $adminCookieFile);
    if ($empleadosResponse['http_code'] === 200) {
        echo "✅ Empleados index accessible\n";
        
        if (preg_match('/\/empleados\/(\d+)\/edit/', $empleadosResponse['response'], $matches)) {
            $empleadoId = $matches[1];
            
            $editResponse = makeCurlRequest("$baseUrl/empleados/$empleadoId/edit", 'GET', null, $adminCookieFile);
            if ($editResponse['http_code'] === 200) {
                $csrfToken = extractCsrfToken($editResponse['response']);
                if ($csrfToken) {
                    // Test UPDATE with valid salary and date
                    $updateData = http_build_query([
                        '_token' => $csrfToken,
                        '_method' => 'PUT',
                        'nombre' => 'Empleado Test Updated',
                        'apellido' => 'Apellido Test',
                        'email' => 'empleado.test@email.com',
                        'telefono' => '1234567890',
                        'cargo' => 'Mecánico',
                        'salario' => '50000.00', // Valid salary > 1
                        'fecha_contratacion' => '2023-01-01' // Valid past date
                    ]);
                    
                    $updateResponse = makeCurlRequest("$baseUrl/empleados/$empleadoId", 'POST', $updateData, $adminCookieFile, [
                        'Content-Type: application/x-www-form-urlencoded'
                    ]);
                    
                    if ($updateResponse['http_code'] === 302) {
                        echo "✅ Empleado UPDATE operation successful (salary and date validation works)\n";
                        $testResults['crud_operations']['empleados_update'] = 'PASS';
                    } else {
                        echo "❌ Empleado UPDATE failed - HTTP {$updateResponse['http_code']}\n";
                        $testResults['crud_operations']['empleados_update'] = 'FAIL';
                    }
                }
            }
        } else {
            echo "❌ No empleado found to test update\n";
            $testResults['crud_operations']['empleados_update'] = 'SKIP';
        }
    }
    
    // Test Servicios UPDATE operations
    echo "\n--- Testing Servicios UPDATE operations ---\n";
    
    $serviciosResponse = makeCurlRequest("$baseUrl/servicios", 'GET', null, $adminCookieFile);
    if ($serviciosResponse['http_code'] === 200) {
        echo "✅ Servicios index accessible\n";
        
        if (preg_match('/\/servicios\/(\d+)\/edit/', $serviciosResponse['response'], $matches)) {
            $servicioId = $matches[1];
            
            $editResponse = makeCurlRequest("$baseUrl/servicios/$servicioId/edit", 'GET', null, $adminCookieFile);
            if ($editResponse['http_code'] === 200) {
                $csrfToken = extractCsrfToken($editResponse['response']);
                if ($csrfToken) {
                    // Test UPDATE with valid price
                    $updateData = http_build_query([
                        '_token' => $csrfToken,
                        '_method' => 'PUT',
                        'nombre' => 'Servicio Test Updated',
                        'descripcion' => 'Descripcion del servicio actualizada',
                        'precio' => '150.50', // Valid price between 0.01 and 999999.99
                        'duracion_estimada' => '2.5' // Valid duration between 0.25 and 24
                    ]);
                    
                    $updateResponse = makeCurlRequest("$baseUrl/servicios/$servicioId", 'POST', $updateData, $adminCookieFile, [
                        'Content-Type: application/x-www-form-urlencoded'
                    ]);
                    
                    if ($updateResponse['http_code'] === 302) {
                        echo "✅ Servicio UPDATE operation successful (price validation works)\n";
                        $testResults['crud_operations']['servicios_update'] = 'PASS';
                    } else {
                        echo "❌ Servicio UPDATE failed - HTTP {$updateResponse['http_code']}\n";
                        $testResults['crud_operations']['servicios_update'] = 'FAIL';
                    }
                }
            }
        } else {
            echo "❌ No servicio found to test update\n";
            $testResults['crud_operations']['servicios_update'] = 'SKIP';
        }
    }
    
} else {
    echo "❌ Admin login failed\n";
    $testResults['crud_operations']['login'] = 'FAIL';
}

// ============================================================================
// 2. VALIDATION IMPROVEMENTS VERIFICATION
// ============================================================================

echo "\n\n========================================\n";
echo "2. VALIDATION IMPROVEMENTS VERIFICATION\n";
echo "========================================\n\n";

// Test improved field length restrictions
echo "--- Testing Field Length Restrictions ---\n";

// Test phone max 20 characters
$clientesCreateResponse = makeCurlRequest("$baseUrl/clientes/create", 'GET', null, $adminCookieFile);
if ($clientesCreateResponse['http_code'] === 200) {
    $csrfToken = extractCsrfToken($clientesCreateResponse['response']);
    if ($csrfToken) {
        // Test with phone > 20 characters (should fail)
        $invalidPhoneData = http_build_query([
            '_token' => $csrfToken,
            'nombre' => 'Test',
            'apellido' => 'User',
            'telefono' => '123456789012345678901', // 21 characters - should fail
            'email' => 'test@test.com',
            'direccion' => 'Test Address'
        ]);
        
        $invalidPhoneResponse = makeCurlRequest("$baseUrl/clientes", 'POST', $invalidPhoneData, $adminCookieFile, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        if ($invalidPhoneResponse['http_code'] === 422 || strpos($invalidPhoneResponse['response'], 'error') !== false) {
            echo "✅ Phone length validation (max 20) working correctly\n";
            $testResults['validation_improvements']['phone_length'] = 'PASS';
        } else {
            echo "❌ Phone length validation not working\n";
            $testResults['validation_improvements']['phone_length'] = 'FAIL';
        }
    }
}

// Test VIN validation (exactly 17 characters)
$vehiculosCreateResponse = makeCurlRequest("$baseUrl/vehiculos/create", 'GET', null, $adminCookieFile);
if ($vehiculosCreateResponse['http_code'] === 200) {
    $csrfToken = extractCsrfToken($vehiculosCreateResponse['response']);
    if ($csrfToken) {
        // Test with VIN != 17 characters (should fail)
        $invalidVinData = http_build_query([
            '_token' => $csrfToken,
            'cliente_id' => '1',
            'marca' => 'Test',
            'modelo' => 'Test',
            'año' => '2020',
            'vin' => '12345', // Only 5 characters - should fail
            'placa' => 'TEST123'
        ]);
        
        $invalidVinResponse = makeCurlRequest("$baseUrl/vehiculos", 'POST', $invalidVinData, $adminCookieFile, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        if ($invalidVinResponse['http_code'] === 422 || strpos($invalidVinResponse['response'], 'error') !== false) {
            echo "✅ VIN validation (exactly 17 characters) working correctly\n";
            $testResults['validation_improvements']['vin_validation'] = 'PASS';
        } else {
            echo "❌ VIN validation not working\n";
            $testResults['validation_improvements']['vin_validation'] = 'FAIL';
        }
    }
}

// Test salary minimum validation
$empleadosCreateResponse = makeCurlRequest("$baseUrl/empleados/create", 'GET', null, $adminCookieFile);
if ($empleadosCreateResponse['http_code'] === 200) {
    $csrfToken = extractCsrfToken($empleadosCreateResponse['response']);
    if ($csrfToken) {
        // Test with salary < 1 (should fail)
        $invalidSalaryData = http_build_query([
            '_token' => $csrfToken,
            'nombre' => 'Test',
            'apellido' => 'Employee',
            'email' => 'test.employee@test.com',
            'telefono' => '1234567890',
            'cargo' => 'Test',
            'salario' => '0.50', // Less than 1 - should fail
            'fecha_contratacion' => '2023-01-01'
        ]);
        
        $invalidSalaryResponse = makeCurlRequest("$baseUrl/empleados", 'POST', $invalidSalaryData, $adminCookieFile, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        if ($invalidSalaryResponse['http_code'] === 422 || strpos($invalidSalaryResponse['response'], 'error') !== false) {
            echo "✅ Salary minimum validation (min 1) working correctly\n";
            $testResults['validation_improvements']['salary_min'] = 'PASS';
        } else {
            echo "❌ Salary minimum validation not working\n";
            $testResults['validation_improvements']['salary_min'] = 'FAIL';
        }
    }
}

// Test price validation for services
$serviciosCreateResponse = makeCurlRequest("$baseUrl/servicios/create", 'GET', null, $adminCookieFile);
if ($serviciosCreateResponse['http_code'] === 200) {
    $csrfToken = extractCsrfToken($serviciosCreateResponse['response']);
    if ($csrfToken) {
        // Test with price < 0.01 (should fail)
        $invalidPriceData = http_build_query([
            '_token' => $csrfToken,
            'nombre' => 'Test Service',
            'descripcion' => 'Test Description',
            'precio' => '0.005', // Less than 0.01 - should fail
            'duracion_estimada' => '1.0'
        ]);
        
        $invalidPriceResponse = makeCurlRequest("$baseUrl/servicios", 'POST', $invalidPriceData, $adminCookieFile, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        if ($invalidPriceResponse['http_code'] === 422 || strpos($invalidPriceResponse['response'], 'error') !== false) {
            echo "✅ Price validation (min 0.01) working correctly\n";
            $testResults['validation_improvements']['price_validation'] = 'PASS';
        } else {
            echo "❌ Price validation not working\n";
            $testResults['validation_improvements']['price_validation'] = 'FAIL';
        }
    }
}

// ============================================================================
// 3. PERMISSION SYSTEM VERIFICATION
// ============================================================================

echo "\n\n========================================\n";
echo "3. PERMISSION SYSTEM VERIFICATION\n";
echo "========================================\n\n";

// Test with different user roles
foreach (['mecanico', 'recepcion'] as $role) {
    echo "--- Testing with $role user ---\n";
    
    $roleCookieFile = "/tmp/{$role}_permission_cookies.txt";
    if (file_exists($roleCookieFile)) unlink($roleCookieFile);
    
    if (loginUser($testUsers[$role]['email'], $testUsers[$role]['password'], $roleCookieFile)) {
        echo "✅ {$role} login successful\n";
        
        // Test access to empleados (should be denied for non-admin)
        $empleadosResponse = makeCurlRequest("$baseUrl/empleados", 'GET', null, $roleCookieFile);
        
        if ($empleadosResponse['http_code'] === 403 || strpos($empleadosResponse['response'], 'Unauthorized') !== false) {
            echo "✅ {$role} correctly denied access to empleados\n";
            $testResults['permission_system']["{$role}_empleados_access"] = 'PASS';
        } else {
            echo "❌ {$role} should not have access to empleados but does\n";
            $testResults['permission_system']["{$role}_empleados_access"] = 'FAIL';
        }
        
        // Test access to clientes (should be allowed)
        $clientesResponse = makeCurlRequest("$baseUrl/clientes", 'GET', null, $roleCookieFile);
        
        if ($clientesResponse['http_code'] === 200) {
            echo "✅ {$role} correctly has access to clientes\n";
            $testResults['permission_system']["{$role}_clientes_access"] = 'PASS';
        } else {
            echo "❌ {$role} should have access to clientes but doesn't\n";
            $testResults['permission_system']["{$role}_clientes_access"] = 'FAIL';
        }
        
    } else {
        echo "❌ {$role} login failed\n";
        $testResults['permission_system']["{$role}_login"] = 'FAIL';
    }
}

// ============================================================================
// 4. FORM SUBMISSION VERIFICATION
// ============================================================================

echo "\n\n========================================\n";
echo "4. FORM SUBMISSION VERIFICATION\n";
echo "========================================\n\n";

// Test AJAX vs traditional form submission
echo "--- Testing Form Submission Methods ---\n";

// Test traditional form submission
$clientesCreateResponse = makeCurlRequest("$baseUrl/clientes/create", 'GET', null, $adminCookieFile);
if ($clientesCreateResponse['http_code'] === 200) {
    $csrfToken = extractCsrfToken($clientesCreateResponse['response']);
    if ($csrfToken) {
        $formData = http_build_query([
            '_token' => $csrfToken,
            'nombre' => 'Form Test',
            'apellido' => 'Traditional',
            'telefono' => '1234567890',
            'email' => 'form.test@email.com',
            'direccion' => 'Form Test Address'
        ]);
        
        $formResponse = makeCurlRequest("$baseUrl/clientes", 'POST', $formData, $adminCookieFile, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        if ($formResponse['http_code'] === 302) {
            echo "✅ Traditional form submission working\n";
            $testResults['form_submission']['traditional'] = 'PASS';
        } else {
            echo "❌ Traditional form submission failed\n";
            $testResults['form_submission']['traditional'] = 'FAIL';
        }
    }
}

// Test AJAX form submission (by checking for AJAX-friendly responses)
$ajaxHeaders = [
    'Content-Type: application/x-www-form-urlencoded',
    'X-Requested-With: XMLHttpRequest'
];

if ($csrfToken) {
    $ajaxData = http_build_query([
        '_token' => $csrfToken,
        'nombre' => 'AJAX Test',
        'apellido' => 'AJAX',
        'telefono' => '0987654321',
        'email' => 'ajax.test@email.com',
        'direccion' => 'AJAX Test Address'
    ]);
    
    $ajaxResponse = makeCurlRequest("$baseUrl/clientes", 'POST', $ajaxData, $adminCookieFile, $ajaxHeaders);
    
    // AJAX requests might return different status codes or JSON responses
    if ($ajaxResponse['http_code'] === 200 || $ajaxResponse['http_code'] === 302) {
        echo "✅ AJAX form submission working\n";
        $testResults['form_submission']['ajax'] = 'PASS';
    } else {
        echo "❌ AJAX form submission failed\n";
        $testResults['form_submission']['ajax'] = 'FAIL';
    }
}

// ============================================================================
// 5. ROUTE PROTECTION VERIFICATION
// ============================================================================

echo "\n\n========================================\n";
echo "5. ROUTE PROTECTION VERIFICATION\n";
echo "========================================\n\n";

// Test unauthenticated access
echo "--- Testing Unauthenticated Access ---\n";

$unauthCookieFile = '/tmp/unauth_cookies.txt';
if (file_exists($unauthCookieFile)) unlink($unauthCookieFile);

$protectedRoutes = [
    '/dashboard',
    '/clientes',
    '/vehiculos',
    '/servicios',
    '/empleados',
    '/ordenes',
    '/reportes'
];

foreach ($protectedRoutes as $route) {
    $response = makeCurlRequest("$baseUrl$route", 'GET', null, $unauthCookieFile);
    
    if ($response['http_code'] === 302 && strpos($response['response'], 'login') !== false) {
        echo "✅ $route correctly redirects unauthenticated users to login\n";
        $testResults['route_protection'][$route] = 'PASS';
    } else {
        echo "❌ $route should redirect to login but doesn't\n";
        $testResults['route_protection'][$route] = 'FAIL';
    }
}

// ============================================================================
// 6. ERROR HANDLING VERIFICATION
// ============================================================================

echo "\n\n========================================\n";
echo "6. ERROR HANDLING VERIFICATION\n";
echo "========================================\n\n";

// Test validation error display
echo "--- Testing Validation Error Display ---\n";

$clientesCreateResponse = makeCurlRequest("$baseUrl/clientes/create", 'GET', null, $adminCookieFile);
if ($clientesCreateResponse['http_code'] === 200) {
    $csrfToken = extractCsrfToken($clientesCreateResponse['response']);
    if ($csrfToken) {
        // Submit form with missing required fields
        $incompleteData = http_build_query([
            '_token' => $csrfToken,
            'nombre' => '', // Missing required field
            'apellido' => '',
            'telefono' => '',
            'email' => 'invalid-email', // Invalid format
            'direccion' => ''
        ]);
        
        $errorResponse = makeCurlRequest("$baseUrl/clientes", 'POST', $incompleteData, $adminCookieFile, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        if ($errorResponse['http_code'] === 422 || strpos($errorResponse['response'], 'error') !== false) {
            echo "✅ Validation errors properly displayed\n";
            $testResults['error_handling']['validation_display'] = 'PASS';
        } else {
            echo "❌ Validation errors not properly displayed\n";
            $testResults['error_handling']['validation_display'] = 'FAIL';
        }
    }
}

// Test 404 error handling for non-existent resources
$notFoundResponse = makeCurlRequest("$baseUrl/clientes/99999", 'GET', null, $adminCookieFile);
if ($notFoundResponse['http_code'] === 404) {
    echo "✅ 404 errors properly handled\n";
    $testResults['error_handling']['404_handling'] = 'PASS';
} else {
    echo "❌ 404 errors not properly handled\n";
    $testResults['error_handling']['404_handling'] = 'FAIL';
}

// ============================================================================
// GENERATE COMPREHENSIVE REPORT
// ============================================================================

echo "\n\n========================================\n";
echo "COMPREHENSIVE TEST RESULTS SUMMARY\n";
echo "========================================\n\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$skippedTests = 0;

foreach ($testResults as $category => $tests) {
    if ($category === 'summary') continue;
    
    echo "--- " . strtoupper(str_replace('_', ' ', $category)) . " ---\n";
    
    foreach ($tests as $test => $result) {
        $totalTests++;
        $status = match($result) {
            'PASS' => '✅',
            'FAIL' => '❌',
            'SKIP' => '⏸️',
            default => '❓'
        };
        
        echo "$status $test: $result\n";
        
        switch ($result) {
            case 'PASS':
                $passedTests++;
                break;
            case 'FAIL':
                $failedTests++;
                break;
            case 'SKIP':
                $skippedTests++;
                break;
        }
    }
    echo "\n";
}

$testResults['summary'] = [
    'total_tests' => $totalTests,
    'passed' => $passedTests,
    'failed' => $failedTests,
    'skipped' => $skippedTests,
    'success_rate' => $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0
];

echo "OVERALL RESULTS:\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: $failedTests\n";
echo "Skipped: $skippedTests\n";
echo "Success Rate: " . $testResults['summary']['success_rate'] . "%\n\n";

// Clean up cookie files
foreach (glob('/tmp/*_cookies.txt') as $cookieFile) {
    unlink($cookieFile);
}

// Save detailed results to JSON file
file_put_contents('/mnt/c/Users/lukka/taller-sistema/comprehensive_final_verification_report.json', json_encode($testResults, JSON_PRETTY_PRINT));

echo "Detailed test report saved to: comprehensive_final_verification_report.json\n";

?>