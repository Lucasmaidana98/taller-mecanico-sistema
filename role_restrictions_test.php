<?php
/**
 * Role-Based Field Restrictions Test
 * Tests specific field access restrictions for different user roles
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$baseUrl = 'http://localhost:8003';
$testResults = [];

function makeRequest($url, $data = null, $method = 'GET', $cookieFile = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
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
    $loginPage = makeRequest("$baseUrl/login", null, 'GET', $cookieFile);
    
    if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $loginPage['body'], $matches)) {
        $token = $matches[1];
    } else {
        return false;
    }
    
    $loginData = http_build_query([
        '_token' => $token,
        'email' => $email,
        'password' => $password
    ]);
    
    $loginResponse = makeRequest("$baseUrl/login", $loginData, 'POST', $cookieFile);
    
    if ($loginResponse['code'] === 302 || $loginResponse['code'] === 200) {
        $dashboardResponse = makeRequest("$baseUrl/dashboard", null, 'GET', $cookieFile);
        return $dashboardResponse['code'] === 200 && strpos($dashboardResponse['body'], 'Dashboard') !== false;
    }
    
    return false;
}

function analyzeFormFields($html, $module) {
    $fields = [];
    
    // Extract all input fields
    if (preg_match_all('/<input[^>]*name="([^"]*)"[^>]*/', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $fieldName = $match[1];
            if (in_array($fieldName, ['_token', '_method'])) continue;
            
            $fieldHtml = $match[0];
            $fields[$fieldName] = [
                'exists' => true,
                'disabled' => preg_match('/\bdisabled\b/', $fieldHtml),
                'readonly' => preg_match('/\breadonly\b/', $fieldHtml),
                'hidden' => preg_match('/type=["\']hidden["\']/', $fieldHtml),
                'type' => 'input'
            ];
        }
    }
    
    // Extract select fields
    if (preg_match_all('/<select[^>]*name="([^"]*)"[^>]*/', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $fieldName = $match[1];
            $fieldHtml = $match[0];
            $fields[$fieldName] = [
                'exists' => true,
                'disabled' => preg_match('/\bdisabled\b/', $fieldHtml),
                'readonly' => false,
                'hidden' => false,
                'type' => 'select'
            ];
        }
    }
    
    // Extract textarea fields
    if (preg_match_all('/<textarea[^>]*name="([^"]*)"[^>]*/', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $fieldName = $match[1];
            $fieldHtml = $match[0];
            $fields[$fieldName] = [
                'exists' => true,
                'disabled' => preg_match('/\bdisabled\b/', $fieldHtml),
                'readonly' => preg_match('/\breadonly\b/', $fieldHtml),
                'hidden' => false,
                'type' => 'textarea'
            ];
        }
    }
    
    return $fields;
}

