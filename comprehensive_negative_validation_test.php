<?php

/**
 * COMPREHENSIVE NEGATIVE VALIDATION TESTING SCRIPT
 * Focused and optimized version for Laravel Taller Sistema application
 * URL: http://localhost:8003
 * Admin Credentials: admin@taller.com / admin123
 */

class ComprehensiveNegativeValidationTester
{
    private $baseUrl = 'http://localhost:8003';
    private $cookieFile = '/tmp/validation_test_cookies.txt';
    private $results = [];
    private $vulnerabilities = [];
    private $csrfToken = '';

    public function __construct()
    {
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
        touch($this->cookieFile);
    }

    public function runTests()
    {
        echo "🔍 COMPREHENSIVE NEGATIVE VALIDATION TESTING\n";
        echo "===========================================\n\n";

        if (!$this->login()) {
            echo "❌ Failed to login. Aborting tests.\n";
            return false;
        }

        // Run targeted negative validation tests
        $this->testClientesValidation();
        $this->testVehiculosValidation();
        $this->testServiciosValidation();
        $this->testEmpleadosValidation();
        $this->testOrdenesValidation();
        $this->testProfileValidation();

        $this->generateReport();
        return true;
    }

    private function login()
    {
        echo "🔐 Logging in...\n";
        
        $loginPage = $this->makeRequest('GET', '/login');
        if (!$loginPage) return false;

        $this->csrfToken = $this->extractCsrfToken($loginPage);
        if (!$this->csrfToken) return false;

        $loginData = [
            '_token' => $this->csrfToken,
            'email' => 'admin@taller.com',
            'password' => 'admin123'
        ];

        $response = $this->makeRequest('POST', '/login', $loginData);
        
        if (strpos($response, 'dashboard') !== false || strpos($response, 'Dashboard') !== false) {
            echo "✅ Login successful\n\n";
            return true;
        }

        return false;
    }

    private function testClientesValidation()
    {
        echo "📋 TESTING CLIENTES MODULE VALIDATION GAPS\n";
        echo "========================================\n";

        $moduleResults = [
            'module' => 'CLIENTES',
            'critical_tests' => [],
            'validation_gaps' => [],
            'security_issues' => []
        ];

        // Get CSRF token for forms
        $createForm = $this->makeRequest('GET', '/clientes/create');
        $token = $this->extractCsrfToken($createForm);

        // Critical Test 1: Empty required fields
        echo "  → Testing empty required fields\n";
        $result = $this->testFormSubmission('/clientes', [
            '_token' => $token,
            'name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'document_number' => ''
        ], 'Empty required fields');
        $moduleResults['critical_tests'][] = $result;

        // Critical Test 2: SQL Injection in text fields
        echo "  → Testing SQL injection attempts\n";
        $sqlPayloads = ["'; DROP TABLE clientes; --", "' OR '1'='1", "1' UNION SELECT * FROM users --"];
        foreach ($sqlPayloads as $payload) {
            $result = $this->testFormSubmission('/clientes', [
                '_token' => $token,
                'name' => $payload,
                'email' => 'test@domain.com',
                'phone' => '123456789',
                'address' => $payload,
                'document_number' => 'DOC123'
            ], 'SQL Injection: ' . substr($payload, 0, 15));
            $moduleResults['critical_tests'][] = $result;
        }

        // Critical Test 3: XSS attempts
        echo "  → Testing XSS attempts\n";
        $xssPayloads = ['<script>alert("XSS")</script>', '<img src="x" onerror="alert(\'XSS\')">'];
        foreach ($xssPayloads as $payload) {
            $result = $this->testFormSubmission('/clientes', [
                '_token' => $token,
                'name' => $payload,
                'email' => 'test@domain.com',
                'phone' => '123456789',
                'address' => $payload,
                'document_number' => 'DOC123'
            ], 'XSS: ' . substr($payload, 0, 15));
            $moduleResults['critical_tests'][] = $result;
        }

        // Critical Test 4: Invalid email formats
        echo "  → Testing invalid email formats\n";
        $invalidEmails = ['invalid-email', 'test@', '@domain.com', '<script>alert("xss")</script>@domain.com'];
        foreach ($invalidEmails as $email) {
            $result = $this->testFormSubmission('/clientes', [
                '_token' => $token,
                'name' => 'Test Cliente',
                'email' => $email,
                'phone' => '123456789',
                'address' => 'Test Address',
                'document_number' => 'DOC123'
            ], 'Invalid email: ' . $email);
            $moduleResults['critical_tests'][] = $result;
        }

        // Critical Test 5: Buffer overflow attempts
        echo "  → Testing buffer overflow attempts\n";
        $longString = str_repeat('A', 10000);
        $result = $this->testFormSubmission('/clientes', [
            '_token' => $token,
            'name' => $longString,
            'email' => 'test@domain.com',
            'phone' => $longString,
            'address' => $longString,
            'document_number' => $longString
        ], 'Buffer overflow attempt');
        $moduleResults['critical_tests'][] = $result;

        $this->results[] = $moduleResults;
        echo "✅ CLIENTES validation tests completed\n\n";
    }

