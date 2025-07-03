<?php

/**
 * FINAL NEGATIVE VALIDATION TESTING SCRIPT
 * Laravel Taller Sistema Security Assessment
 * URL: http://localhost:8003
 */

class FinalNegativeValidationTester
{
    private $baseUrl = 'http://localhost:8003';
    private $cookieFile;
    private $results = [];
    private $vulnerabilities = [];

    public function __construct()
    {
        $this->cookieFile = sys_get_temp_dir() . '/final_validation_cookies.txt';
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    public function runTests()
    {
        echo "🔍 FINAL NEGATIVE VALIDATION SECURITY ASSESSMENT\n";
        echo "==============================================\n\n";

        // Test each module
        $this->testModuleValidation('CLIENTES', '/clientes', $this->getClientesTestData());
        $this->testModuleValidation('VEHICULOS', '/vehiculos', $this->getVehiculosTestData());
        $this->testModuleValidation('SERVICIOS', '/servicios', $this->getServiciosTestData());
        $this->testModuleValidation('EMPLEADOS', '/empleados', $this->getEmpleadosTestData());
        $this->testModuleValidation('ORDENES', '/ordenes', $this->getOrdenesTestData());
        
        // Test authentication and profile
        $this->testAuthenticationSecurity();
        
        // Generate comprehensive report
        $this->generateFinalReport();
        
        return true;
    }

    private function testModuleValidation($moduleName, $endpoint, $testCases)
    {
        echo "🔧 TESTING $moduleName MODULE\n";
        echo str_repeat("=", 30) . "\n";

        $moduleResults = [
            'module' => $moduleName,
            'endpoint' => $endpoint,
            'tests' => [],
            'vulnerabilities' => [],
            'validation_gaps' => []
        ];

        foreach ($testCases as $testCase) {
            echo "  → " . $testCase['name'] . "\n";
            $result = $this->executeSecurityTest($endpoint, $testCase);
            $moduleResults['tests'][] = $result;
        }

        $this->results[] = $moduleResults;
        echo "✅ $moduleName tests completed\n\n";
    }

    private function executeSecurityTest($endpoint, $testCase)
    {
        // Attempt to get CSRF token by visiting the create page
        $token = $this->getCsrfToken($endpoint . '/create');
        
        if ($testCase['data']) {
            $testCase['data']['_token'] = $token;
        }

        $response = $this->makeHttpRequest($testCase['method'], $endpoint, $testCase['data']);
        
        $result = [
            'test_name' => $testCase['name'],
            'method' => $testCase['method'],
            'endpoint' => $endpoint,
            'expected_result' => $testCase['expected'],
            'actual_result' => 'UNKNOWN',
            'security_status' => 'PASS',
            'issues_found' => []
        ];

        if ($response) {
            $result['actual_result'] = $this->analyzeResponse($response, $testCase);
            $result['issues_found'] = $this->detectSecurityIssues($response, $testCase);
            
            if (!empty($result['issues_found'])) {
                $result['security_status'] = 'FAIL';
                foreach ($result['issues_found'] as $issue) {
                    $this->vulnerabilities[] = [
                        'module' => $testCase['module'] ?? 'UNKNOWN',
                        'test' => $testCase['name'],
                        'endpoint' => $endpoint,
                        'issue' => $issue,
                        'severity' => $this->getSeverity($issue)
                    ];
                }
            }
        }

        return $result;
    }

    private function getCsrfToken($url)
    {
        $response = $this->makeHttpRequest('GET', $url);
        if (!$response) return '';

        // Try multiple patterns to extract CSRF token
        $patterns = [
            '/<meta name="csrf-token" content="([^"]+)"/',
            '/<input[^>]*name="_token"[^>]*value="([^"]+)"/',
            '/"csrf_token":"([^"]+)"/',
            '/csrf_token["\']:\s*["\']([^"\']+)["\']/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $response, $matches)) {
                return $matches[1];
            }
        }

        return 'test-token-' . time(); // Fallback token
    }