function testRoleModuleAccess($baseUrl, $role, $email, $password) {
    global $testResults;
    
    $cookieFile = __DIR__ . "/role_test_{$role}_cookies.txt";
    
    echo "Testing role: $role ($email)\n";
    echo str_repeat("-", 40) . "\n";
    
    if (!loginUser($baseUrl, $email, $password, $cookieFile)) {
        echo "❌ Failed to login as $role\n\n";
        return;
    }
    
    echo "✅ Successfully logged in as $role\n";
    
    $modules = [
        'clientes' => 'Clients',
        'vehiculos' => 'Vehicles', 
        'servicios' => 'Services',
        'empleados' => 'Employees',
        'ordenes' => 'Work Orders'
    ];
    
    foreach ($modules as $module => $moduleName) {
        echo "\nTesting $moduleName module:\n";
        
        // Test module access
        $indexResponse = makeRequest("$baseUrl/$module", null, 'GET', $cookieFile);
        
        if ($indexResponse['code'] !== 200) {
            echo "  ❌ Cannot access $moduleName index (HTTP {$indexResponse['code']})\n";
            $testResults[] = [
                'role' => $role,
                'module' => $module,
                'test' => 'module_access',
                'result' => 'blocked',
                'status_code' => $indexResponse['code']
            ];
            continue;
        }
        
        echo "  ✅ Can access $moduleName index\n";
        
        // Check for create button
        $hasCreateButton = strpos($indexResponse['body'], '/create') !== false;
        echo "  " . ($hasCreateButton ? "✅" : "❌") . " Create button " . ($hasCreateButton ? "present" : "absent") . "\n";
        
        // Find first record to test edit access
        if (preg_match('/href="[^"]*\/' . $module . '\/(\d+)\/edit"/', $indexResponse['body'], $matches)) {
            $recordId = $matches[1];
            
            // Test edit access
            $editResponse = makeRequest("$baseUrl/$module/$recordId/edit", null, 'GET', $cookieFile);
            
            if ($editResponse['code'] !== 200) {
                echo "  ❌ Cannot access edit form (HTTP {$editResponse['code']})\n";
                $testResults[] = [
                    'role' => $role,
                    'module' => $module,
                    'test' => 'edit_access',
                    'result' => 'blocked',
                    'status_code' => $editResponse['code']
                ];
                continue;
            }
            
            echo "  ✅ Can access edit form\n";
            
            // Analyze form fields
            $fields = analyzeFormFields($editResponse['body'], $module);
            echo "  📝 Form fields analysis:\n";
            
            foreach ($fields as $fieldName => $fieldInfo) {
                $status = "editable";
                if ($fieldInfo['hidden']) {
                    $status = "hidden";
                } elseif ($fieldInfo['disabled']) {
                    $status = "disabled";
                } elseif ($fieldInfo['readonly']) {
                    $status = "readonly";
                }
                
                $icon = ($status === 'editable') ? "🔓" : "🔒";
                echo "    $icon $fieldName: $status ({$fieldInfo['type']})\n";
                
                $testResults[] = [
                    'role' => $role,
                    'module' => $module,
                    'field' => $fieldName,
                    'test' => 'field_access',
                    'status' => $status,
                    'type' => $fieldInfo['type'],
                    'exists' => $fieldInfo['exists'],
                    'disabled' => $fieldInfo['disabled'],
                    'readonly' => $fieldInfo['readonly'],
                    'hidden' => $fieldInfo['hidden']
                ];
            }
            
        } else {
            echo "  ⚠️ No records found to test edit functionality\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
}

// Test roles
$roles = [
    'Administrador' => ['admin@taller.com', 'admin123'],
    'Mecánico' => ['mecanico@taller.com', 'mecanico123'],
    'Recepcionista' => ['recepcion@taller.com', 'recepcion123']
];

echo "ROLE-BASED FIELD RESTRICTIONS TEST\n";
echo str_repeat("=", 60) . "\n\n";

foreach ($roles as $role => $credentials) {
    testRoleModuleAccess($baseUrl, $role, $credentials[0], $credentials[1]);
}

// Analyze results
echo "FIELD ACCESS ANALYSIS BY ROLE\n";
echo str_repeat("=", 60) . "\n\n";

$fieldsByRole = [];
foreach ($testResults as $result) {
    if ($result['test'] === 'field_access') {
        $fieldsByRole[$result['role']][$result['module']][] = $result;
    }
}

foreach ($fieldsByRole as $role => $modules) {
    echo "$role Field Access:\n";
    echo str_repeat("-", 30) . "\n";
    
    foreach ($modules as $module => $fields) {
        echo "  $module:\n";
        
        $editableFields = array_filter($fields, function($f) { return $f['status'] === 'editable'; });
        $restrictedFields = array_filter($fields, function($f) { return $f['status'] !== 'editable'; });
        
        echo "    Editable fields (" . count($editableFields) . "): ";
        echo implode(', ', array_column($editableFields, 'field')) . "\n";
        
        echo "    Restricted fields (" . count($restrictedFields) . "): ";
        foreach ($restrictedFields as $field) {
            echo "{$field['field']}({$field['status']}) ";
        }
        echo "\n\n";
    }
}

// Critical field analysis
echo "CRITICAL FIELD SECURITY ANALYSIS\n";
echo str_repeat("=", 60) . "\n\n";

$criticalFields = [
    'id' => 'Primary key should never be editable',
    'created_at' => 'Creation timestamp should be immutable',
    'updated_at' => 'Update timestamp should be system managed',
    'password' => 'Password should not be in regular edit forms',
    'remember_token' => 'Authentication tokens should not be editable'
];

foreach ($fieldsByRole as $role => $modules) {
    echo "Critical fields accessible to $role:\n";
    $criticalIssues = [];
    
    foreach ($modules as $module => $fields) {
        foreach ($fields as $field) {
            if (isset($criticalFields[$field['field']]) && $field['status'] === 'editable') {
                $criticalIssues[] = [
                    'module' => $module,
                    'field' => $field['field'],
                    'reason' => $criticalFields[$field['field']]
                ];
            }
        }
    }
    
    if (empty($criticalIssues)) {
        echo "  ✅ No critical security issues found\n";
    } else {
        echo "  ❌ SECURITY ISSUES FOUND:\n";
        foreach ($criticalIssues as $issue) {
            echo "    - {$issue['module']}.{$issue['field']}: {$issue['reason']}\n";
        }
    }
    echo "\n";
}

// Save results
file_put_contents(__DIR__ . '/role_restrictions_test_results.json', json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'test_summary' => [
        'total_tests' => count($testResults),
        'roles_tested' => array_keys($roles),
        'modules_tested' => ['clientes', 'vehiculos', 'servicios', 'empleados', 'ordenes']
    ],
    'detailed_results' => $testResults,
    'field_access_by_role' => $fieldsByRole
], JSON_PRETTY_PRINT));

echo "Detailed results saved to: role_restrictions_test_results.json\n";
echo "Test completed: " . date('Y-m-d H:i:s') . "\n";
?>