    private function testVehiculosValidation()
    {
        echo "🚗 TESTING VEHICULOS MODULE VALIDATION GAPS\n";
        echo "=========================================\n";

        $moduleResults = [
            'module' => 'VEHICULOS',
            'critical_tests' => [],
            'validation_gaps' => [],
            'security_issues' => []
        ];

        $createForm = $this->makeRequest('GET', '/vehiculos/create');
        $token = $this->extractCsrfToken($createForm);

        // Critical Test 1: Non-existent foreign key injection
        echo "  → Testing non-existent client_id\n";
        $result = $this->testFormSubmission('/vehiculos', [
            '_token' => $token,
            'cliente_id' => '99999',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => '2020',
            'license_plate' => 'ABC123',
            'vin' => 'VIN123456789',
            'color' => 'Red'
        ], 'Non-existent client_id');
        $moduleResults['critical_tests'][] = $result;

        // Critical Test 2: Invalid year formats
        echo "  → Testing invalid year formats\n";
        $invalidYears = ['2050', '1800', 'abc', '-2020', '20.5'];
        foreach ($invalidYears as $year) {
            $result = $this->testFormSubmission('/vehiculos', [
                '_token' => $token,
                'cliente_id' => '1',
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'year' => $year,
                'license_plate' => 'ABC' . rand(100, 999),
                'vin' => 'VIN' . rand(100000, 999999),
                'color' => 'Red'
            ], 'Invalid year: ' . $year);
            $moduleResults['critical_tests'][] = $result;
        }

        // Critical Test 3: SQL injection in vehicle fields
        echo "  → Testing SQL injection in vehicle fields\n";
        $result = $this->testFormSubmission('/vehiculos', [
            '_token' => $token,
            'cliente_id' => '1',
            'brand' => "'; DROP TABLE vehiculos; --",
            'model' => "' OR '1'='1",
            'year' => '2020',
            'license_plate' => 'ABC123',
            'vin' => 'VIN123456',
            'color' => "1' UNION SELECT * FROM users --"
        ], 'SQL injection in vehicle fields');
        $moduleResults['critical_tests'][] = $result;

        $this->results[] = $moduleResults;
        echo "✅ VEHICULOS validation tests completed\n\n";
    }