    private function makeHttpRequest($method, $url, $data = null)
    {
        $fullUrl = $this->baseUrl . $url;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'Security Assessment Tool',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => $method
        ]);

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response;
    }

    private function analyzeResponse($response, $testCase)
    {
        // Check for validation errors (expected for negative tests)
        if (strpos($response, 'error') !== false || 
            strpos($response, 'invalid') !== false ||
            strpos($response, 'required') !== false ||
            strpos($response, 'validation') !== false) {
            return 'VALIDATION_TRIGGERED';
        }

        // Check for successful submission (unexpected for negative tests)
        if (strpos($response, 'success') !== false || 
            strpos($response, 'created') !== false ||
            strpos($response, 'saved') !== false) {
            return 'ACCEPTED_INVALID_DATA';
        }

        // Check for errors
        if (strpos($response, '500') !== false || 
            strpos($response, 'Fatal error') !== false ||
            strpos($response, 'Exception') !== false) {
            return 'SERVER_ERROR';
        }

        return 'NO_CLEAR_RESPONSE';
    }

    private function detectSecurityIssues($response, $testCase)
    {
        $issues = [];

        // SQL Injection detection
        if (isset($testCase['data'])) {
            foreach ($testCase['data'] as $key => $value) {
                if (is_string($value) && 
                    (strpos($value, 'DROP TABLE') !== false || 
                     strpos($value, 'UNION SELECT') !== false ||
                     strpos($value, "' OR '1'='1") !== false) &&
                    (strpos($response, 'SQL') !== false || 
                     strpos($response, 'mysql') !== false ||
                     strpos($response, 'database') !== false)) {
                    $issues[] = 'SQL_INJECTION_VULNERABLE';
                }
            }
        }

        // XSS detection
        if (isset($testCase['data'])) {
            foreach ($testCase['data'] as $key => $value) {
                if (is_string($value) && 
                    (strpos($value, '<script>') !== false || 
                     strpos($value, 'onerror') !== false ||
                     strpos($value, 'javascript:') !== false) &&
                    strpos($response, $value) !== false) {
                    $issues[] = 'XSS_VULNERABLE';
                }
            }
        }

        // Validation bypass detection
        if ($this->analyzeResponse($response, $testCase) === 'ACCEPTED_INVALID_DATA') {
            $issues[] = 'VALIDATION_BYPASS';
        }

        // Information disclosure
        if (strpos($response, '500') !== false || 
            strpos($response, 'Fatal error') !== false ||
            strpos($response, 'Call to undefined') !== false ||
            strpos($response, 'file_get_contents') !== false) {
            $issues[] = 'INFORMATION_DISCLOSURE';
        }

        return $issues;
    }

    private function getSeverity($issue)
    {
        $criticalIssues = ['SQL_INJECTION_VULNERABLE', 'XSS_VULNERABLE'];
        $highIssues = ['VALIDATION_BYPASS', 'INFORMATION_DISCLOSURE'];
        
        if (in_array($issue, $criticalIssues)) return 'CRITICAL';
        if (in_array($issue, $highIssues)) return 'HIGH';
        return 'MEDIUM';
    }

    private function getClientesTestData()
    {
        return [
            [
                'name' => 'Empty Required Fields',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'name' => '',
                    'email' => '',
                    'phone' => '',
                    'address' => '',
                    'document_number' => ''
                ]
            ],
            [
                'name' => 'SQL Injection in Name',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'name' => "'; DROP TABLE clientes; --",
                    'email' => 'test@domain.com',
                    'phone' => '123456789',
                    'address' => 'Test Address',
                    'document_number' => 'DOC123'
                ]
            ],
            [
                'name' => 'XSS in Name Field',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'name' => '<script>alert("XSS")</script>',
                    'email' => 'test@domain.com',
                    'phone' => '123456789',
                    'address' => 'Test Address',
                    'document_number' => 'DOC123'
                ]
            ],
            [
                'name' => 'Invalid Email Format',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'name' => 'Test User',
                    'email' => 'invalid-email-format',
                    'phone' => '123456789',
                    'address' => 'Test Address',
                    'document_number' => 'DOC123'
                ]
            ],
            [
                'name' => 'Buffer Overflow Attempt',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'name' => str_repeat('A', 10000),
                    'email' => 'test@domain.com',
                    'phone' => '123456789',
                    'address' => str_repeat('B', 10000),
                    'document_number' => 'DOC123'
                ]
            ]
        ];
    }

    private function getVehiculosTestData()
    {
        return [
            [
                'name' => 'Non-existent Client ID',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'cliente_id' => '99999',
                    'brand' => 'Toyota',
                    'model' => 'Corolla',
                    'year' => '2020',
                    'license_plate' => 'ABC123',
                    'vin' => 'VIN123456789',
                    'color' => 'Red'
                ]
            ],
            [
                'name' => 'Invalid Year Format',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'cliente_id' => '1',
                    'brand' => 'Toyota',
                    'model' => 'Corolla',
                    'year' => 'invalid_year',
                    'license_plate' => 'ABC123',
                    'vin' => 'VIN123456789',
                    'color' => 'Red'
                ]
            ],
            [
                'name' => 'Future Year',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'cliente_id' => '1',
                    'brand' => 'Toyota',
                    'model' => 'Corolla',
                    'year' => '2050',
                    'license_plate' => 'ABC123',
                    'vin' => 'VIN123456789',
                    'color' => 'Red'
                ]
            ]
        ];
    }

    private function getServiciosTestData()
    {
        return [
            [
                'name' => 'Negative Price',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'name' => 'Test Service',
                    'description' => 'Test Description',
                    'price' => '-100',
                    'duration_hours' => '2'
                ]
            ],
            [
                'name' => 'Invalid Price Format',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'name' => 'Test Service',
                    'description' => 'Test Description',
                    'price' => 'invalid_price',
                    'duration_hours' => '2'
                ]
            ],
            [
                'name' => 'Empty Service Name',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'name' => '',
                    'description' => 'Test Description',
                    'price' => '100',
                    'duration_hours' => '2'
                ]
            ]
        ];
    }

    private function getEmpleadosTestData()
    {
        return [
            [
                'name' => 'Negative Salary',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'name' => 'Test Employee',
                    'email' => 'test@domain.com',
                    'phone' => '123456789',
                    'position' => 'Mechanic',
                    'salary' => '-1000',
                    'hire_date' => '2023-01-01'
                ]
            ],
            [
                'name' => 'Future Hire Date',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'name' => 'Test Employee',
                    'email' => 'test@domain.com',
                    'phone' => '123456789',
                    'position' => 'Mechanic',  
                    'salary' => '3000',
                    'hire_date' => date('Y-m-d', strtotime('+1 year'))
                ]
            ],
            [
                'name' => 'Invalid Email Format',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'name' => 'Test Employee',
                    'email' => 'invalid-email',
                    'phone' => '123456789',
                    'position' => 'Mechanic',
                    'salary' => '3000',
                    'hire_date' => '2023-01-01'
                ]
            ]
        ];
    }

    private function getOrdenesTestData()
    {
        return [
            [
                'name' => 'Missing Foreign Keys',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'cliente_id' => '',
                    'vehiculo_id' => '',
                    'empleado_id' => '',
                    'servicio_id' => '',
                    'description' => 'Test Description',
                    'status' => 'pending',
                    'total_amount' => '100',
                    'start_date' => '2023-01-01',
                    'end_date' => '2023-01-02'
                ]
            ],
            [
                'name' => 'Invalid Date Range',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'cliente_id' => '1',
                    'vehiculo_id' => '1',
                    'empleado_id' => '1',
                    'servicio_id' => '1',
                    'description' => 'Test Description',
                    'status' => 'pending',
                    'total_amount' => '100',
                    'start_date' => '2023-01-02',
                    'end_date' => '2023-01-01'
                ]
            ],
            [
                'name' => 'Negative Amount',
                'method' => 'POST',
                'expected' => 'VALIDATION_ERROR',
                'data' => [
                    'cliente_id' => '1',
                    'vehiculo_id' => '1',
                    'empleado_id' => '1',
                    'servicio_id' => '1',
                    'description' => 'Test Description',
                    'status' => 'pending',
                    'total_amount' => '-100',
                    'start_date' => '2023-01-01',
                    'end_date' => '2023-01-02'
                ]
            ]
        ];
    }

    private function testAuthenticationSecurity()
    {
        echo "🔐 TESTING AUTHENTICATION SECURITY\n";
        echo "================================\n";

        $authTests = [
            [
                'name' => 'SQL Injection in Login',
                'data' => [
                    'email' => "admin' OR '1'='1' --",
                    'password' => 'anything'
                ]
            ],
            [
                'name' => 'XSS in Login Form',
                'data' => [
                    'email' => '<script>alert("XSS")</script>',
                    'password' => 'password'
                ]
            ]
        ];

        foreach ($authTests as $test) {
            echo "  → " . $test['name'] . "\n";
            $token = $this->getCsrfToken('/login');
            $test['data']['_token'] = $token;
            
            $response = $this->makeHttpRequest('POST', '/login', $test['data']);
            $issues = $this->detectSecurityIssues($response, $test);
            
            if (!empty($issues)) {
                foreach ($issues as $issue) {
                    $this->vulnerabilities[] = [
                        'module' => 'AUTHENTICATION',
                        'test' => $test['name'],
                        'endpoint' => '/login',
                        'issue' => $issue,
                        'severity' => $this->getSeverity($issue)
                    ];
                }
            }
        }

        echo "✅ Authentication security tests completed\n\n";
    }

    private function generateFinalReport()
    {
        echo "📊 GENERATING FINAL SECURITY ASSESSMENT REPORT\n";
        echo "=============================================\n\n";

        $totalTests = 0;
        $criticalVulns = 0;
        $highVulns = 0;
        $mediumVulns = 0;

        foreach ($this->results as $module) {
            $totalTests += count($module['tests']);
        }

        foreach ($this->vulnerabilities as $vuln) {
            switch ($vuln['severity']) {
                case 'CRITICAL': $criticalVulns++; break;
                case 'HIGH': $highVulns++; break;
                case 'MEDIUM': $mediumVulns++; break;
            }
        }

        $securityRating = $this->calculateOverallRating($criticalVulns, $highVulns, $mediumVulns);

        $report = [
            'assessment_summary' => [
                'application' => 'Laravel Taller Sistema',
                'assessment_date' => date('Y-m-d H:i:s'),
                'total_modules_tested' => count($this->results),
                'total_tests_executed' => $totalTests,
                'critical_vulnerabilities' => $criticalVulns,
                'high_risk_vulnerabilities' => $highVulns,
                'medium_risk_vulnerabilities' => $mediumVulns,
                'overall_security_rating' => $securityRating
            ],
            'detailed_results' => $this->results,
            'vulnerabilities' => $this->vulnerabilities,
            'recommendations' => $this->generateSecurityRecommendations()
        ];

        // Save comprehensive report
        file_put_contents('/mnt/c/Users/lukka/taller-sistema/FINAL_NEGATIVE_VALIDATION_SECURITY_REPORT.json', json_encode($report, JSON_PRETTY_PRINT));
        
        // Generate markdown report
        $this->generateSecurityMarkdownReport($report);

        // Display executive summary
        echo "🎯 EXECUTIVE SUMMARY\n";
        echo "===================\n";
        echo "Application: Laravel Taller Sistema\n";
        echo "Assessment Date: " . date('Y-m-d H:i:s') . "\n";
        echo "Modules Tested: " . count($this->results) . "\n";
        echo "Total Tests: $totalTests\n";
        echo "Critical Vulnerabilities: $criticalVulns 🔴\n";
        echo "High Risk Vulnerabilities: $highVulns 🟠\n";  
        echo "Medium Risk Vulnerabilities: $mediumVulns 🟡\n";
        echo "Overall Security Rating: $securityRating\n\n";

        if (!empty($this->vulnerabilities)) {
            echo "⚠️  SECURITY VULNERABILITIES IDENTIFIED:\n";
            echo "=======================================\n";
            foreach ($this->vulnerabilities as $vuln) {
                echo "• " . $vuln['severity'] . " - " . $vuln['issue'] . " in " . $vuln['module'] . " (" . $vuln['endpoint'] . ")\n";
            }
            echo "\n";
        }

        echo "📄 Reports Generated:\n";
        echo "• JSON: FINAL_NEGATIVE_VALIDATION_SECURITY_REPORT.json\n";
        echo "• Markdown: FINAL_NEGATIVE_VALIDATION_SECURITY_REPORT.md\n\n";
        echo "✅ NEGATIVE VALIDATION SECURITY ASSESSMENT COMPLETED\n";
    }

    private function calculateOverallRating($critical, $high, $medium)
    {
        if ($critical > 0) return 'CRITICAL - IMMEDIATE ACTION REQUIRED';
        if ($high > 2) return 'HIGH RISK - ACTION REQUIRED';
        if ($high > 0 || $medium > 5) return 'MEDIUM RISK - IMPROVEMENTS NEEDED';
        if ($medium > 0) return 'LOW RISK - MINOR IMPROVEMENTS';
        return 'SECURE - GOOD SECURITY POSTURE';
    }

    private function generateSecurityRecommendations()
    {
        $recommendations = [
            'critical_actions' => [],
            'high_priority' => [],
            'medium_priority' => [],
            'best_practices' => []
        ];

        $hasSQL = false;
        $hasXSS = false;
        $hasValidationBypass = false;

        foreach ($this->vulnerabilities as $vuln) {
            switch ($vuln['issue']) {
                case 'SQL_INJECTION_VULNERABLE':
                    if (!$hasSQL) {
                        $recommendations['critical_actions'][] = 'IMMEDIATE: Fix SQL injection vulnerabilities using parameterized queries and input validation';
                        $hasSQL = true;
                    }
                    break;
                case 'XSS_VULNERABLE':
                    if (!$hasXSS) {
                        $recommendations['critical_actions'][] = 'IMMEDIATE: Implement output encoding and Content Security Policy to prevent XSS attacks';
                        $hasXSS = true;
                    }
                    break;
                case 'VALIDATION_BYPASS':
                    if (!$hasValidationBypass) {
                        $recommendations['high_priority'][] = 'Strengthen server-side validation rules and ensure client-side validation cannot be bypassed';
                        $hasValidationBypass = true;
                    }
                    break;
                case 'INFORMATION_DISCLOSURE':
                    $recommendations['high_priority'][] = 'Implement proper error handling to prevent sensitive information disclosure';
                    break;
            }
        }

        // Add general recommendations
        $recommendations['medium_priority'] = [
            'Implement rate limiting for form submissions to prevent brute force attacks',
            'Add input length limits and validation for all form fields',
            'Implement proper session management and CSRF protection',
            'Add logging for security events and failed validation attempts'
        ];

        $recommendations['best_practices'] = [
            'Regular security audits and penetration testing',
            'Implement Web Application Firewall (WAF)',
            'Keep Laravel framework and dependencies updated',
            'Implement security headers (HSTS, CSP, etc.)',
            'Regular backup and disaster recovery testing'
        ];

        return $recommendations;
    }

    private function generateSecurityMarkdownReport($data)
    {
        $md = "# NEGATIVE VALIDATION SECURITY ASSESSMENT REPORT\n\n";
        $md .= "**Application:** Laravel Taller Sistema  \n";
        $md .= "**Assessment Date:** {$data['assessment_summary']['assessment_date']}  \n";
        $md .= "**Security Rating:** {$data['assessment_summary']['overall_security_rating']}  \n\n";

        $md .= "## 🎯 Executive Summary\n\n";
        $md .= "| Assessment Metric | Result |\n";
        $md .= "|-------------------|--------|\n";
        $md .= "| Modules Tested | {$data['assessment_summary']['total_modules_tested']} |\n";
        $md .= "| Total Tests Executed | {$data['assessment_summary']['total_tests_executed']} |\n";
        $md .= "| Critical Vulnerabilities | {$data['assessment_summary']['critical_vulnerabilities']} |\n";
        $md .= "| High Risk Vulnerabilities | {$data['assessment_summary']['high_risk_vulnerabilities']} |\n";
        $md .= "| Medium Risk Vulnerabilities | {$data['assessment_summary']['medium_risk_vulnerabilities']} |\n\n";

        if (!empty($data['vulnerabilities'])) {
            $md .= "## 🚨 Security Vulnerabilities\n\n";
            $criticalVulns = array_filter($data['vulnerabilities'], fn($v) => $v['severity'] === 'CRITICAL');
            $highVulns = array_filter($data['vulnerabilities'], fn($v) => $v['severity'] === 'HIGH');
            $mediumVulns = array_filter($data['vulnerabilities'], fn($v) => $v['severity'] === 'MEDIUM');

            if (!empty($criticalVulns)) {
                $md .= "### 🔴 Critical Vulnerabilities\n";
                foreach ($criticalVulns as $vuln) {
                    $md .= "- **{$vuln['issue']}** in {$vuln['module']} module (`{$vuln['endpoint']}`)\n";
                    $md .= "  - Test: {$vuln['test']}\n";
                }
                $md .= "\n";
            }

            if (!empty($highVulns)) {
                $md .= "### 🟠 High Risk Vulnerabilities\n";
                foreach ($highVulns as $vuln) {
                    $md .= "- **{$vuln['issue']}** in {$vuln['module']} module (`{$vuln['endpoint']}`)\n";
                    $md .= "  - Test: {$vuln['test']}\n";
                }
                $md .= "\n";
            }

            if (!empty($mediumVulns)) {
                $md .= "### 🟡 Medium Risk Vulnerabilities\n";
                foreach ($mediumVulns as $vuln) {
                    $md .= "- **{$vuln['issue']}** in {$vuln['module']} module (`{$vuln['endpoint']}`)\n";
                    $md .= "  - Test: {$vuln['test']}\n";
                }
                $md .= "\n";
            }
        }

        $md .= "## 📋 Module Test Results\n\n";
        foreach ($data['detailed_results'] as $module) {
            $md .= "### {$module['module']} Module\n";
            $md .= "**Endpoint:** `{$module['endpoint']}`  \n";
            $md .= "**Tests Executed:** " . count($module['tests']) . "\n\n";
            
            foreach ($module['tests'] as $test) {
                $statusIcon = $test['security_status'] === 'PASS' ? '🟢' : '🔴';
                $md .= "- {$statusIcon} **{$test['test_name']}** - {$test['security_status']}\n";
                if (!empty($test['issues_found'])) {
                    foreach ($test['issues_found'] as $issue) {
                        $md .= "  - ⚠️ {$issue}\n";
                    }
                }
            }
            $md .= "\n";
        }

        $md .= "## 🛠️ Security Recommendations\n\n";
        if (!empty($data['recommendations']['critical_actions'])) {
            $md .= "### 🔴 Critical Actions (Immediate)\n";
            foreach ($data['recommendations']['critical_actions'] as $action) {
                $md .= "- {$action}\n";
            }
            $md .= "\n";
        }

        if (!empty($data['recommendations']['high_priority'])) {
            $md .= "### 🟠 High Priority\n";
            foreach ($data['recommendations']['high_priority'] as $action) {
                $md .= "- {$action}\n";
            }
            $md .= "\n";
        }

        if (!empty($data['recommendations']['medium_priority'])) {
            $md .= "### 🟡 Medium Priority\n";
            foreach ($data['recommendations']['medium_priority'] as $action) {
                $md .= "- {$action}\n";
            }
            $md .= "\n";
        }

        if (!empty($data['recommendations']['best_practices'])) {
            $md .= "### 🟢 Security Best Practices\n";
            foreach ($data['recommendations']['best_practices'] as $practice) {
                $md .= "- {$practice}\n";
            }
        }

        file_put_contents('/mnt/c/Users/lukka/taller-sistema/FINAL_NEGATIVE_VALIDATION_SECURITY_REPORT.md', $md);
    }
}

// Execute the final negative validation security assessment
$tester = new FinalNegativeValidationTester();
$tester->runTests();

?>