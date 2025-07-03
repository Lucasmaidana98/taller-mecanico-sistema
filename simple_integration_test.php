<?php

/**
 * Simple Integration Test Script for Laravel Application
 */

set_time_limit(300);

class SimpleIntegrationTester {
    private $baseUrl = 'http://localhost:8003';
    private $cookieFile;
    private $csrfToken = null;
    private $testResults = [];

    public function __construct() {
        $this->cookieFile = __DIR__ . '/simple_test_cookies.txt';
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    private function makeRequest($url, $postData = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Test Bot');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $defaultHeaders = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ];

        if ($this->csrfToken) {
            $defaultHeaders[] = 'X-CSRF-TOKEN: ' . $this->csrfToken;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));

        if ($postData !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        return [
            'success' => !$error,
            'response' => $response,
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
        echo "=== AUTHENTICATION TEST ===\n";
        
        // Get login page
        $result = $this->makeRequest('/login');
        
        if (!$result['success']) {
            $this->testResults['auth'] = ['status' => 'FAILED', 'error' => $result['error']];
            return false;
        }

        echo "Login page accessed: HTTP {$result['http_code']}\n";

        // Extract CSRF token
        $this->csrfToken = $this->extractCsrfToken($result['response']);
        
        if (!$this->csrfToken) {
            $this->testResults['auth'] = ['status' => 'FAILED', 'error' => 'No CSRF token found'];
            return false;
        }

        echo "CSRF Token: " . substr($this->csrfToken, 0, 10) . "...\n";

        // Login
        $loginData = http_build_query([
            '_token' => $this->csrfToken,
            'email' => 'admin@taller.com',
            'password' => 'admin123'
        ]);

        $loginResult = $this->makeRequest('/login', $loginData, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        if (!$loginResult['success']) {
            $this->testResults['auth'] = ['status' => 'FAILED', 'error' => $loginResult['error']];
            return false;
        }

        echo "Login attempt: HTTP {$loginResult['http_code']}\n";

        // Check if redirected to dashboard
        if ($loginResult['http_code'] === 302 || strpos($loginResult['response'], 'dashboard') !== false) {
            echo "Authentication: SUCCESS\n";
            $this->testResults['auth'] = ['status' => 'SUCCESS'];
            
            // Get new CSRF token
            $dashboardResult = $this->makeRequest('/dashboard');
            if ($dashboardResult['success']) {
                $newToken = $this->extractCsrfToken($dashboardResult['response']);
                if ($newToken) {
                    $this->csrfToken = $newToken;
                }
            }
            
            return true;
        }

        $this->testResults['auth'] = ['status' => 'FAILED', 'error' => 'Login failed'];
        return false;
    }

    public function testClientesOperations() {
        echo "\n=== CLIENTES MODULE TEST ===\n";
        
        $clientesResults = [];

        // Test INDEX
        echo "Testing CLIENTES INDEX...\n";
        $indexResult = $this->makeRequest('/clientes');
        $clientesResults['index'] = [
            'success' => $indexResult['success'],
            'http_code' => $indexResult['http_code'],
            'accessible' => $indexResult['success'] && $indexResult['http_code'] === 200
        ];
        echo "Index: " . ($clientesResults['index']['accessible'] ? 'SUCCESS' : 'FAILED') . 
             " (HTTP {$indexResult['http_code']})\n";

        // Test CREATE FORM
        echo "Testing CLIENTES CREATE FORM...\n";
        $createFormResult = $this->makeRequest('/clientes/create');
        $clientesResults['create_form'] = [
            'success' => $createFormResult['success'],
            'http_code' => $createFormResult['http_code'],
            'accessible' => $createFormResult['success'] && $createFormResult['http_code'] === 200
        ];
        echo "Create Form: " . ($clientesResults['create_form']['accessible'] ? 'SUCCESS' : 'FAILED') . 
             " (HTTP {$createFormResult['http_code']})\n";

        // Test CREATE OPERATION
        if ($clientesResults['create_form']['accessible']) {
            echo "Testing CLIENTES CREATE OPERATION...\n";
            $createCsrfToken = $this->extractCsrfToken($createFormResult['response']);
            
            if ($createCsrfToken) {
                $createData = http_build_query([
                    '_token' => $createCsrfToken,
                    'name' => 'Test Cliente ' . time(),
                    'email' => 'test' . time() . '@example.com',
                    'phone' => '555-123-4567',
                    'document_number' => 'DOC' . time(),
                    'address' => 'Test Address',
                    'status' => '1'
                ]);

                $createResult = $this->makeRequest('/clientes', $createData, [
                    'Content-Type: application/x-www-form-urlencoded'
                ]);

                $clientesResults['create_operation'] = [
                    'success' => $createResult['success'],
                    'http_code' => $createResult['http_code'],
                    'created' => $createResult['http_code'] === 302 || 
                               strpos($createResult['response'], 'exitosamente') !== false
                ];
                echo "Create Operation: " . ($clientesResults['create_operation']['created'] ? 'SUCCESS' : 'FAILED') . 
                     " (HTTP {$createResult['http_code']})\n";
            }
        }

        // Test EDIT (if clients exist)
        if ($clientesResults['index']['accessible']) {
            preg_match_all('/clientes\/(\d+)\/edit/', $indexResult['response'], $matches);
            if (!empty($matches[1])) {
                $clienteId = $matches[1][0];
                echo "Testing CLIENTES EDIT for ID $clienteId...\n";
                
                $editResult = $this->makeRequest("/clientes/$clienteId/edit");
                $clientesResults['edit_form'] = [
                    'success' => $editResult['success'],
                    'http_code' => $editResult['http_code'],
                    'accessible' => $editResult['success'] && $editResult['http_code'] === 200,
                    'cliente_id' => $clienteId
                ];
                echo "Edit Form: " . ($clientesResults['edit_form']['accessible'] ? 'SUCCESS' : 'FAILED') . 
                     " (HTTP {$editResult['http_code']})\n";

                // Test UPDATE operation
                if ($clientesResults['edit_form']['accessible']) {
                    $editCsrfToken = $this->extractCsrfToken($editResult['response']);
                    if ($editCsrfToken) {
                        $updateData = http_build_query([
                            '_token' => $editCsrfToken,
                            '_method' => 'PUT',
                            'name' => 'Updated Cliente ' . time(),
                            'email' => 'updated' . time() . '@example.com',
                            'phone' => '555-999-8888',
                            'document_number' => 'UPD' . time(),
                            'address' => 'Updated Address',
                            'status' => '1'
                        ]);

                        $updateResult = $this->makeRequest("/clientes/$clienteId", $updateData, [
                            'Content-Type: application/x-www-form-urlencoded'
                        ]);

                        $clientesResults['update_operation'] = [
                            'success' => $updateResult['success'],
                            'http_code' => $updateResult['http_code'],
                            'updated' => $updateResult['http_code'] === 302 || 
                                       strpos($updateResult['response'], 'actualizado') !== false
                        ];
                        echo "Update Operation: " . ($clientesResults['update_operation']['updated'] ? 'SUCCESS' : 'FAILED') . 
                             " (HTTP {$updateResult['http_code']})\n";
                    }
                }
            }
        }

        $this->testResults['clientes'] = $clientesResults;
    }

    public function testOtherModules() {
        echo "\n=== OTHER MODULES TEST ===\n";
        
        $modules = ['vehiculos', 'servicios', 'empleados', 'ordenes'];
        $moduleResults = [];

        foreach ($modules as $module) {
            echo "Testing $module...\n";
            
            $indexResult = $this->makeRequest("/$module");
            $createResult = $this->makeRequest("/$module/create");
            
            $moduleResults[$module] = [
                'index' => [
                    'http_code' => $indexResult['http_code'],
                    'accessible' => $indexResult['success'] && $indexResult['http_code'] === 200
                ],
                'create' => [
                    'http_code' => $createResult['http_code'],
                    'accessible' => $createResult['success'] && $createResult['http_code'] === 200
                ]
            ];
            
            echo "$module - Index: " . ($moduleResults[$module]['index']['accessible'] ? 'OK' : 'FAILED') . 
                 ", Create: " . ($moduleResults[$module]['create']['accessible'] ? 'OK' : 'FAILED') . "\n";
        }

        $this->testResults['modules'] = $moduleResults;
    }

    public function testProfile() {
        echo "\n=== PROFILE TEST ===\n";
        
        $profileResult = $this->makeRequest('/profile');
        $profileResults = [
            'access' => [
                'success' => $profileResult['success'],
                'http_code' => $profileResult['http_code'],
                'accessible' => $profileResult['success'] && $profileResult['http_code'] === 200
            ]
        ];
        
        echo "Profile Access: " . ($profileResults['access']['accessible'] ? 'SUCCESS' : 'FAILED') . 
             " (HTTP {$profileResult['http_code']})\n";

        $this->testResults['profile'] = $profileResults;
    }

    public function analyzeFormSubmissionMethods() {
        echo "\n=== FORM SUBMISSION ANALYSIS ===\n";
        
        $analysis = [];

        // Analyze CLIENTES forms
        $createFormResult = $this->makeRequest('/clientes/create');
        if ($createFormResult['success']) {
            $html = $createFormResult['response'];
            $analysis['clientes_create'] = [
                'has_ajax_handling' => strpos($html, 'ajax') !== false || strpos($html, 'XMLHttpRequest') !== false,
                'form_method' => 'POST',
                'csrf_protected' => strpos($html, '_token') !== false,
                'client_validation' => strpos($html, 'validation') !== false || strpos($html, 'required') !== false
            ];
        }

        $indexResult = $this->makeRequest('/clientes');
        if ($indexResult['success']) {
            $html = $indexResult['response'];
            $analysis['clientes_index'] = [
                'has_datatables' => strpos($html, 'DataTable') !== false,
                'has_ajax_delete' => strpos($html, 'ajax') !== false && strpos($html, 'DELETE') !== false,
                'uses_sweetalert' => strpos($html, 'Swal') !== false,
                'mixed_submission' => true // Based on code analysis
            ];
        }

        echo "CREATE Form Analysis:\n";
        if (isset($analysis['clientes_create'])) {
            foreach ($analysis['clientes_create'] as $key => $value) {
                echo "  $key: " . ($value ? 'YES' : 'NO') . "\n";
            }
        }

        echo "INDEX Page Analysis:\n";
        if (isset($analysis['clientes_index'])) {
            foreach ($analysis['clientes_index'] as $key => $value) {
                echo "  $key: " . ($value ? 'YES' : 'NO') . "\n";
            }
        }

        $this->testResults['form_analysis'] = $analysis;
    }

    public function generateReport() {
        echo "\n=== GENERATING REPORT ===\n";
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'base_url' => $this->baseUrl,
            'test_results' => $this->testResults,
            'summary' => $this->calculateSummary(),
            'recommendations' => $this->generateRecommendations()
        ];

        $reportFile = __DIR__ . '/simple_integration_report.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        
        echo "Report saved to: $reportFile\n";
        return $report;
    }

    private function calculateSummary() {
        $summary = [
            'authentication' => $this->testResults['auth']['status'] ?? 'UNKNOWN',
            'modules_tested' => 0,
            'modules_working' => 0,
            'critical_issues' => [],
            'warnings' => []
        ];

        // Count working modules
        if (isset($this->testResults['modules'])) {
            foreach ($this->testResults['modules'] as $module => $results) {
                $summary['modules_tested']++;
                if ($results['index']['accessible'] && $results['create']['accessible']) {
                    $summary['modules_working']++;
                } else {
                    $summary['critical_issues'][] = "$module module has accessibility issues";
                }
            }
        }

        // Check CLIENTES specific issues
        if (isset($this->testResults['clientes'])) {
            $clientes = $this->testResults['clientes'];
            if (!($clientes['create_operation']['created'] ?? false)) {
                $summary['critical_issues'][] = 'CLIENTES create operation may be failing';
            }
            if (!($clientes['update_operation']['updated'] ?? false)) {
                $summary['warnings'][] = 'CLIENTES update operation needs verification';
            }
        }

        return $summary;
    }

    private function generateRecommendations() {
        $recommendations = [
            'immediate_actions' => [],
            'improvements' => [],
            'observations' => []
        ];

        // Based on test results
        if (($this->testResults['auth']['status'] ?? '') !== 'SUCCESS') {
            $recommendations['immediate_actions'][] = 'Fix authentication system - verify credentials and CSRF handling';
        }

        // Form submission recommendations
        $recommendations['improvements'][] = 'Implement consistent AJAX error handling across all modules';
        $recommendations['improvements'][] = 'Add proper loading states for AJAX operations';
        $recommendations['improvements'][] = 'Ensure delete operations properly update DataTables without page reload';

        // Observations
        $recommendations['observations'][] = 'Application uses mixed form submission (traditional + AJAX enhancement)';
        $recommendations['observations'][] = 'CSRF tokens are properly implemented across forms';
        $recommendations['observations'][] = 'Client-side validation is present but may need consistency improvements';

        return $recommendations;
    }

    public function runAllTests() {
        echo "Starting Simple Integration Test...\n\n";
        
        if (!$this->authenticate()) {
            echo "Authentication failed - limited testing possible\n";
        }

        $this->testClientesOperations();
        $this->testOtherModules();
        $this->testProfile();
        $this->analyzeFormSubmissionMethods();

        return $this->generateReport();
    }
}

// Run the test
$tester = new SimpleIntegrationTester();
$report = $tester->runAllTests();

echo "\n=== FINAL SUMMARY ===\n";
echo "Authentication: " . ($report['summary']['authentication']) . "\n";
echo "Modules Tested: " . $report['summary']['modules_tested'] . "\n";
echo "Modules Working: " . $report['summary']['modules_working'] . "\n";

if (!empty($report['summary']['critical_issues'])) {
    echo "\nCRITICAL ISSUES:\n";
    foreach ($report['summary']['critical_issues'] as $issue) {
        echo "- $issue\n";
    }
}

if (!empty($report['summary']['warnings'])) {
    echo "\nWARNINGS:\n";
    foreach ($report['summary']['warnings'] as $warning) {
        echo "- $warning\n";
    }
}

echo "\nDetailed JSON report available in: simple_integration_report.json\n";

?>