    private function testServiciosValidation()
    {
        echo "🔧 TESTING SERVICIOS MODULE VALIDATION GAPS\n";
        echo "=========================================\n";

        $moduleResults = [
            'module' => 'SERVICIOS',
            'critical_tests' => [],
            'validation_gaps' => [],
            'security_issues' => []
        ];

        $createForm = $this->makeRequest('GET', '/servicios/create');
        $token = $this->extractCsrfToken($createForm);

        // Critical Test 1: Negative and zero prices
        echo "  → Testing negative and zero prices\n";
        $invalidPrices = ['-100', '0', 'abc', '$100', '999999999999999'];
        foreach ($invalidPrices as $price) {
            $result = $this->testFormSubmission('/servicios', [
                '_token' => $token,
                'name' => 'Test Service',
                'description' => 'Test Description',
                'price' => $price,
                'duration_hours' => '2'
            ], 'Invalid price: ' . $price);
            $moduleResults['critical_tests'][] = $result;
        }

        // Critical Test 2: Invalid duration formats
        echo "  → Testing invalid duration formats\n";
        $invalidDurations = ['-5', 'abc', '∞', 'null'];
        foreach ($invalidDurations as $duration) {
            $result = $this->testFormSubmission('/servicios', [
                '_token' => $token,
                'name' => 'Test Service',
                'description' => 'Test Description',
                'price' => '100',
                'duration_hours' => $duration
            ], 'Invalid duration: ' . $duration);
            $moduleResults['critical_tests'][] = $result;
        }

        // Critical Test 3: XSS in service fields
        echo "  → Testing XSS in service fields\n";
        $result = $this->testFormSubmission('/servicios', [
            '_token' => $token,
            'name' => '<script>alert("XSS")</script>',
            'description' => '<img src="x" onerror="alert(\'XSS\')">',
            'price' => '100',
            'duration_hours' => '2'
        ], 'XSS in service fields');
        $moduleResults['critical_tests'][] = $result;

        $this->results[] = $moduleResults;
        echo "✅ SERVICIOS validation tests completed\n\n";
    }

    private function testEmpleadosValidation()
    {
        echo "👥 TESTING EMPLEADOS MODULE VALIDATION GAPS\n";
        echo "=========================================\n";

        $moduleResults = [
            'module' => 'EMPLEADOS',
            'critical_tests' => [],
            'validation_gaps' => [],
            'security_issues' => []
        ];

        $createForm = $this->makeRequest('GET', '/empleados/create');
        $token = $this->extractCsrfToken($createForm);

        // Critical Test 1: Invalid salary amounts
        echo "  → Testing invalid salary amounts\n";
        $invalidSalaries = ['-1000', '0', '999999999999999', 'abc', '$1000'];
        foreach ($invalidSalaries as $salary) {
            $result = $this->testFormSubmission('/empleados', [
                '_token' => $token,
                'name' => 'Test Employee',
                'email' => 'test' . rand(1000, 9999) . '@test.com',
                'phone' => '123456789',
                'position' => 'Mechanic',
                'salary' => $salary,
                'hire_date' => '2023-01-01'
            ], 'Invalid salary: ' . $salary);
            $moduleResults['critical_tests'][] = $result;
        }

        // Critical Test 2: Future hire dates
        echo "  → Testing future hire dates\n";
        $futureDate = date('Y-m-d', strtotime('+1 year'));
        $result = $this->testFormSubmission('/empleados', [
            '_token' => $token,
            'name' => 'Test Employee',
            'email' => 'future@test.com',
            'phone' => '123456789',
            'position' => 'Mechanic',
            'salary' => '3000',
            'hire_date' => $futureDate
        ], 'Future hire date');
        $moduleResults['critical_tests'][] = $result;

        // Critical Test 3: Empty required fields
        echo "  → Testing empty required fields\n";
        $result = $this->testFormSubmission('/empleados', [
            '_token' => $token,
            'name' => '',
            'email' => '',
            'phone' => '',
            'position' => '',
            'salary' => '',
            'hire_date' => ''
        ], 'All empty required fields');
        $moduleResults['critical_tests'][] = $result;

        $this->results[] = $moduleResults;
        echo "✅ EMPLEADOS validation tests completed\n\n";
    }

