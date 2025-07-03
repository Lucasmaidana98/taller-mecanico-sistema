<?php
/**
 * Focused Immutable Fields Test - Quick Assessment
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$baseUrl = 'http://localhost:8003';
$cookieFile = __DIR__ . '/focused_immutable_cookies.txt';

$testResults = [];
$securityIssues = [];

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

function loginAsAdmin($baseUrl, $cookieFile) {
    $loginPage = makeRequest("$baseUrl/login", null, 'GET', $cookieFile);
    
    if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $loginPage['body'], $matches)) {
        $token = $matches[1];
    } else {
        return false;
    }
    
    $loginData = http_build_query([
        '_token' => $token,
        'email' => 'admin@taller.com',
        'password' => 'admin123'
    ]);
    
    $loginResponse = makeRequest("$baseUrl/login", $loginData, 'POST', $cookieFile);
    
    // Check if login successful by trying to access dashboard
    if ($loginResponse['code'] === 302 || $loginResponse['code'] === 200) {
        $dashboardResponse = makeRequest("$baseUrl/dashboard", null, 'GET', $cookieFile);
        return $dashboardResponse['code'] === 200 && strpos($dashboardResponse['body'], 'Dashboard') !== false;
    }
    
    return false;
}

function testFieldModification($baseUrl, $module, $field, $newValue, $cookieFile) {
    global $testResults, $securityIssues;
    
    // Get first record
    $indexResponse = makeRequest("$baseUrl/$module", null, 'GET', $cookieFile);
    if ($indexResponse['code'] !== 200) {
        return;
    }
    
    if (!preg_match('/href="[^"]*\/' . $module . '\/(\d+)\/edit"/', $indexResponse['body'], $matches)) {
        return;
    }
    
    $recordId = $matches[1];
    
    // Get edit form
    $editResponse = makeRequest("$baseUrl/$module/$recordId/edit", null, 'GET', $cookieFile);
    if ($editResponse['code'] !== 200) {
        return;
    }
    
    // Check if field exists and is editable
    $fieldPattern = "/name=[\"']" . preg_quote($field, '/') . "[\"']/";
    $fieldExists = preg_match($fieldPattern, $editResponse['body']);
    
    $disabledPattern = "/name=[\"']" . preg_quote($field, '/') . "[\"'][^>]*(?:disabled|readonly)/";
    $isDisabled = preg_match($disabledPattern, $editResponse['body']);
    
    $result = [
        'module' => $module,
        'field' => $field,
        'field_exists' => $fieldExists,
        'field_disabled' => $isDisabled,
        'status' => 'unknown'
    ];
    
    if (!$fieldExists) {
        $result['status'] = 'properly_hidden';
        $result['message'] = "Field '$field' not present in form (good)";
    } elseif ($isDisabled) {
        $result['status'] = 'properly_disabled';
        $result['message'] = "Field '$field' is disabled (good)";
    } else {
        // Field is editable - potential security issue for immutable fields
        $result['status'] = 'editable';
        $result['message'] = "Field '$field' is editable - potential security issue";
        
        // Try to modify it
        if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $editResponse['body'], $matches)) {
            $token = $matches[1];
            
            // Get current values
            $currentData = [];
            if (preg_match_all('/name="([^"]*)"[^>]*value="([^"]*)"/', $editResponse['body'], $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $currentData[$match[1]] = $match[2];
                }
            }
            
            // Try update
            $updateData = array_merge($currentData, [
                '_token' => $token,
                '_method' => 'PUT',
                $field => $newValue
            ]);
            
            $updateResponse = makeRequest("$baseUrl/$module/$recordId", http_build_query($updateData), 'POST', $cookieFile);
            
            if ($updateResponse['code'] === 422 || strpos($updateResponse['body'], 'error') !== false) {
                $result['status'] = 'backend_protected';
                $result['message'] = "Field '$field' modification rejected by backend (good)";
            } elseif ($updateResponse['code'] === 302) {
                // Check if actually modified
                $showResponse = makeRequest("$baseUrl/$module/$recordId", null, 'GET', $cookieFile);
                if (strpos($showResponse['body'], $newValue) !== false) {
                    $result['status'] = 'SECURITY_ISSUE';
                    $result['message'] = "CRITICAL: Field '$field' was successfully modified!";
                    $securityIssues[] = $result;
                } else {
                    $result['status'] = 'silently_ignored';
                    $result['message'] = "Field '$field' modification was ignored (acceptable)";
                }
            }
        }
    }
    
    $testResults[] = $result;
    return $result;
}

echo "Focused Immutable Fields Test\n";
echo "==============================\n\n";

// Login as admin
if (!loginAsAdmin($baseUrl, $cookieFile)) {
    die("❌ Failed to login as admin\n");
}

echo "✅ Logged in as admin\n\n";

// Test critical immutable fields
$criticalFields = [
    'clientes' => ['id', 'created_at', 'document_number'],
    'vehiculos' => ['id', 'created_at', 'vin', 'license_plate'],
    'empleados' => ['id', 'created_at', 'hire_date'],
    'servicios' => ['id', 'created_at'],
    'ordenes' => ['id', 'created_at', 'cliente_id', 'vehiculo_id']
];

echo "Testing Critical Immutable Fields:\n";
echo "-----------------------------------\n";

foreach ($criticalFields as $module => $fields) {
    echo "Testing $module module:\n";
    
    foreach ($fields as $field) {
        $testValue = ($field === 'id') ? '99999' : 'MODIFIED_' . time();
        $result = testFieldModification($baseUrl, $module, $field, $testValue, $cookieFile);
        
        if ($result) {
            $status = $result['status'];
            $icon = ($status === 'SECURITY_ISSUE') ? '❌' : 
                   (in_array($status, ['properly_hidden', 'properly_disabled', 'backend_protected']) ? '✅' : '⚠️');
            
            echo "  $icon $field: {$result['message']}\n";
        } else {
            echo "  ⚠️ $field: Could not test\n";
        }
    }
    echo "\n";
}

// Test unique field constraints
echo "Testing Unique Field Constraints:\n";
echo "----------------------------------\n";

$uniqueFields = [
    'clientes' => ['email', 'document_number'],
    'vehiculos' => ['license_plate', 'vin'],
    'empleados' => ['email'],
    'servicios' => []
];

foreach ($uniqueFields as $module => $fields) {
    if (empty($fields)) continue;
    
    echo "Testing $module module:\n";
    
    foreach ($fields as $field) {
        // Test with duplicate value
        $result = testFieldModification($baseUrl, $module, $field, 'duplicate@test.com', $cookieFile);
        
        if ($result && $result['status'] === 'backend_protected') {
            echo "  ✅ $field: Uniqueness constraint enforced\n";
        } elseif ($result && $result['status'] === 'SECURITY_ISSUE') {
            echo "  ❌ $field: Uniqueness constraint BYPASSED\n";
        } else {
            echo "  ⚠️ $field: Could not verify uniqueness constraint\n";
        }
    }
    echo "\n";
}

// Summary
echo "\nSUMMARY:\n";
echo "=========\n";

$totalTests = count($testResults);
$securityIssuesCount = count($securityIssues);
$properlyProtected = count(array_filter($testResults, function($r) {
    return in_array($r['status'], ['properly_hidden', 'properly_disabled', 'backend_protected']);
}));

echo "Total field tests: $totalTests\n";
echo "Properly protected: $properlyProtected\n";
echo "Security issues found: $securityIssuesCount\n";

if ($securityIssuesCount > 0) {
    echo "\n🚨 CRITICAL SECURITY ISSUES:\n";
    foreach ($securityIssues as $issue) {
        echo "- {$issue['module']}.{$issue['field']}: {$issue['message']}\n";
    }
} else {
    echo "\n✅ No critical security issues found with immutable fields\n";
}

// Detailed breakdown
echo "\nDETAILED RESULTS:\n";
echo "=================\n";

$statusGroups = [];
foreach ($testResults as $result) {
    $statusGroups[$result['status']][] = $result;
}

foreach ($statusGroups as $status => $results) {
    echo "\n$status (" . count($results) . " fields):\n";
    foreach ($results as $result) {
        echo "  - {$result['module']}.{$result['field']}\n";
    }
}

// Save results
file_put_contents(__DIR__ . '/focused_immutable_test_results.json', json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'total_tests' => $totalTests,
    'security_issues' => $securityIssuesCount,
    'properly_protected' => $properlyProtected,
    'results' => $testResults,
    'critical_issues' => $securityIssues
], JSON_PRETTY_PRINT));

echo "\nTest completed. Results saved to focused_immutable_test_results.json\n";
?>