<?php
/**
 * Comprehensive Immutable Fields and Field Restrictions Test
 * Tests field-level security, immutable fields, and role-based restrictions
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuration
$baseUrl = 'http://localhost:8003';
$cookieFile = __DIR__ . '/immutable_fields_test_cookies.txt';

// Test accounts with different roles
$testAccounts = [
    'admin' => [
        'email' => 'admin@taller.com',
        'password' => 'admin123',
        'role' => 'Administrador'
    ],
    'mecanico' => [
        'email' => 'mecanico@taller.com', 
        'password' => 'mecanico123',
        'role' => 'Mecánico'
    ],
    'recepcionista' => [
        'email' => 'recepcion@taller.com',
        'password' => 'recepcion123',
        'role' => 'Recepcionista'
    ]
];

// Identified immutable fields by business logic
$immutableFields = [
    'clientes' => [
        'id' => 'Primary key should never be modifiable',
        'created_at' => 'Creation timestamp should be immutable',
        'updated_at' => 'System managed timestamp'
    ],
    'vehiculos' => [
        'id' => 'Primary key should never be modifiable',
        'vin' => 'VIN number should be immutable after creation',
        'created_at' => 'Creation timestamp should be immutable'
    ],
    'servicios' => [
        'id' => 'Primary key should never be modifiable',
        'created_at' => 'Creation timestamp should be immutable'
    ],
    'empleados' => [
        'id' => 'Primary key should never be modifiable',
        'hire_date' => 'Hire date should be immutable after creation',
        'created_at' => 'Creation timestamp should be immutable'
    ],
    'ordenes' => [
        'id' => 'Primary key should never be modifiable',
        'created_at' => 'Creation timestamp should be immutable',
        'cliente_id' => 'Client assignment should be immutable after creation',
        'vehiculo_id' => 'Vehicle assignment should be immutable after creation'
    ]
];

// Role-based field restrictions
$roleRestrictions = [
    'Mecánico' => [
        'servicios' => ['price' => 'Mechanics should not modify pricing'],
        'empleados' => ['salary' => 'Mechanics should not modify salaries'],
        'clientes' => ['document_number' => 'Mechanics may not modify critical client data']
    ],
    'Recepcionista' => [
        'empleados' => ['salary' => 'Receptionists should not modify salaries'],
        'servicios' => ['price' => 'Receptionists should not modify pricing']
    ]
];

$testResults = [];

function makeRequest($url, $data = null, $method = 'GET', $cookieFile = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    
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
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['body' => $response, 'code' => $httpCode];
}

function loginUser($baseUrl, $email, $password, $cookieFile) {
    // Get login page and CSRF token
    $loginPage = makeRequest("$baseUrl/login", null, 'GET', $cookieFile);
    
    if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $loginPage['body'], $matches)) {
        $token = $matches[1];
    } else {
        return false;
    }
    
    // Perform login
    $loginData = http_build_query([
        '_token' => $token,
        'email' => $email,
        'password' => $password
    ]);
    
    $loginResponse = makeRequest("$baseUrl/login", $loginData, 'POST', $cookieFile);
    
    // Check if login was successful (redirect or dashboard access)
    return $loginResponse['code'] === 302 || strpos($loginResponse['body'], 'dashboard') !== false;
}

function extractCsrfToken($html) {
    if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $html, $matches) ||
        preg_match('/<meta[^>]*name="csrf-token"[^>]*content="([^"]*)"/', $html, $matches)) {
        return $matches[1];
    }
    return null;
}

function testImmutableFieldModification($baseUrl, $module, $recordId, $field, $newValue, $cookieFile, $role) {
    global $testResults;
    
    $result = [
        'module' => $module,
        'field' => $field,
        'role' => $role,
        'test_type' => 'immutable_field',
        'success' => false,
        'message' => '',
        'details' => []
    ];
    
    // Get edit form
    $editUrl = "$baseUrl/$module/$recordId/edit";
    $editResponse = makeRequest($editUrl, null, 'GET', $cookieFile);
    
    if ($editResponse['code'] !== 200) {
        $result['message'] = "Cannot access edit form for $module ID $recordId";
        $testResults[] = $result;
        return;
    }
    
    // Check if field is present in form
    $fieldPattern = "/name=[\"']" . preg_quote($field, '/') . "[\"']/";
    $fieldInForm = preg_match($fieldPattern, $editResponse['body']);
    
    // Check if field is disabled or readonly
    $disabledPattern = "/name=[\"']" . preg_quote($field, '/') . "[\"'][^>]*(?:disabled|readonly)/";
    $isDisabled = preg_match($disabledPattern, $editResponse['body']);
    
    $result['details']['field_in_form'] = $fieldInForm;
    $result['details']['field_disabled'] = $isDisabled;
    
    if (!$fieldInForm) {
        $result['success'] = true;
        $result['message'] = "Field '$field' is not present in edit form (properly hidden)";
        $testResults[] = $result;
        return;
    }
    
    if ($isDisabled) {
        $result['success'] = true;
        $result['message'] = "Field '$field' is disabled/readonly in form (properly protected)";
        $testResults[] = $result;
        return;
    }
    
    // Extract CSRF token
    $token = extractCsrfToken($editResponse['body']);
    if (!$token) {
        $result['message'] = "Could not extract CSRF token";
        $testResults[] = $result;
        return;
    }
    
    // Extract current field values to preserve other data
    $currentData = [];
    if (preg_match_all('/name="([^"]*)"[^>]*value="([^"]*)"/', $editResponse['body'], $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $currentData[$match[1]] = $match[2];
        }
    }
    
    // Try to modify the immutable field
    $updateData = array_merge($currentData, [
        '_token' => $token,
        '_method' => 'PUT',
        $field => $newValue
    ]);
    
    $updateUrl = "$baseUrl/$module/$recordId";
    $updateResponse = makeRequest($updateUrl, http_build_query($updateData), 'POST', $cookieFile);
    
    $result['details']['update_response_code'] = $updateResponse['code'];
    $result['details']['attempted_value'] = $newValue;
    
    // Check if update was rejected (should be for immutable fields)
    if ($updateResponse['code'] === 422 || strpos($updateResponse['body'], 'error') !== false) {
        $result['success'] = true;
        $result['message'] = "Field '$field' modification was properly rejected";
    } elseif ($updateResponse['code'] === 302) {
        // Check if the field was actually modified
        $showResponse = makeRequest("$baseUrl/$module/$recordId", null, 'GET', $cookieFile);
        if (strpos($showResponse['body'], $newValue) !== false) {
            $result['success'] = false;
            $result['message'] = "SECURITY ISSUE: Immutable field '$field' was modified to '$newValue'";
        } else {
            $result['success'] = true;
            $result['message'] = "Field '$field' modification was silently ignored (acceptable)";
        }
    } else {
        $result['success'] = false;
        $result['message'] = "Unexpected response when modifying immutable field '$field'";
    }
    
    $testResults[] = $result;
}

function testRoleBasedFieldRestriction($baseUrl, $module, $recordId, $field, $newValue, $cookieFile, $role) {
    global $testResults;
    
    $result = [
        'module' => $module,
        'field' => $field,
        'role' => $role,
        'test_type' => 'role_restriction',
        'success' => false,
        'message' => '',
        'details' => []
    ];
    
    // Get edit form
    $editUrl = "$baseUrl/$module/$recordId/edit";
    $editResponse = makeRequest($editUrl, null, 'GET', $cookieFile);
    
    if ($editResponse['code'] !== 200) {
        $result['message'] = "Cannot access edit form for $module ID $recordId with role $role";
        $testResults[] = $result;
        return;
    }
    
    // Check if field is accessible by this role
    $fieldPattern = "/name=[\"']" . preg_quote($field, '/') . "[\"']/";
    $fieldInForm = preg_match($fieldPattern, $editResponse['body']);
    
    // Check if field is disabled for this role
    $disabledPattern = "/name=[\"']" . preg_quote($field, '/') . "[\"'][^>]*(?:disabled|readonly)/";
    $isDisabled = preg_match($disabledPattern, $editResponse['body']);
    
    $result['details']['field_in_form'] = $fieldInForm;
    $result['details']['field_disabled'] = $isDisabled;
    
    if (!$fieldInForm) {
        $result['success'] = true;
        $result['message'] = "Field '$field' properly hidden from role '$role'";
        $testResults[] = $result;
        return;
    }
    
    if ($isDisabled) {
        $result['success'] = true;
        $result['message'] = "Field '$field' properly disabled for role '$role'";
        $testResults[] = $result;
        return;
    }
    
    // If field is accessible, try to modify it
    $token = extractCsrfToken($editResponse['body']);
    if (!$token) {
        $result['message'] = "Could not extract CSRF token";
        $testResults[] = $result;
        return;
    }
    
    // Extract current values
    $currentData = [];
    if (preg_match_all('/name="([^"]*)"[^>]*value="([^"]*)"/', $editResponse['body'], $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $currentData[$match[1]] = $match[2];
        }
    }
    
    // Try to modify the restricted field
    $updateData = array_merge($currentData, [
        '_token' => $token,
        '_method' => 'PUT',
        $field => $newValue
    ]);
    
    $updateUrl = "$baseUrl/$module/$recordId";
    $updateResponse = makeRequest($updateUrl, http_build_query($updateData), 'POST', $cookieFile);
    
    $result['details']['update_response_code'] = $updateResponse['code'];
    
    // Check if modification was properly restricted
    if ($updateResponse['code'] === 403 || $updateResponse['code'] === 422) {
        $result['success'] = true;
        $result['message'] = "Field '$field' modification properly restricted for role '$role'";
    } else {
        $result['success'] = false;
        $result['message'] = "SECURITY ISSUE: Role '$role' can modify restricted field '$field'";
    }
    
    $testResults[] = $result;
}

function testSystemIntegrityFields($baseUrl, $cookieFile, $role) {
    global $testResults;
    
    $integrityTests = [
        [
            'module' => 'vehiculos',
            'field' => 'cliente_id',
            'test' => 'foreign_key_integrity',
            'description' => 'Test if vehicle client_id can be set to non-existent client'
        ],
        [
            'module' => 'ordenes',
            'field' => 'vehiculo_id', 
            'test' => 'foreign_key_integrity',
            'description' => 'Test if order vehiculo_id can be set to non-existent vehicle'
        ]
    ];
    
    foreach ($integrityTests as $test) {
        $result = [
            'module' => $test['module'],
            'field' => $test['field'],
            'role' => $role,
            'test_type' => 'system_integrity',
            'success' => false,
            'message' => '',
            'details' => ['test_description' => $test['description']]
        ];
        
        // Get first record to test with
        $indexResponse = makeRequest("$baseUrl/{$test['module']}", null, 'GET', $cookieFile);
        if ($indexResponse['code'] !== 200) {
            $result['message'] = "Cannot access {$test['module']} index";
            $testResults[] = $result;
            continue;
        }
        
        // Extract first record ID
        if (preg_match('/href="[^"]*\/' . $test['module'] . '\/(\d+)\/edit"/', $indexResponse['body'], $matches)) {
            $recordId = $matches[1];
        } else {
            $result['message'] = "No records found in {$test['module']} to test";
            $testResults[] = $result;
            continue;
        }
        
        // Try to set foreign key to invalid value
        $editUrl = "$baseUrl/{$test['module']}/$recordId/edit";
        $editResponse = makeRequest($editUrl, null, 'GET', $cookieFile);
        
        if ($editResponse['code'] !== 200) {
            $result['message'] = "Cannot access edit form";
            $testResults[] = $result;
            continue;
        }
        
        $token = extractCsrfToken($editResponse['body']);
        if (!$token) {
            $result['message'] = "Could not extract CSRF token";
            $testResults[] = $result;
            continue;
        }
        
        // Extract current values
        $currentData = [];
        if (preg_match_all('/name="([^"]*)"[^>]*value="([^"]*)"/', $editResponse['body'], $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $currentData[$match[1]] = $match[2];
            }
        }
        
        // Try to set invalid foreign key (very high number that shouldn't exist)
        $updateData = array_merge($currentData, [
            '_token' => $token,
            '_method' => 'PUT',
            $test['field'] => '99999'
        ]);
        
        $updateUrl = "$baseUrl/{$test['module']}/$recordId";
        $updateResponse = makeRequest($updateUrl, http_build_query($updateData), 'POST', $cookieFile);
        
        $result['details']['update_response_code'] = $updateResponse['code'];
        $result['details']['attempted_invalid_id'] = '99999';
        
        // Check if foreign key constraint was enforced
        if ($updateResponse['code'] === 422 || strpos($updateResponse['body'], 'error') !== false) {
            $result['success'] = true;
            $result['message'] = "Foreign key integrity properly enforced for {$test['field']}";
        } else {
            $result['success'] = false;
            $result['message'] = "SECURITY ISSUE: Foreign key integrity not enforced for {$test['field']}";
        }
        
        $testResults[] = $result;
    }
}

// Main test execution
echo "Starting Immutable Fields and Field Restrictions Test\n";
echo "=" . str_repeat("=", 60) . "\n\n";

foreach ($testAccounts as $accountType => $account) {
    echo "Testing with role: {$account['role']} ({$account['email']})\n";
    echo "-" . str_repeat("-", 50) . "\n";
    
    // Create separate cookie file for each role
    $roleCookieFile = str_replace('.txt', "_$accountType.txt", $cookieFile);
    
    // Login
    if (!loginUser($baseUrl, $account['email'], $account['password'], $roleCookieFile)) {
        echo "❌ Failed to login as {$account['role']}\n\n";
        continue;
    }
    
    echo "✅ Successfully logged in as {$account['role']}\n";
    
    // Test immutable fields for each module
    echo "\n1. Testing Immutable Fields:\n";
    
    // Get sample records to test with
    $modules = ['clientes', 'vehiculos', 'servicios', 'empleados', 'ordenes'];
    
    foreach ($modules as $module) {
        $indexResponse = makeRequest("$baseUrl/$module", null, 'GET', $roleCookieFile);
        if ($indexResponse['code'] !== 200) {
            echo "   ⚠️ Cannot access $module index with role {$account['role']}\n";
            continue;
        }
        
        // Extract first record ID
        if (preg_match('/href="[^"]*\/' . $module . '\/(\d+)\/edit"/', $indexResponse['body'], $matches)) {
            $recordId = $matches[1];
            
            // Test immutable fields for this module
            if (isset($immutableFields[$module])) {
                foreach ($immutableFields[$module] as $field => $reason) {
                    $testValue = ($field === 'id') ? '99999' : 'MODIFIED_' . time();
                    testImmutableFieldModification($baseUrl, $module, $recordId, $field, $testValue, $roleCookieFile, $account['role']);
                    echo "   • Testing $module.$field immutability\n";
                }
            }
        }
    }
    
    // Test role-based restrictions
    echo "\n2. Testing Role-Based Field Restrictions:\n";
    
    if (isset($roleRestrictions[$account['role']])) {
        foreach ($roleRestrictions[$account['role']] as $module => $fields) {
            $indexResponse = makeRequest("$baseUrl/$module", null, 'GET', $roleCookieFile);
            if ($indexResponse['code'] === 200 && preg_match('/href="[^"]*\/' . $module . '\/(\d+)\/edit"/', $indexResponse['body'], $matches)) {
                $recordId = $matches[1];
                
                foreach ($fields as $field => $reason) {
                    $testValue = 'RESTRICTED_' . time();
                    testRoleBasedFieldRestriction($baseUrl, $module, $recordId, $field, $testValue, $roleCookieFile, $account['role']);
                    echo "   • Testing $module.$field restriction for {$account['role']}\n";
                }
            }
        }
    }
    
    // Test system integrity (only with admin role to avoid permission issues)
    if ($account['role'] === 'Administrador') {
        echo "\n3. Testing System Integrity:\n";
        testSystemIntegrityFields($baseUrl, $roleCookieFile, $account['role']);
        echo "   • Testing foreign key constraints\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
}

// Generate comprehensive report
echo "\nIMMUTABLE FIELDS AND FIELD RESTRICTIONS TEST REPORT\n";
echo str_repeat("=", 70) . "\n\n";

$totalTests = count($testResults);
$passedTests = array_filter($testResults, function($result) { return $result['success']; });
$failedTests = array_filter($testResults, function($result) { return !$result['success']; });

echo "SUMMARY:\n";
echo "Total Tests: $totalTests\n";
echo "Passed: " . count($passedTests) . "\n";
echo "Failed: " . count($failedTests) . "\n";
echo "Success Rate: " . round((count($passedTests) / $totalTests) * 100, 2) . "%\n\n";

if (!empty($failedTests)) {
    echo "SECURITY ISSUES FOUND:\n";
    echo str_repeat("-", 30) . "\n";
    
    foreach ($failedTests as $test) {
        echo "❌ {$test['module']}.{$test['field']} ({$test['role']})\n";
        echo "   Issue: {$test['message']}\n";
        echo "   Type: {$test['test_type']}\n";
        if (!empty($test['details'])) {
            echo "   Details: " . json_encode($test['details']) . "\n";
        }
        echo "\n";
    }
}

echo "\nPASSED TESTS (Proper Security Implementation):\n";
echo str_repeat("-", 50) . "\n";

foreach ($passedTests as $test) {
    echo "✅ {$test['module']}.{$test['field']} ({$test['role']})\n";
    echo "   {$test['message']}\n\n";
}

// Save detailed results to JSON
$reportData = [
    'test_timestamp' => date('Y-m-d H:i:s'),
    'base_url' => $baseUrl,
    'summary' => [
        'total_tests' => $totalTests,
        'passed' => count($passedTests),
        'failed' => count($failedTests),
        'success_rate' => round((count($passedTests) / $totalTests) * 100, 2)
    ],
    'test_results' => $testResults,
    'security_issues' => $failedTests
];

file_put_contents(__DIR__ . '/immutable_fields_test_report.json', json_encode($reportData, JSON_PRETTY_PRINT));

echo "\nDetailed report saved to: immutable_fields_test_report.json\n";
echo "Test completed: " . date('Y-m-d H:i:s') . "\n";
?>