    private function testOrdenesValidation()
    {
        echo "📋 TESTING ORDENES MODULE VALIDATION GAPS\n";
        echo "=======================================\n";

        $moduleResults = [
            'module' => 'ORDENES',
            'critical_tests' => [],
            'validation_gaps' => [],
            'security_issues' => []
        ];

        $createForm = $this->makeRequest('GET', '/ordenes/create');
        $token = $this->extractCsrfToken($createForm);

        // Critical Test 1: Missing required foreign keys
        echo "  → Testing missing required foreign keys\n";
        $result = $this->testFormSubmission('/ordenes', [
            '_token' => $token,
            'cliente_id' => '',
            'vehiculo_id' => '',
            'empleado_id' => '',
            'servicio_id' => '',
            'description' => 'Test Description',
            'status' => 'pending',
            'total_amount' => '100',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02'
        ], 'Missing all foreign keys');
        $moduleResults['critical_tests'][] = $result;

        // Critical Test 2: Invalid date combinations
        echo "  → Testing invalid date combinations\n";
        $result = $this->testFormSubmission('/ordenes', [
            '_token' => $token,
            'cliente_id' => '1',
            'vehiculo_id' => '1',
            'empleado_id' => '1',
            'servicio_id' => '1',
            'description' => 'Test Description',
            'status' => 'pending',
            'total_amount' => '100',
            'start_date' => '2023-01-02',
            'end_date' => '2023-01-01'
        ], 'End date before start date');
        $moduleResults['critical_tests'][] = $result;

        // Critical Test 3: Negative amounts
        echo "  → Testing negative amounts\n";
        $result = $this->testFormSubmission('/ordenes', [
            '_token' => $token,
            'cliente_id' => '1',
            'vehiculo_id' => '1',
            'empleado_id' => '1',
            'servicio_id' => '1',
            'description' => 'Test Description',
            'status' => 'pending',
            'total_amount' => '-100',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02'
        ], 'Negative total amount');
        $moduleResults['critical_tests'][] = $result;

        // Critical Test 4: Invalid status values
        echo "  → Testing invalid status values\n";
        $invalidStatuses = ['invalid_status', 'PENDING', '1', 'true'];
        foreach ($invalidStatuses as $status) {
            $result = $this->testFormSubmission('/ordenes', [
                '_token' => $token,
                'cliente_id' => '1',
                'vehiculo_id' => '1',
                'empleado_id' => '1',
                'servicio_id' => '1',
                'description' => 'Test Description',
                'status' => $status,
                'total_amount' => '100',
                'start_date' => '2023-01-01',
                'end_date' => '2023-01-02'
            ], 'Invalid status: ' . $status);
            $moduleResults['critical_tests'][] = $result;
        }

        $this->results[] = $moduleResults;
        echo "✅ ORDENES validation tests completed\n\n";
    }

    private function testProfileValidation()
    {
        echo "👤 TESTING PROFILE MODULE VALIDATION GAPS\n";
        echo "=======================================\n";

        $moduleResults = [
            'module' => 'PROFILE',
            'critical_tests' => [],
            'validation_gaps' => [],
            'security_issues' => []
        ];

        $profilePage = $this->makeRequest('GET', '/profile');
        $token = $this->extractCsrfToken($profilePage);

        // Critical Test 1: Empty name fields
        echo "  → Testing empty name fields\n";
        $result = $this->testFormSubmission('/profile', [
            '_token' => $token,
            'name' => '',
            'email' => 'admin@taller.com'
        ], 'Empty name field', 'PATCH');
        $moduleResults['critical_tests'][] = $result;

        // Critical Test 2: Invalid email changes
        echo "  → Testing invalid email changes\n";
        $invalidEmails = ['invalid-email', 'admin@taller.com@hack.com'];
        foreach ($invalidEmails as $email) {
            $result = $this->testFormSubmission('/profile', [
                '_token' => $token,
                'name' => 'Admin User',
                'email' => $email
            ], 'Invalid email: ' . $email, 'PATCH');
            $moduleResults['critical_tests'][] = $result;
        }

        // Critical Test 3: Weak passwords
        echo "  → Testing weak passwords\n";
        $weakPasswords = ['123', 'password', 'admin'];
        foreach ($weakPasswords as $password) {
            $result = $this->testFormSubmission('/password', [
                '_token' => $token,
                'current_password' => 'admin123',
                'password' => $password,
                'password_confirmation' => $password
            ], 'Weak password: ' . $password, 'PUT');
            $moduleResults['critical_tests'][] = $result;
        }

        $this->results[] = $moduleResults;
        echo "✅ PROFILE validation tests completed\n\n";
    }

