<?php

/**
 * EXHAUSTIVE NEGATIVE VALIDATION TESTING SCRIPT
 * Testing all modules of the Laravel Taller Sistema application
 * URL: http://localhost:8003
 * Admin Credentials: admin@taller.com / admin123
 */

class ExhaustiveNegativeValidationTester
{
    private $baseUrl = 'http://localhost:8003';
    private $cookieFile = '/tmp/negative_test_cookies.txt';
    private $results = [];
    private $vulnerabilities = [];
    private $validationGaps = [];

    public function __construct()
    {
        // Initialize cURL cookie jar
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
        touch($this->cookieFile);
    }

    /**
     * Main test execution method
     */
    public function runTests()
    {
        echo "🔍 STARTING EXHAUSTIVE NEGATIVE VALIDATION TESTS\n";
        echo "================================================\n\n";

        // Login first
        if (!$this->login()) {
            echo "❌ Failed to login. Aborting tests.\n";
            return false;
        }

        // Run all module tests
        $this->testClientesModule();
        $this->testVehiculosModule();
        $this->testServiciosModule();
        $this->testEmpleadosModule();
        $this->testOrdenesModule();
        $this->testProfileModule();

        // Generate final report
        $this->generateReport();
        
        return true;
    }

    /**
     * Login to the application
     */
    private function login()
    {
        echo "🔐 Logging in...\n";
        
        // Get CSRF token from login page
        $loginPage = $this->makeRequest('GET', '/login');
        if (!$loginPage) {
            echo "❌ Failed to get login page\n";
            return false;
        }

        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $loginPage, $matches);
        $csrfToken = $matches[1] ?? '';
        
