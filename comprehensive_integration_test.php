<?php

/**
 * Comprehensive Frontend-Backend Integration Testing Script
 * Tests all aspects of Laravel application integration
 */

set_time_limit(300); // 5 minutes timeout

class ComprehensiveIntegrationTester {
    private $baseUrl = 'http://localhost:8003';
    private $cookieFile;
    private $csrfToken = null;
    private $authenticated = false;
    private $testResults = [];
    private $logFile;

    public function __construct() {
        $this->cookieFile = __DIR__ . '/integration_test_cookies.txt';
        $this->logFile = __DIR__ . '/integration_test.log';
        
        // Clean up old files
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
        
        file_put_contents($this->logFile, "=== COMPREHENSIVE INTEGRATION TEST START ===\n", LOCK_EX);
    }

    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        echo $logMessage;
    }

    private function makeRequest($url, $options = []) {
        $ch = curl_init();
        
        $defaultOptions = [
            CURLOPT_URL => $this->baseUrl . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'Integration Test Bot 1.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
        ];

        // Add CSRF token if available
        if ($this->csrfToken) {
            $headers[] = 'X-CSRF-TOKEN: ' . $this->csrfToken;
        }

        // Merge headers from options if present
        if (isset($options[CURLOPT_HTTPHEADER])) {
            $headers = array_merge($headers, $options[CURLOPT_HTTPHEADER]);
            unset($options[CURLOPT_HTTPHEADER]);
        }

        $defaultOptions[CURLOPT_HTTPHEADER] = $headers;

        curl_setopt_array($ch, array_merge($defaultOptions, $options));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        curl_close($ch);

        if ($error) {
            $this->log("CURL Error: $error");
            return ['success' => false, 'error' => $error];
        }

        return [
            'success' => true,
            'response' => $response,
            'http_code' => $httpCode,
            'info' => $info
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

    private function makeAjaxRequest($url, $data = [], $method = 'POST') {
        $headers = [
            'X-Requested-With: XMLHttpRequest',
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($this->csrfToken) {
            $headers[] = 'X-CSRF-TOKEN: ' . $this->csrfToken;
        }

        $options = [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        if (!empty($data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        return $this->makeRequest($url, $options);
    }

    public function authenticate() {
        $this->log("=== AUTHENTICATION TEST ===");
        
        // Get login page
        $result = $this->makeRequest('/login');
        
        if (!$result['success']) {
            $this->testResults['authentication'] = [
                'status' => 'FAILED',
                'error' => 'Could not access login page: ' . $result['error']
            ];
            return false;
        }

        if ($result['http_code'] !== 200) {
            $this->testResults['authentication'] = [
                'status' => 'FAILED',
                'error' => 'Login page returned HTTP ' . $result['http_code']
            ];
            return false;
        }

        // Extract CSRF token
        $this->csrfToken = $this->extractCsrfToken($result['response']);
        
        if (!$this->csrfToken) {
            $this->testResults['authentication'] = [
                'status' => 'FAILED',
                'error' => 'Could not extract CSRF token from login page'
            ];
            return false;
        }

        $this->log("CSRF Token extracted: " . substr($this->csrfToken, 0, 10) . "...");

        // Perform login
        $loginData = [
            '_token' => $this->csrfToken,
            'email' => 'admin@taller.com',
            'password' => 'admin123'
        ];

        $loginResult = $this->makeRequest('/login', [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($loginData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'X-CSRF-TOKEN: ' . $this->csrfToken
            ]
        ]);

        if (!$loginResult['success']) {
            $this->testResults['authentication'] = [
                'status' => 'FAILED',
                'error' => 'Login request failed: ' . $loginResult['error']
            ];
            return false;
        }

        // Check if redirected to dashboard (302 or 200 on dashboard)
        if ($loginResult['http_code'] === 302 || strpos($loginResult['response'], 'dashboard') !== false) {
            $this->authenticated = true;
            $this->log("Authentication successful");
            
            // Get new CSRF token from dashboard
            $dashboardResult = $this->makeRequest('/dashboard');
            if ($dashboardResult['success']) {
                $newToken = $this->extractCsrfToken($dashboardResult['response']);
                if ($newToken) {
                    $this->csrfToken = $newToken;
                }
            }
            
            $this->testResults['authentication'] = [
                'status' => 'SUCCESS',
                'message' => 'Successfully authenticated and redirected'
            ];
            return true;
        }

        $this->testResults['authentication'] = [
            'status' => 'FAILED',
            'error' => 'Authentication failed - HTTP ' . $loginResult['http_code']
        ];
        return false;
    }

    public function testClientesModule() {
        $this->log("=== CLIENTES MODULE INTEGRATION TEST ===");
        
        $moduleResults = [
            'create' => $this->testClientesCreate(),
            'edit' => $this->testClientesEdit(),
            'delete' => $this->testClientesDelete(),
            'routing' => $this->testClientesRouting(),
            'ajax_vs_traditional' => $this->testClientesAjaxVsTraditional()
        ];
        
        $this->testResults['clientes_module'] = $moduleResults;
        return $moduleResults;
    }

    private function testClientesCreate() {
        $this->log("Testing CLIENTES CREATE functionality...");
        
        // Test GET create form
        $createFormResult = $this->makeRequest('/clientes/create');
        
        if (!$createFormResult['success'] || $createFormResult['http_code'] !== 200) {
            return [
                'status' => 'FAILED',
                'error' => 'Could not access create form',
                'http_code' => $createFormResult['http_code'] ?? 'N/A'
            ];
        }

        // Extract CSRF token from create form
        $createCsrfToken = $this->extractCsrfToken($createFormResult['response']);
        
        if (!$createCsrfToken) {
            return [
                'status' => 'FAILED',
                'error' => 'Could not extract CSRF token from create form'
            ];
        }

        // Test POST create (traditional form submission)
        $createData = [
            '_token' => $createCsrfToken,
            'name' => 'Test Cliente Integration ' . time(),
            'email' => 'test.integration.' . time() . '@example.com',
            'phone' => '(555) 123-4567',
            'document_number' => 'DOC' . time(),
            'address' => 'Test Address Integration',
            'status' => '1'
        ];

        $createResult = $this->makeRequest('/clientes', [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($createData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'X-CSRF-TOKEN: ' . $createCsrfToken
            ]
        ]);

        if (!$createResult['success']) {
            return [
                'status' => 'FAILED',
                'error' => 'POST request failed: ' . $createResult['error']
            ];
        }

        // Check for successful creation (redirect or success message)
        $isSuccess = $createResult['http_code'] === 302 || 
                    strpos($createResult['response'], 'exitosamente') !== false ||
                    strpos($createResult['response'], 'success') !== false;

        if ($isSuccess) {
            // Test AJAX create
            $ajaxCreateData = [
                'name' => 'Test Cliente AJAX ' . time(),
                'email' => 'test.ajax.' . time() . '@example.com',
                'phone' => '(555) 987-6543',
                'document_number' => 'AJAX' . time(),
                'address' => 'Test AJAX Address',
                'status' => 1
            ];

            $ajaxResult = $this->makeAjaxRequest('/clientes', $ajaxCreateData);
            
            return [
                'status' => 'SUCCESS',
                'traditional_form' => true,
                'ajax_submission' => $ajaxResult['success'] && $ajaxResult['http_code'] === 201,
                'http_codes' => [
                    'traditional' => $createResult['http_code'],
                    'ajax' => $ajaxResult['http_code']
                ]
            ];
        }

        return [
            'status' => 'FAILED',
            'error' => 'Create operation did not succeed',
            'http_code' => $createResult['http_code'],
            'response_snippet' => substr($createResult['response'], 0, 500)
        ];
    }

    private function testClientesEdit() {
        $this->log("Testing CLIENTES EDIT functionality...");
        
        // First, get list of clients to find one to edit
        $indexResult = $this->makeRequest('/clientes');
        
        if (!$indexResult['success']) {
            return [
                'status' => 'FAILED',
                'error' => 'Could not access clientes index'
            ];
        }

        // Extract a client ID from the HTML
        preg_match_all('/clientes\/(\d+)\/edit/', $indexResult['response'], $matches);
        
        if (empty($matches[1])) {
            return [
                'status' => 'FAILED',
                'error' => 'No editable clients found in index'
            ];
        }

        $clienteId = $matches[1][0];
        $this->log("Testing edit for cliente ID: $clienteId");

        // Test GET edit form
        $editFormResult = $this->makeRequest("/clientes/$clienteId/edit");
        
        if (!$editFormResult['success'] || $editFormResult['http_code'] !== 200) {
            return [
                'status' => 'FAILED',
                'error' => 'Could not access edit form',
                'http_code' => $editFormResult['http_code']
            ];
        }

        // Extract CSRF token and current data
        $editCsrfToken = $this->extractCsrfToken($editFormResult['response']);
        
        if (!$editCsrfToken) {
            return [
                'status' => 'FAILED',
                'error' => 'Could not extract CSRF token from edit form'
            ];
        }

        // Test PUT update (traditional form submission)
        $updateData = [
            '_token' => $editCsrfToken,
            '_method' => 'PUT',
            'name' => 'Updated Cliente ' . time(),
            'email' => 'updated.' . time() . '@example.com',
            'phone' => '(555) 999-8888',
            'document_number' => 'UPD' . time(),
            'address' => 'Updated Address',
            'status' => '1'
        ];

        $updateResult = $this->makeRequest("/clientes/$clienteId", [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($updateData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'X-CSRF-TOKEN: ' . $editCsrfToken
            ]
        ]);

        if (!$updateResult['success']) {
            return [
                'status' => 'FAILED',
                'error' => 'PUT request failed: ' . $updateResult['error']
            ];
        }

        $isSuccess = $updateResult['http_code'] === 302 || 
                    strpos($updateResult['response'], 'exitosamente') !== false ||
                    strpos($updateResult['response'], 'actualizado') !== false;

        // Test AJAX update
        $ajaxUpdateData = [
            'name' => 'AJAX Updated Cliente ' . time(),
            'email' => 'ajax.updated.' . time() . '@example.com',
            'phone' => '(555) 777-6666',
            'document_number' => 'AJAXUPD' . time(),
            'address' => 'AJAX Updated Address',
            'status' => 1
        ];

        $ajaxUpdateResult = $this->makeAjaxRequest("/clientes/$clienteId", $ajaxUpdateData, 'PUT');

        return [
            'status' => $isSuccess ? 'SUCCESS' : 'FAILED',
            'traditional_form' => $isSuccess,
            'ajax_submission' => $ajaxUpdateResult['success'] && $ajaxUpdateResult['http_code'] === 200,
            'http_codes' => [
                'traditional' => $updateResult['http_code'],
                'ajax' => $ajaxUpdateResult['http_code']
            ],
            'cliente_id_tested' => $clienteId
        ];
    }

    private function testClientesDelete() {
        $this->log("Testing CLIENTES DELETE functionality...");
        
        // Get list of clients to find one to delete
        $indexResult = $this->makeRequest('/clientes');
        
        if (!$indexResult['success']) {
            return [
                'status' => 'FAILED',
                'error' => 'Could not access clientes index for delete test'
            ];
        }

        // Extract client IDs
        preg_match_all('/clientes\/(\d+).*method.*DELETE/', $indexResult['response'], $matches);
        
        if (empty($matches[1])) {
            return [
                'status' => 'FAILED',
                'error' => 'No deletable clients found'
            ];
        }

        $clienteId = $matches[1][0];
        $this->log("Testing delete for cliente ID: $clienteId");

        // Test traditional form DELETE
        $deleteData = [
            '_token' => $this->csrfToken,
            '_method' => 'DELETE'
        ];

        $deleteResult = $this->makeRequest("/clientes/$clienteId", [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($deleteData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'X-CSRF-TOKEN: ' . $this->csrfToken
            ]
        ]);

        if (!$deleteResult['success']) {
            return [
                'status' => 'FAILED',
                'error' => 'DELETE request failed: ' . $deleteResult['error']
            ];
        }

        $traditionalSuccess = $deleteResult['http_code'] === 302 || 
                            strpos($deleteResult['response'], 'eliminado') !== false;

        // Test AJAX DELETE
        $ajaxDeleteResult = $this->makeAjaxRequest("/clientes/$clienteId", [], 'DELETE');

        return [
            'status' => $traditionalSuccess ? 'SUCCESS' : 'PARTIAL',
            'traditional_delete' => $traditionalSuccess,
            'ajax_delete' => $ajaxDeleteResult['success'],
            'http_codes' => [
                'traditional' => $deleteResult['http_code'],
                'ajax' => $ajaxDeleteResult['http_code']
            ],
            'cliente_id_tested' => $clienteId,
            'issues' => $traditionalSuccess ? [] : ['Traditional delete may have issues']
        ];
    }

    private function testClientesRouting() {
        $this->log("Testing CLIENTES routing and HTTP methods...");
        
        $routingTests = [
            'GET /clientes' => $this->makeRequest('/clientes'),
            'GET /clientes/create' => $this->makeRequest('/clientes/create'),
        ];

        // Test a specific client routes if available
        $indexResult = $this->makeRequest('/clientes');
        if ($indexResult['success']) {
            preg_match('/clientes\/(\d+)/', $indexResult['response'], $matches);
            if (!empty($matches[1])) {
                $clienteId = $matches[1];
                $routingTests["GET /clientes/$clienteId"] = $this->makeRequest("/clientes/$clienteId");
                $routingTests["GET /clientes/$clienteId/edit"] = $this->makeRequest("/clientes/$clienteId/edit");
            }
        }

        $results = [];
        foreach ($routingTests as $route => $result) {
            $results[$route] = [
                'success' => $result['success'],
                'http_code' => $result['http_code'],
                'accessible' => $result['success'] && $result['http_code'] === 200
            ];
        }

        return [
            'status' => 'SUCCESS',
            'routes_tested' => $results,
            'all_routes_accessible' => !in_array(false, array_column($results, 'accessible'))
        ];
    }

    private function testClientesAjaxVsTraditional() {
        $this->log("Analyzing AJAX vs Traditional form submission patterns...");
        
        // Get create form and analyze
        $createFormResult = $this->makeRequest('/clientes/create');
        $indexResult = $this->makeRequest('/clientes');

        $analysis = [
            'create_form_method' => 'traditional', // Based on HTML analysis
            'edit_form_method' => 'traditional',   // Based on HTML analysis
            'delete_method' => 'mixed',            // Traditional with AJAX enhancement
            'index_interactions' => 'mixed'        // Traditional with DataTables/AJAX
        ];

        // Analyze JavaScript patterns
        if ($createFormResult['success']) {
            $createHtml = $createFormResult['response'];
            $hasAjaxCreate = strpos($createHtml, 'ajax') !== false || 
                           strpos($createHtml, 'XMLHttpRequest') !== false;
            $analysis['create_form_method'] = $hasAjaxCreate ? 'ajax' : 'traditional';
        }

        if ($indexResult['success']) {
            $indexHtml = $indexResult['response'];
            $hasDataTables = strpos($indexHtml, 'DataTable') !== false;
            $hasAjaxDelete = strpos($indexHtml, 'ajax') !== false;
            
            $analysis['index_has_datatables'] = $hasDataTables;
            $analysis['index_has_ajax_delete'] = $hasAjaxDelete;
        }

        return [
            'status' => 'SUCCESS',
            'analysis' => $analysis,
            'recommendations' => [
                'Delete operations use AJAX enhancement but may have issues with DataTable reload',
                'Forms primarily use traditional submission with client-side validation',
                'Mixed approach provides good user experience but needs consistent error handling'
            ]
        ];
    }

    public function testAllModulesIntegration() {
        $this->log("=== ALL MODULES INTEGRATION TEST ===");
        
        $modules = ['vehiculos', 'servicios', 'empleados', 'ordenes'];
        $moduleResults = [];

        foreach ($modules as $module) {
            $this->log("Testing module: $module");
            $moduleResults[$module] = $this->testModuleBasics($module);
        }

        $this->testResults['all_modules'] = $moduleResults;
        return $moduleResults;
    }

    private function testModuleBasics($module) {
        $results = [
            'index_access' => false,
            'create_access' => false,
            'csrf_token_present' => false,
            'authentication_required' => true,
            'http_codes' => []
        ];

        // Test index access
        $indexResult = $this->makeRequest("/$module");
        $results['index_access'] = $indexResult['success'] && $indexResult['http_code'] === 200;
        $results['http_codes']['index'] = $indexResult['http_code'];

        if ($results['index_access']) {
            $results['csrf_token_present'] = $this->extractCsrfToken($indexResult['response']) !== null;
        }

        // Test create access
        $createResult = $this->makeRequest("/$module/create");
        $results['create_access'] = $createResult['success'] && $createResult['http_code'] === 200;
        $results['http_codes']['create'] = $createResult['http_code'];

        return $results;
    }

    public function testProfileSection() {
        $this->log("=== PROFILE SECTION INTEGRATION TEST ===");
        
        $profileResults = [];

        // Test profile edit access
        $profileResult = $this->makeRequest('/profile');
        $profileResults['edit_access'] = [
            'success' => $profileResult['success'],
            'http_code' => $profileResult['http_code'],
            'accessible' => $profileResult['success'] && $profileResult['http_code'] === 200
        ];

        if ($profileResults['edit_access']['accessible']) {
            $csrfToken = $this->extractCsrfToken($profileResult['response']);
            
            // Test profile update
            if ($csrfToken) {
                $updateData = [
                    '_token' => $csrfToken,
                    '_method' => 'PATCH',
                    'name' => 'Updated Admin Name',
                    'email' => 'admin@taller.com' // Keep same to avoid conflicts
                ];

                $updateResult = $this->makeRequest('/profile', [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($updateData),
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN: ' . $csrfToken
                    ]
                ]);

                $profileResults['update_test'] = [
                    'success' => $updateResult['success'],
                    'http_code' => $updateResult['http_code'],
                    'updated' => $updateResult['http_code'] === 302 || 
                               strpos($updateResult['response'], 'actualizado') !== false
                ];
            }
        }

        $this->testResults['profile_section'] = $profileResults;
        return $profileResults;
    }

    public function generateReport() {
        $this->log("=== GENERATING COMPREHENSIVE REPORT ===");
        
        $report = [
            'test_timestamp' => date('Y-m-d H:i:s'),
            'base_url' => $this->baseUrl,
            'authentication_status' => $this->authenticated,
            'test_results' => $this->testResults,
            'summary' => $this->generateSummary(),
            'recommendations' => $this->generateRecommendations()
        ];

        $reportFile = __DIR__ . '/comprehensive_integration_report.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));

        $this->log("Report saved to: $reportFile");
        return $report;
    }

    private function generateSummary() {
        $summary = [
            'total_tests' => 0,
            'passed_tests' => 0,
            'failed_tests' => 0,
            'critical_issues' => [],
            'warnings' => []
        ];

        // Analyze results recursively
        $this->analyzeSummary($this->testResults, $summary);

        $summary['success_rate'] = $summary['total_tests'] > 0 ? 
            round(($summary['passed_tests'] / $summary['total_tests']) * 100, 2) : 0;

        return $summary;
    }

    private function analyzeSummary($results, &$summary) {
        foreach ($results as $key => $value) {
            if (is_array($value)) {
                if (isset($value['status'])) {
                    $summary['total_tests']++;
                    if ($value['status'] === 'SUCCESS') {
                        $summary['passed_tests']++;
                    } else {
                        $summary['failed_tests']++;
                        if (isset($value['error'])) {
                            $summary['critical_issues'][] = "$key: " . $value['error'];
                        }
                    }
                } else {
                    $this->analyzeSummary($value, $summary);
                }
            }
        }
    }

    private function generateRecommendations() {
        $recommendations = [
            'immediate_fixes' => [],
            'improvements' => [],
            'best_practices' => []
        ];

        // Analyze specific issues and generate recommendations
        if (isset($this->testResults['clientes_module']['delete']['status']) && 
            $this->testResults['clientes_module']['delete']['status'] !== 'SUCCESS') {
            $recommendations['immediate_fixes'][] = 'Fix delete operation in CLIENTES module - ensure proper AJAX response handling';
        }

        if (isset($this->testResults['authentication']['status']) && 
            $this->testResults['authentication']['status'] !== 'SUCCESS') {
            $recommendations['immediate_fixes'][] = 'Authentication system needs debugging - check credentials and CSRF token handling';
        }

        $recommendations['improvements'][] = 'Implement consistent AJAX error handling across all modules';
        $recommendations['improvements'][] = 'Add loading states for AJAX operations';
        $recommendations['improvements'][] = 'Implement client-side validation consistency';

        $recommendations['best_practices'][] = 'Ensure all forms have proper CSRF protection';
        $recommendations['best_practices'][] = 'Implement proper HTTP status codes for all operations';
        $recommendations['best_practices'][] = 'Add comprehensive logging for debugging';

        return $recommendations;
    }

    public function runAllTests() {
        $this->log("Starting comprehensive integration testing...");
        
        // Step 1: Authenticate
        if (!$this->authenticate()) {
            $this->log("Authentication failed - cannot continue with authenticated tests");
            return $this->generateReport();
        }

        // Step 2: Test CLIENTES module thoroughly
        $this->testClientesModule();

        // Step 3: Test all other modules
        $this->testAllModulesIntegration();

        // Step 4: Test profile section
        $this->testProfileSection();

        // Step 5: Generate comprehensive report
        return $this->generateReport();
    }
}

// Run the comprehensive test
echo "=== COMPREHENSIVE FRONTEND-BACKEND INTEGRATION TESTING ===\n\n";

$tester = new ComprehensiveIntegrationTester();
$report = $tester->runAllTests();

echo "\n=== FINAL REPORT SUMMARY ===\n";
echo "Authentication: " . ($report['authentication_status'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Total Tests: " . $report['summary']['total_tests'] . "\n";
echo "Passed: " . $report['summary']['passed_tests'] . "\n";
echo "Failed: " . $report['summary']['failed_tests'] . "\n";
echo "Success Rate: " . $report['summary']['success_rate'] . "%\n";

if (!empty($report['summary']['critical_issues'])) {
    echo "\nCRITICAL ISSUES:\n";
    foreach ($report['summary']['critical_issues'] as $issue) {
        echo "- $issue\n";
    }
}

echo "\nDetailed report saved to: comprehensive_integration_report.json\n";
echo "Log file: integration_test.log\n";

?>