    private function testFormSubmission($url, $data, $testName, $method = 'POST')
    {
        $response = $this->makeRequest($method, $url, $data);
        
        $result = [
            'test_name' => $testName,
            'url' => $url,
            'method' => $method,
            'response_received' => !empty($response),
            'validation_triggered' => false,
            'security_issues' => [],
            'status' => 'UNKNOWN'
        ];

        if ($response) {
            // Check for validation errors (good)
            if (strpos($response, 'error') !== false || 
                strpos($response, 'invalid') !== false || 
                strpos($response, 'required') !== false ||
                strpos($response, 'The given data was invalid') !== false) {
                $result['validation_triggered'] = true;
                $result['status'] = 'PASS';
            }
            
            // Check for successful submission (bad for negative tests)
            if (strpos($response, 'success') !== false || 
                strpos($response, 'created') !== false ||
                strpos($response, 'updated') !== false) {
                $result['security_issues'][] = 'Form accepted invalid data';
                $result['status'] = 'FAIL';
                $this->vulnerabilities[] = [
                    'type' => 'Validation Bypass',
                    'test' => $testName,
                    'url' => $url,
                    'severity' => 'HIGH',
                    'description' => 'Form accepted data that should have been rejected'
                ];
            }
            
            // Check for SQL injection indicators
            if (strpos($response, 'SQL') !== false || 
                strpos($response, 'mysql') !== false || 
                strpos($response, 'database error') !== false) {
                $result['security_issues'][] = 'SQL injection vulnerability detected';
                $result['status'] = 'CRITICAL';
                $this->vulnerabilities[] = [
                    'type' => 'SQL Injection',
                    'test' => $testName,
                    'url' => $url,
                    'severity' => 'CRITICAL',
                    'description' => 'SQL injection vulnerability detected'
                ];
            }
            
            // Check for XSS reflection
            foreach ($data as $key => $value) {
                if (is_string($value) && 
                    (strpos($value, '<script>') !== false || strpos($value, 'onerror') !== false) && 
                    strpos($response, $value) !== false) {
                    $result['security_issues'][] = 'XSS vulnerability detected';
                    $result['status'] = 'CRITICAL';
                    $this->vulnerabilities[] = [
                        'type' => 'XSS Reflection',
                        'test' => $testName,
                        'url' => $url,
                        'field' => $key,
                        'severity' => 'CRITICAL',
                        'description' => 'XSS payload reflected in response'
                    ];
                }
            }
            
            // Check for 500 errors
            if (strpos($response, '500') !== false || 
                strpos($response, 'Internal Server Error') !== false ||
                strpos($response, 'Fatal error') !== false) {
                $result['security_issues'][] = 'Server error - possible information disclosure';
                $result['status'] = 'HIGH';
                $this->vulnerabilities[] = [
                    'type' => 'Information Disclosure',
                    'test' => $testName,
                    'url' => $url,
                    'severity' => 'HIGH',
                    'description' => 'Server error may disclose sensitive information'
                ];
            }

            if ($result['status'] === 'UNKNOWN') {
                $result['status'] = $result['validation_triggered'] ? 'PASS' : 'REVIEW';
            }
        }

        return $result;
    }