        if (!$csrfToken) {
            preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $loginPage, $matches);
            $csrfToken = $matches[1] ?? '';
        }

        if (!$csrfToken) {
            echo "❌ Could not extract CSRF token\n";
            return false;
        }

        // Perform login
        $loginData = [
            '_token' => $csrfToken,
            'email' => 'admin@taller.com',
            'password' => 'admin123'
        ];

        $response = $this->makeRequest('POST', '/login', $loginData);
        
        if (strpos($response, 'dashboard') !== false || strpos($response, 'Dashboard') !== false) {
            echo "✅ Login successful\n\n";
            return true;
        }

        echo "❌ Login failed\n";
        return false;
    }

    /**
     * Test CLIENTES module negative validation
     */
    private function testClientesModule()
    {
        echo "📋 TESTING CLIENTES MODULE NEGATIVE VALIDATION\n";
        echo "===========================================\n";

        $moduleResults = [
            'module' => 'CLIENTES',
            'tests' => [],
            'vulnerabilities' => [],
            'validation_gaps' => []
        ];

        // Get create form
        $createForm = $this->getCreateForm('/clientes/create');
        if (!$createForm) return;

        $csrfToken = $this->extractCsrfToken($createForm);

        // Test 1: Empty required fields
        echo "Test 1: Empty required fields\n";
        $testData = [
            '_token' => $csrfToken,
            'name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'document_number' => '',
            'status' => '1'
        ];
        $result = $this->testFormSubmission('/clientes', $testData, 'Empty required fields');
        $moduleResults['tests'][] = $result;

        // Test 2: Invalid email formats
        echo "Test 2: Invalid email formats\n";
        $invalidEmails = [
            'invalid-email',
            'test@',
            '@domain.com',
            'test..test@domain.com',
            'test@domain',
            'test@.com',
            'test email@domain.com',
            '<script>alert("xss")</script>@domain.com'
        ];

        foreach ($invalidEmails as $email) {
            $testData = [
                '_token' => $csrfToken,
                'name' => 'Test Cliente',
                'email' => $email,
                'phone' => '123456789',
                'address' => 'Test Address',
                'document_number' => 'DOC123456',
                'status' => '1'
            ];
            $result = $this->testFormSubmission('/clientes', $testData, "Invalid email: $email");
            $moduleResults['tests'][] = $result;
        }

        // Test 3: SQL Injection attempts
        echo "Test 3: SQL Injection attempts\n";
        $sqlPayloads = [
            "'; DROP TABLE clientes; --",
            "' OR '1'='1",
            "1' UNION SELECT * FROM users --",
            "'; INSERT INTO clientes VALUES ('hacked'); --",
            "' OR 1=1 --",
            "admin'--",
            "' OR 'x'='x",
            "1'; WAITFOR DELAY '00:00:05' --"
        ];

        foreach ($sqlPayloads as $payload) {
            $testData = [
                '_token' => $csrfToken,
                'name' => $payload,
                'email' => 'test@domain.com',
                'phone' => $payload,
                'address' => $payload,
                'document_number' => $payload,
                'status' => '1'
            ];
            $result = $this->testFormSubmission('/clientes', $testData, "SQL Injection: " . substr($payload, 0, 20) . "...");
            $moduleResults['tests'][] = $result;
        }

        // Test 4: XSS attempts
        echo "Test 4: XSS attempts\n";
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src="x" onerror="alert(\'XSS\')">',
            'javascript:alert("XSS")',
            '<svg onload="alert(\'XSS\')">',
            '"><script>alert("XSS")</script>',
            '<iframe src="javascript:alert(\'XSS\')"></iframe>',
            '<object data="javascript:alert(\'XSS\')"></object>',
            '<embed src="javascript:alert(\'XSS\')">',
            '<link rel="stylesheet" href="javascript:alert(\'XSS\')">',
            '<style>@import "javascript:alert(\'XSS\')"</style>'
        ];

        foreach ($xssPayloads as $payload) {
            $testData = [
                '_token' => $csrfToken,
                'name' => $payload,
                'email' => 'test@domain.com',
                'phone' => '123456789',
                'address' => $payload,
                'document_number' => 'DOC123',
                'status' => '1'
            ];
            $result = $this->testFormSubmission('/clientes', $testData, "XSS: " . substr($payload, 0, 20) . "...");
            $moduleResults['tests'][] = $result;
        }

        // Test 5: Excessively long strings
        echo "Test 5: Excessively long strings\n";
        $longString = str_repeat('A', 1000);
        $veryLongString = str_repeat('B', 10000);
        
        $testData = [
            '_token' => $csrfToken,
            'name' => $longString,
            'email' => 'test@domain.com',
            'phone' => $longString,
            'address' => $veryLongString,
            'document_number' => $longString,
            'status' => '1'
        ];
        $result = $this->testFormSubmission('/clientes', $testData, "Excessively long strings");
        $moduleResults['tests'][] = $result;

        // Test 6: Special characters and Unicode
        echo "Test 6: Special characters and Unicode\n";
        $specialChars = [
            '€∑∂ƒ©˙∆˚¬…æ',
            '™£¢∞§¶',
            '中文测试',
            'العربية',
            '🚗🔧⚙️',
            'null\x00byte',
            'carriage\rreturn',
            'line\nfeed',
            'tab\tcharacter'
        ];

        foreach ($specialChars as $chars) {
            $testData = [
                '_token' => $csrfToken,
                'name' => $chars,
                'email' => 'test@domain.com',
                'phone' => '123456789',
                'address' => $chars,
                'document_number' => 'DOC123',
                'status' => '1'
            ];
            $result = $this->testFormSubmission('/clientes', $testData, "Special chars: " . substr($chars, 0, 10) . "...");
            $moduleResults['tests'][] = $result;
        }

        // Test 7: Duplicate entries (if we can create one first)
        echo "Test 7: Testing duplicate validation\n";
        // First create a valid entry
        $validData = [
            '_token' => $csrfToken,
            'name' => 'Test Duplicate Cliente',
            'email' => 'duplicate@test.com',
            'phone' => '123456789',
            'address' => 'Test Address',
            'document_number' => 'DUPLICATE123',
            'status' => '1'
        ];
        $this->makeRequest('POST', '/clientes', $validData);

        // Now try to create duplicate
        $result = $this->testFormSubmission('/clientes', $validData, "Duplicate email and document");
        $moduleResults['tests'][] = $result;

        $this->results[] = $moduleResults;
        echo "✅ CLIENTES module tests completed\n\n";
    }

    /**
     * Test VEHICULOS module negative validation
     */
    private function testVehiculosModule()
    {
        echo "🚗 TESTING VEHICULOS MODULE NEGATIVE VALIDATION\n";
        echo "============================================\n";

        $moduleResults = [
            'module' => 'VEHICULOS',
            'tests' => [],
            'vulnerabilities' => [],
            'validation_gaps' => []
        ];

        $createForm = $this->getCreateForm('/vehiculos/create');
        if (!$createForm) return;

        $csrfToken = $this->extractCsrfToken($createForm);

        // Test 1: Without selecting client
        echo "Test 1: Without selecting client\n";
        $testData = [
            '_token' => $csrfToken,
            'cliente_id' => '',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => '2020',
            'license_plate' => 'ABC123',
            'vin' => 'VIN123456789',
            'color' => 'Red',
            'status' => '1'
        ];
        $result = $this->testFormSubmission('/vehiculos', $testData, 'Empty client_id');
        $moduleResults['tests'][] = $result;

        // Test 2: Non-existent client_id
        echo "Test 2: Non-existent client_id\n";
        $testData['cliente_id'] = '99999';
        $result = $this->testFormSubmission('/vehiculos', $testData, 'Non-existent client_id');
        $moduleResults['tests'][] = $result;

        // Test 3: Invalid years
        echo "Test 3: Invalid years\n";
        $invalidYears = [
            '1800', // Too old
            '2050', // Future year
            '0',
            '-2020',
            'abc',
            '20.5',
            '2020.5'
        ];

        foreach ($invalidYears as $year) {
            $testData = [
                '_token' => $csrfToken,
                'cliente_id' => '1',
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'year' => $year,
                'license_plate' => 'ABC' . rand(100, 999),
                'vin' => 'VIN' . rand(100000, 999999),
                'color' => 'Red',
                'status' => '1'
            ];
            $result = $this->testFormSubmission('/vehiculos', $testData, "Invalid year: $year");
            $moduleResults['tests'][] = $result;
        }

        // Test 4: SQL Injection in vehicle fields
        echo "Test 4: SQL Injection attempts\n";
        $sqlPayloads = [
            "'; DROP TABLE vehiculos; --",
            "' OR '1'='1",
            "1' UNION SELECT * FROM users --"
        ];

        foreach ($sqlPayloads as $payload) {
            $testData = [
                '_token' => $csrfToken,
                'cliente_id' => '1',
                'brand' => $payload,
                'model' => $payload,
                'year' => '2020',
                'license_plate' => 'ABC123',
                'vin' => 'VIN123456',
                'color' => $payload,
                'status' => '1'
            ];
            $result = $this->testFormSubmission('/vehiculos', $testData, "SQL Injection: " . substr($payload, 0, 20));
            $moduleResults['tests'][] = $result;
        }

        // Test 5: Duplicate license plates and VINs
        echo "Test 5: Duplicate license plates and VINs\n";
        $duplicateData = [
            '_token' => $csrfToken,
            'cliente_id' => '1',
            'brand' => 'Honda',
            'model' => 'Civic',
            'year' => '2019',
            'license_plate' => 'DUPLICATE123',
            'vin' => 'DUPLICATEVIN123',
            'color' => 'Blue',
            'status' => '1'
        ];
        
        // Create first entry
        $this->makeRequest('POST', '/vehiculos', $duplicateData);
        
        // Try to create duplicate
        $result = $this->testFormSubmission('/vehiculos', $duplicateData, "Duplicate license plate and VIN");
        $moduleResults['tests'][] = $result;

        $this->results[] = $moduleResults;
        echo "✅ VEHICULOS module tests completed\n\n";
    }

    /**
     * Test SERVICIOS module negative validation
     */
    private function testServiciosModule()
    {
        echo "🔧 TESTING SERVICIOS MODULE NEGATIVE VALIDATION\n";
        echo "============================================\n";

        $moduleResults = [
            'module' => 'SERVICIOS',
            'tests' => [],
            'vulnerabilities' => [],
            'validation_gaps' => []
        ];

        $createForm = $this->getCreateForm('/servicios/create');
        if (!$createForm) return;

        $csrfToken = $this->extractCsrfToken($createForm);

        // Test 1: Negative prices
        echo "Test 1: Negative prices\n";
        $testData = [
            '_token' => $csrfToken,
            'name' => 'Test Service',
            'description' => 'Test Description',
            'price' => '-100',
            'duration_hours' => '2',
            'status' => '1'
        ];
        $result = $this->testFormSubmission('/servicios', $testData, 'Negative price');
        $moduleResults['tests'][] = $result;

        // Test 2: Zero prices
        echo "Test 2: Zero prices\n";
        $testData['price'] = '0';
        $result = $this->testFormSubmission('/servicios', $testData, 'Zero price');
        $moduleResults['tests'][] = $result;

        // Test 3: Invalid price formats
        echo "Test 3: Invalid price formats\n";
        $invalidPrices = [
            'abc',
            '10.5.5',
            '$100',
            '100,50',
            'price',
            '1e10',
            'null',
            'undefined'
        ];

        foreach ($invalidPrices as $price) {
            $testData = [
                '_token' => $csrfToken,
                'name' => 'Test Service',
                'description' => 'Test Description',
                'price' => $price,
                'duration_hours' => '2',
                'status' => '1'
            ];
            $result = $this->testFormSubmission('/servicios', $testData, "Invalid price format: $price");
            $moduleResults['tests'][] = $result;
        }

        // Test 4: Extremely high prices
        echo "Test 4: Extremely high prices\n";
        $testData = [
            '_token' => $csrfToken,
            'name' => 'Test Service',
            'description' => 'Test Description',
            'price' => '999999999999999',
            'duration_hours' => '2',
            'status' => '1'
        ];
        $result = $this->testFormSubmission('/servicios', $testData, 'Extremely high price');
        $moduleResults['tests'][] = $result;

        // Test 5: Invalid duration formats
        echo "Test 5: Invalid duration formats\n";
        $invalidDurations = [
            '-5',
            'abc',
            '2.5.5',
            'two hours',
            'null',
            '∞'
        ];

        foreach ($invalidDurations as $duration) {
            $testData = [
                '_token' => $csrfToken,
                'name' => 'Test Service',
                'description' => 'Test Description',
                'price' => '100',
                'duration_hours' => $duration,
                'status' => '1'
            ];
            $result = $this->testFormSubmission('/servicios', $testData, "Invalid duration: $duration");
            $moduleResults['tests'][] = $result;
        }

        // Test 6: Empty service names
        echo "Test 6: Empty service names\n";
        $testData = [
            '_token' => $csrfToken,
            'name' => '',
            'description' => 'Test Description',
            'price' => '100',
            'duration_hours' => '2',
            'status' => '1'
        ];
        $result = $this->testFormSubmission('/servicios', $testData, 'Empty service name');
        $moduleResults['tests'][] = $result;

        // Test 7: XSS in service fields
        echo "Test 7: XSS in service fields\n";
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src="x" onerror="alert(\'XSS\')">',
            'javascript:alert("XSS")'
        ];

        foreach ($xssPayloads as $payload) {
            $testData = [
                '_token' => $csrfToken,
                'name' => $payload,
                'description' => $payload,
                'price' => '100',
                'duration_hours' => '2',
                'status' => '1'
            ];
            $result = $this->testFormSubmission('/servicios', $testData, "XSS: " . substr($payload, 0, 20));
            $moduleResults['tests'][] = $result;
        }

        $this->results[] = $moduleResults;
        echo "✅ SERVICIOS module tests completed\n\n";
    }

    /**
     * Test EMPLEADOS module negative validation
     */
    private function testEmpleadosModule()
    {
        echo "👥 TESTING EMPLEADOS MODULE NEGATIVE VALIDATION\n";
        echo "============================================\n";

        $moduleResults = [
            'module' => 'EMPLEADOS',
            'tests' => [],
            'vulnerabilities' => [],
            'validation_gaps' => []
        ];

        $createForm = $this->getCreateForm('/empleados/create');
        if (!$createForm) return;

        $csrfToken = $this->extractCsrfToken($createForm);

        // Test 1: Invalid salary amounts
        echo "Test 1: Invalid salary amounts\n";
        $invalidSalaries = [
            '-1000', // Negative
            '0',     // Zero
            '999999999999999', // Extremely high
            'abc',
            '$1000',
            '1000,50'
        ];

        foreach ($invalidSalaries as $salary) {
            $testData = [
                '_token' => $csrfToken,
                'name' => 'Test Employee',
                'email' => 'test' . rand(1000, 9999) . '@test.com',
                'phone' => '123456789',
                'position' => 'Mechanic',
                'salary' => $salary,
                'hire_date' => '2023-01-01',
                'status' => '1'
            ];
            $result = $this->testFormSubmission('/empleados', $testData, "Invalid salary: $salary");
            $moduleResults['tests'][] = $result;
        }

        // Test 2: Future hire dates
        echo "Test 2: Future hire dates\n";
        $futureDate = date('Y-m-d', strtotime('+1 year'));
        $testData = [
            '_token' => $csrfToken,
            'name' => 'Test Employee',
            'email' => 'future@test.com',
            'phone' => '123456789',
            'position' => 'Mechanic',
            'salary' => '3000',
            'hire_date' => $futureDate,
            'status' => '1'
        ];
        $result = $this->testFormSubmission('/empleados', $testData, 'Future hire date');
        $moduleResults['tests'][] = $result;

        // Test 3: Duplicate emails
        echo "Test 3: Duplicate emails\n";
        $duplicateData = [
            '_token' => $csrfToken,
            'name' => 'Test Employee Duplicate',
            'email' => 'duplicate.employee@test.com',
            'phone' => '123456789',
            'position' => 'Mechanic',
            'salary' => '3000',
            'hire_date' => '2023-01-01',
            'status' => '1'
        ];
        
        // Create first entry
        $this->makeRequest('POST', '/empleados', $duplicateData);
        
        // Try to create duplicate
        $result = $this->testFormSubmission('/empleados', $duplicateData, 'Duplicate email');
        $moduleResults['tests'][] = $result;

        // Test 4: Invalid phone formats
        echo "Test 4: Invalid phone formats\n";
        $invalidPhones = [
            '', // Empty
            '123', // Too short
            str_repeat('1', 50), // Too long
            'phone-number',
            '123-abc-7890'
        ];

        foreach ($invalidPhones as $phone) {
            $testData = [
                '_token' => $csrfToken,
                'name' => 'Test Employee',
                'email' => 'test' . rand(1000, 9999) . '@test.com',
                'phone' => $phone,
                'position' => 'Mechanic',
                'salary' => '3000',
                'hire_date' => '2023-01-01',
                'status' => '1'
            ];
            $result = $this->testFormSubmission('/empleados', $testData, "Invalid phone: $phone");
            $moduleResults['tests'][] = $result;
        }

        // Test 5: Empty required fields
        echo "Test 5: Empty required fields\n";
        $testData = [
            '_token' => $csrfToken,
            'name' => '',
            'email' => '',
            'phone' => '',
            'position' => '',
            'salary' => '',
            'hire_date' => '',
            'status' => '1'
        ];
        $result = $this->testFormSubmission('/empleados', $testData, 'All empty required fields');
        $moduleResults['tests'][] = $result;

        $this->results[] = $moduleResults;
        echo "✅ EMPLEADOS module tests completed\n\n";
    }

    /**
     * Test ORDENES module negative validation
     */
    private function testOrdenesModule()
    {
        echo "📋 TESTING ORDENES MODULE NEGATIVE VALIDATION\n";
        echo "==========================================\n";

        $moduleResults = [
            'module' => 'ORDENES',
            'tests' => [],
            'vulnerabilities' => [],
            'validation_gaps' => []
        ];

        $createForm = $this->getCreateForm('/ordenes/create');
        if (!$createForm) return;

        $csrfToken = $this->extractCsrfToken($createForm);

        // Test 1: Without selecting required foreign keys
        echo "Test 1: Missing required foreign keys\n";
        $testData = [
            '_token' => $csrfToken,
            'cliente_id' => '',
            'vehiculo_id' => '',
            'empleado_id' => '',
            'servicio_id' => '',
            'description' => 'Test Description',
            'status' => 'pending',
            'total_amount' => '100',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02'
        ];
        $result = $this->testFormSubmission('/ordenes', $testData, 'Missing all foreign keys');
        $moduleResults['tests'][] = $result;

        // Test 2: Non-existent foreign key references
        echo "Test 2: Non-existent foreign key references\n";
        $testData = [
            '_token' => $csrfToken,
            'cliente_id' => '99999',
            'vehiculo_id' => '99999',
            'empleado_id' => '99999',
            'servicio_id' => '99999',
            'description' => 'Test Description',
            'status' => 'pending',
            'total_amount' => '100',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02'
        ];
        $result = $this->testFormSubmission('/ordenes', $testData, 'Non-existent foreign keys');
        $moduleResults['tests'][] = $result;

        // Test 3: Invalid date combinations
        echo "Test 3: Invalid date combinations\n";
        $testData = [
            '_token' => $csrfToken,
            'cliente_id' => '1',
            'vehiculo_id' => '1',
            'empleado_id' => '1',
            'servicio_id' => '1',
            'description' => 'Test Description',
            'status' => 'pending',
            'total_amount' => '100',
            'start_date' => '2023-01-02',
            'end_date' => '2023-01-01' // End before start
        ];
        $result = $this->testFormSubmission('/ordenes', $testData, 'End date before start date');
        $moduleResults['tests'][] = $result;

        // Test 4: Negative amounts
        echo "Test 4: Negative amounts\n";
        $testData = [
            '_token' => $csrfToken,
            'cliente_id' => '1',
            'vehiculo_id' => '1',
            'empleado_id' => '1',
            'servicio_id' => '1',
            'description' => 'Test Description',
            'status' => 'pending',
            'total_amount' => '-100',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02'
        ];
        $result = $this->testFormSubmission('/ordenes', $testData, 'Negative total amount');
        $moduleResults['tests'][] = $result;

        // Test 5: Invalid status values
        echo "Test 5: Invalid status values\n";
        $invalidStatuses = [
            'invalid_status',
            'PENDING', // Wrong case
            'completed_wrong',
            '1',
            'true'
        ];

        foreach ($invalidStatuses as $status) {
            $testData = [
                '_token' => $csrfToken,
                'cliente_id' => '1',
                'vehiculo_id' => '1',
                'empleado_id' => '1',
                'servicio_id' => '1',
                'description' => 'Test Description',
                'status' => $status,
                'total_amount' => '100',
                'start_date' => '2023-01-01',
                'end_date' => '2023-01-02'
            ];
            $result = $this->testFormSubmission('/ordenes', $testData, "Invalid status: $status");
            $moduleResults['tests'][] = $result;
        }

        $this->results[] = $moduleResults;
        echo "✅ ORDENES module tests completed\n\n";
    }

    /**
     * Test PROFILE module negative validation
     */
    private function testProfileModule()
    {
        echo "👤 TESTING PROFILE MODULE NEGATIVE VALIDATION\n";
        echo "==========================================\n";

        $moduleResults = [
            'module' => 'PROFILE',
            'tests' => [],
            'vulnerabilities' => [],
            'validation_gaps' => []
        ];

        $profilePage = $this->makeRequest('GET', '/profile');
        if (!$profilePage) {
            echo "❌ Failed to get profile page\n";
            return;
        }

        $csrfToken = $this->extractCsrfToken($profilePage);

        // Test 1: Empty name fields
        echo "Test 1: Empty name fields\n";
        $testData = [
            '_token' => $csrfToken,
            'name' => '',
            'email' => 'admin@taller.com'
        ];
        $result = $this->testFormSubmission('/profile', $testData, 'Empty name field', 'PATCH');
        $moduleResults['tests'][] = $result;

        // Test 2: Invalid email changes
        echo "Test 2: Invalid email changes\n";
        $invalidEmails = [
            'invalid-email',
            'test@',
            '@domain.com',
            'admin@taller.com@hack.com'
        ];

        foreach ($invalidEmails as $email) {
            $testData = [
                '_token' => $csrfToken,
                'name' => 'Admin User',
                'email' => $email
            ];
            $result = $this->testFormSubmission('/profile', $testData, "Invalid email: $email", 'PATCH');
            $moduleResults['tests'][] = $result;
        }

        // Test 3: Password update with weak passwords
        echo "Test 3: Weak passwords\n";
        $weakPasswords = [
            '123',
            'password',
            '12345678',
            'admin',
            'qwerty'
        ];

        foreach ($weakPasswords as $password) {
            $testData = [
                '_token' => $csrfToken,
                'current_password' => 'admin123',
                'password' => $password,
                'password_confirmation' => $password
            ];
            $result = $this->testFormSubmission('/password', $testData, "Weak password: $password", 'PUT');
            $moduleResults['tests'][] = $result;
        }

        // Test 4: Password confirmation mismatches
        echo "Test 4: Password confirmation mismatches\n";
        $testData = [
            '_token' => $csrfToken,
            'current_password' => 'admin123',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword123!'
        ];
        $result = $this->testFormSubmission('/password', $testData, 'Password confirmation mismatch', 'PUT');
        $moduleResults['tests'][] = $result;

        $this->results[] = $moduleResults;
        echo "✅ PROFILE module tests completed\n\n";
    }

    /**
     * Get create form and extract CSRF token
     */
    private function getCreateForm($url)
    {
        $form = $this->makeRequest('GET', $url);
        if (!$form) {
            echo "❌ Failed to get create form from $url\n";
            return null;
        }
        return $form;
    }

    /**
     * Extract CSRF token from form
     */
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

    /**
     * Test form submission with validation
     */
    private function testFormSubmission($url, $data, $testName, $method = 'POST')
    {
        echo "  → Testing: $testName\n";
        
        $response = $this->makeRequest($method, $url, $data);
        
        $result = [
            'test_name' => $testName,
            'url' => $url,
            'method' => $method,
            'data' => $data,
            'response_received' => !empty($response),
            'validation_errors' => [],
            'security_issues' => [],
            'http_status' => 'unknown'
        ];

        if ($response) {
            // Check for validation errors
            if (strpos($response, 'error') !== false || strpos($response, 'invalid') !== false) {
                $result['validation_errors'][] = 'Form validation triggered';
            }
            
            // Check for successful submission (which would be bad for negative tests)
            if (strpos($response, 'success') !== false || strpos($response, 'created') !== false) {
                $result['security_issues'][] = 'Form accepted invalid data';
                $this->vulnerabilities[] = [
                    'type' => 'Validation Bypass',
                    'test' => $testName,
                    'url' => $url,
                    'description' => 'Form accepted data that should have been rejected'
                ];
            }
            
            // Check for SQL injection indicators
            if (strpos($response, 'SQL') !== false || strpos($response, 'mysql') !== false || strpos($response, 'database') !== false) {
                $result['security_issues'][] = 'Possible SQL injection vulnerability';
                $this->vulnerabilities[] = [
                    'type' => 'SQL Injection',
                    'test' => $testName,
                    'url' => $url,
                    'description' => 'Response contains database error information'
                ];
            }
            
            // Check for XSS reflection
            foreach ($data as $key => $value) {
                if (is_string($value) && strpos($value, '<script>') !== false && strpos($response, $value) !== false) {
                    $result['security_issues'][] = 'XSS payload reflected in response';
                    $this->vulnerabilities[] = [
                        'type' => 'XSS Reflection',
                        'test' => $testName,
                        'url' => $url,
                        'field' => $key,
                        'description' => 'Malicious script reflected in response'
                    ];
                }
            }
            
            // Check for 500 errors
            if (strpos($response, '500') !== false || strpos($response, 'Internal Server Error') !== false) {
                $result['security_issues'][] = 'Server error (500)';
                $this->vulnerabilities[] = [
                    'type' => 'Server Error',
                    'test' => $testName,
                    'url' => $url,
                    'description' => 'Request caused internal server error'
                ];
            }
        }

        return $result;
    }

    /**
     * Make HTTP request
     */
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
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Negative Validation Tester 1.0',
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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            echo "❌ cURL Error: " . curl_error($ch) . "\n";
        }
        
        curl_close($ch);
        
        return $response;
    }

    /**
     * Generate comprehensive test report
     */
    private function generateReport()
    {
        echo "📊 GENERATING COMPREHENSIVE TEST REPORT\n";
        echo "=====================================\n\n";

        $report = [
            'test_summary' => [
                'total_modules_tested' => count($this->results),
                'total_tests_executed' => 0,
                'total_vulnerabilities_found' => count($this->vulnerabilities),
                'test_date' => date('Y-m-d H:i:s')
            ],
            'modules' => $this->results,
            'vulnerabilities' => $this->vulnerabilities,
            'validation_gaps' => $this->validationGaps,
            'recommendations' => []
        ];

        // Count total tests
        foreach ($this->results as $module) {
            $report['test_summary']['total_tests_executed'] += count($module['tests']);
        }

        // Generate recommendations
        $report['recommendations'] = $this->generateRecommendations();

        // Save report to file
        $reportFile = '/mnt/c/Users/lukka/taller-sistema/EXHAUSTIVE_NEGATIVE_VALIDATION_REPORT.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));

        // Generate markdown report
        $this->generateMarkdownReport($report);

        // Display summary
        echo "📈 TEST EXECUTION SUMMARY:\n";
        echo "- Modules Tested: " . $report['test_summary']['total_modules_tested'] . "\n";
        echo "- Total Tests: " . $report['test_summary']['total_tests_executed'] . "\n";
        echo "- Vulnerabilities Found: " . $report['test_summary']['total_vulnerabilities_found'] . "\n";
        echo "- Report saved to: $reportFile\n";
        
        if (count($this->vulnerabilities) > 0) {
            echo "\n⚠️  CRITICAL SECURITY ISSUES FOUND:\n";
            foreach ($this->vulnerabilities as $vuln) {
                echo "- " . $vuln['type'] . " in " . $vuln['url'] . ": " . $vuln['description'] . "\n";
            }
        }

        echo "\n✅ EXHAUSTIVE NEGATIVE VALIDATION TESTING COMPLETED\n";
    }

    /**
     * Generate recommendations based on findings
     */
    private function generateRecommendations()
    {
        $recommendations = [
            'high_priority' => [],
            'medium_priority' => [],
            'low_priority' => []
        ];

        // Analyze vulnerabilities and generate recommendations
        foreach ($this->vulnerabilities as $vuln) {
            switch ($vuln['type']) {
                case 'SQL Injection':
                    $recommendations['high_priority'][] = 'Implement parameterized queries and input sanitization for all database operations';
                    break;
                case 'XSS Reflection':
                    $recommendations['high_priority'][] = 'Implement proper output encoding and Content Security Policy (CSP)';
                    break;
                case 'Validation Bypass':
                    $recommendations['medium_priority'][] = 'Strengthen server-side validation rules';
                    break;
                case 'Server Error':
                    $recommendations['medium_priority'][] = 'Implement proper error handling to prevent information disclosure';
                    break;
            }
        }

        // Add general recommendations
        $recommendations['medium_priority'][] = 'Implement rate limiting for form submissions';
        $recommendations['medium_priority'][] = 'Add CAPTCHA for sensitive operations';
        $recommendations['low_priority'][] = 'Implement comprehensive logging for security events';
        $recommendations['low_priority'][] = 'Regular security audits and penetration testing';

        return $recommendations;
    }

    /**
     * Generate markdown report
     */
    private function generateMarkdownReport($data)
    {
        $markdown = "# EXHAUSTIVE NEGATIVE VALIDATION TEST REPORT\n\n";
        $markdown .= "**Test Date:** " . $data['test_summary']['test_date'] . "\n";
        $markdown .= "**Application:** Laravel Taller Sistema (http://localhost:8003)\n\n";

        $markdown .= "## Executive Summary\n\n";
        $markdown .= "- **Total Modules Tested:** " . $data['test_summary']['total_modules_tested'] . "\n";
        $markdown .= "- **Total Tests Executed:** " . $data['test_summary']['total_tests_executed'] . "\n";
        $markdown .= "- **Critical Vulnerabilities Found:** " . $data['test_summary']['total_vulnerabilities_found'] . "\n\n";

        if (count($data['vulnerabilities']) > 0) {
            $markdown .= "## 🚨 CRITICAL SECURITY FINDINGS\n\n";
            foreach ($data['vulnerabilities'] as $vuln) {
                $markdown .= "### " . $vuln['type'] . "\n";
                $markdown .= "- **URL:** `" . $vuln['url'] . "`\n";
                $markdown .= "- **Test:** " . $vuln['test'] . "\n";
                $markdown .= "- **Description:** " . $vuln['description'] . "\n\n";
            }
        }

        $markdown .= "## Module Test Results\n\n";
        foreach ($data['modules'] as $module) {
            $markdown .= "### " . $module['module'] . " Module\n\n";
            $markdown .= "**Tests Executed:** " . count($module['tests']) . "\n\n";
            
            foreach ($module['tests'] as $test) {
                $markdown .= "#### " . $test['test_name'] . "\n";
                $markdown .= "- **URL:** `" . $test['url'] . "`\n";
                $markdown .= "- **Method:** " . $test['method'] . "\n";
                if (!empty($test['validation_errors'])) {
                    $markdown .= "- **Validation Errors:** " . implode(', ', $test['validation_errors']) . "\n";
                }
                if (!empty($test['security_issues'])) {
                    $markdown .= "- **⚠️ Security Issues:** " . implode(', ', $test['security_issues']) . "\n";
                }
                $markdown .= "\n";
            }
        }

        $markdown .= "## Recommendations\n\n";
        if (!empty($data['recommendations']['high_priority'])) {
            $markdown .= "### 🔴 High Priority\n";
            foreach ($data['recommendations']['high_priority'] as $rec) {
                $markdown .= "- " . $rec . "\n";
            }
            $markdown .= "\n";
        }

        if (!empty($data['recommendations']['medium_priority'])) {
            $markdown .= "### 🟡 Medium Priority\n";
            foreach ($data['recommendations']['medium_priority'] as $rec) {
                $markdown .= "- " . $rec . "\n";
            }
            $markdown .= "\n";
        }

        if (!empty($data['recommendations']['low_priority'])) {
            $markdown .= "### 🟢 Low Priority\n";
            foreach ($data['recommendations']['low_priority'] as $rec) {
                $markdown .= "- " . $rec . "\n";
            }
        }

        $reportFile = '/mnt/c/Users/lukka/taller-sistema/EXHAUSTIVE_NEGATIVE_VALIDATION_REPORT.md';
        file_put_contents($reportFile, $markdown);
    }
}

// Execute the tests
$tester = new ExhaustiveNegativeValidationTester();
$tester->runTests();

?>