    private function makeRequest($method, $url, $data = null)
    {
        $fullUrl = $this->baseUrl . $url;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Security Validation Tester 1.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => $method
        ]);

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'X-Requested-With: XMLHttpRequest'
            ]);
        }

        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }

    private function extractCsrfToken($html)
    {
        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches);
        $token = $matches[1] ?? '';
        
        if (!$token) {
            preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $html, $matches);
            $token = $matches[1] ?? '';
        }
        
        return $token;
    }

    private function generateReport()
    {
        echo "📊 GENERATING COMPREHENSIVE VALIDATION REPORT\n";
        echo "============================================\n\n";

        $totalTests = 0;
        $criticalIssues = 0;
        $highIssues = 0;
        $passedTests = 0;
        $failedTests = 0;

        foreach ($this->results as $module) {
            $totalTests += count($module['critical_tests']);
            foreach ($module['critical_tests'] as $test) {
                switch ($test['status']) {
                    case 'CRITICAL':
                        $criticalIssues++;
                        break;
                    case 'HIGH':
                        $highIssues++;
                        break;
                    case 'FAIL':
                        $failedTests++;
                        break;
                    case 'PASS':
                        $passedTests++;
                        break;
                }
            }
        }

        $report = [
            'executive_summary' => [
                'test_date' => date('Y-m-d H:i:s'),
                'application_url' => $this->baseUrl,
                'total_modules_tested' => count($this->results),
                'total_tests_executed' => $totalTests,
                'critical_vulnerabilities' => $criticalIssues,
                'high_risk_issues' => $highIssues,
                'validation_bypasses' => $failedTests,
                'passed_validations' => $passedTests,
                'overall_security_rating' => $this->calculateSecurityRating($criticalIssues, $highIssues, $failedTests)
            ],
            'detailed_results' => $this->results,
            'vulnerabilities' => $this->vulnerabilities,
            'recommendations' => $this->generateRecommendations()
        ];

        // Save JSON report
        $jsonFile = '/mnt/c/Users/lukka/taller-sistema/COMPREHENSIVE_NEGATIVE_VALIDATION_REPORT.json';
        file_put_contents($jsonFile, json_encode($report, JSON_PRETTY_PRINT));

        // Generate and save markdown report
        $this->generateMarkdownReport($report);

        // Display executive summary
        echo "📈 EXECUTIVE SUMMARY:\n";
        echo "==================\n";
        echo "• Total Modules Tested: " . count($this->results) . "\n";
        echo "• Total Tests Executed: " . $totalTests . "\n";
        echo "• Critical Vulnerabilities: " . $criticalIssues . " 🔴\n";
        echo "• High Risk Issues: " . $highIssues . " 🟠\n";
        echo "• Validation Bypasses: " . $failedTests . " 🟡\n";
        echo "• Passed Validations: " . $passedTests . " 🟢\n";
        echo "• Overall Security Rating: " . $report['executive_summary']['overall_security_rating'] . "\n\n";

        if (count($this->vulnerabilities) > 0) {
            echo "⚠️  CRITICAL SECURITY FINDINGS:\n";
            echo "==============================\n";
            foreach ($this->vulnerabilities as $vuln) {
                echo "• " . $vuln['severity'] . " - " . $vuln['type'] . " in " . $vuln['url'] . "\n";
                echo "  └─ " . $vuln['description'] . "\n";
            }
            echo "\n";
        }

        echo "💾 Reports saved:\n";
        echo "• JSON: $jsonFile\n";
        echo "• Markdown: /mnt/c/Users/lukka/taller-sistema/COMPREHENSIVE_NEGATIVE_VALIDATION_REPORT.md\n\n";
        echo "✅ COMPREHENSIVE NEGATIVE VALIDATION TESTING COMPLETED\n";
    }

    private function calculateSecurityRating($critical, $high, $failed)
    {
        if ($critical > 0) return 'CRITICAL';
        if ($high > 2) return 'HIGH RISK';
        if ($failed > 5) return 'MEDIUM RISK';
        if ($high > 0 || $failed > 0) return 'LOW RISK';
        return 'SECURE';
    }

    private function generateRecommendations()
    {
        $recommendations = [
            'immediate_actions' => [],
            'short_term_improvements' => [],
            'long_term_security' => []
        ];

        foreach ($this->vulnerabilities as $vuln) {
            switch ($vuln['type']) {
                case 'SQL Injection':
                    $recommendations['immediate_actions'][] = 'CRITICAL: Fix SQL injection vulnerabilities immediately using parameterized queries';
                    break;
                case 'XSS Reflection':
                    $recommendations['immediate_actions'][] = 'CRITICAL: Implement output encoding to prevent XSS attacks';
                    break;
                case 'Validation Bypass':
                    $recommendations['short_term_improvements'][] = 'Strengthen server-side validation for ' . $vuln['url'];
                    break;
                case 'Information Disclosure':
                    $recommendations['short_term_improvements'][] = 'Implement proper error handling to prevent information disclosure';
                    break;
            }
        }

        // Add general recommendations
        $recommendations['short_term_improvements'][] = 'Implement rate limiting for form submissions';
        $recommendations['short_term_improvements'][] = 'Add input sanitization for all user inputs';
        $recommendations['long_term_security'][] = 'Implement Content Security Policy (CSP)';
        $recommendations['long_term_security'][] = 'Regular security audits and penetration testing';
        $recommendations['long_term_security'][] = 'Implement Web Application Firewall (WAF)';

        return $recommendations;
    }

    private function generateMarkdownReport($data)
    {
        $md = "# COMPREHENSIVE NEGATIVE VALIDATION TEST REPORT\n\n";
        $md .= "**Application:** Laravel Taller Sistema  \n";
        $md .= "**URL:** {$this->baseUrl}  \n";
        $md .= "**Test Date:** {$data['executive_summary']['test_date']}  \n";
        $md .= "**Security Rating:** {$data['executive_summary']['overall_security_rating']}  \n\n";

        $md .= "## 📊 Executive Summary\n\n";
        $md .= "| Metric | Count |\n";
        $md .= "|--------|-------|\n";
        $md .= "| Modules Tested | {$data['executive_summary']['total_modules_tested']} |\n";
        $md .= "| Tests Executed | {$data['executive_summary']['total_tests_executed']} |\n";
        $md .= "| Critical Vulnerabilities | {$data['executive_summary']['critical_vulnerabilities']} |\n";
        $md .= "| High Risk Issues | {$data['executive_summary']['high_risk_issues']} |\n";
        $md .= "| Validation Bypasses | {$data['executive_summary']['validation_bypasses']} |\n";
        $md .= "| Passed Validations | {$data['executive_summary']['passed_validations']} |\n\n";

        if (!empty($data['vulnerabilities'])) {
            $md .= "## 🚨 Critical Security Vulnerabilities\n\n";
            foreach ($data['vulnerabilities'] as $vuln) {
                $md .= "### {$vuln['type']} - {$vuln['severity']}\n";
                $md .= "- **URL:** `{$vuln['url']}`\n";
                $md .= "- **Test:** {$vuln['test']}\n";
                $md .= "- **Description:** {$vuln['description']}\n\n";
            }
        }

        $md .= "## 📋 Module Test Results\n\n";
        foreach ($data['detailed_results'] as $module) {
            $md .= "### {$module['module']} Module\n\n";
            foreach ($module['critical_tests'] as $test) {
                $statusEmoji = match($test['status']) {
                    'CRITICAL' => '🔴',
                    'HIGH' => '🟠', 
                    'FAIL' => '🟡',
                    'PASS' => '🟢',
                    default => '⚪'
                };
                $md .= "- {$statusEmoji} **{$test['test_name']}** - {$test['status']}\n";
                if (!empty($test['security_issues'])) {
                    foreach ($test['security_issues'] as $issue) {
                        $md .= "  - ⚠️ {$issue}\n";
                    }
                }
            }
            $md .= "\n";
        }

        $md .= "## 🛠️ Recommendations\n\n";
        if (!empty($data['recommendations']['immediate_actions'])) {
            $md .= "### 🔴 Immediate Actions Required\n";
            foreach ($data['recommendations']['immediate_actions'] as $action) {
                $md .= "- {$action}\n";
            }
            $md .= "\n";
        }

        if (!empty($data['recommendations']['short_term_improvements'])) {
            $md .= "### 🟡 Short-term Improvements\n";
            foreach ($data['recommendations']['short_term_improvements'] as $improvement) {
                $md .= "- {$improvement}\n";
            }
            $md .= "\n";
        }

        if (!empty($data['recommendations']['long_term_security'])) {
            $md .= "### 🟢 Long-term Security Enhancements\n";
            foreach ($data['recommendations']['long_term_security'] as $enhancement) {
                $md .= "- {$enhancement}\n";
            }
        }

        file_put_contents('/mnt/c/Users/lukka/taller-sistema/COMPREHENSIVE_NEGATIVE_VALIDATION_REPORT.md', $md);
    }
}

// Execute the comprehensive negative validation tests
$tester = new ComprehensiveNegativeValidationTester();
$tester->runTests();

?>