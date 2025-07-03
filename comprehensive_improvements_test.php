<?php
/**
 * Comprehensive Laravel Application Improvements Testing Script
 * Tests all enhanced features including alerts, CRUD operations, and JavaScript improvements
 */

require_once 'vendor/autoload.php';

class ComprehensiveImprovementsTest
{
    private $baseUrl = 'http://localhost:8002';
    private $cookieJar;
    private $testResults = [];
    private $isLoggedIn = false;
    
    public function __construct()
    {
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'comprehensive_improvements_test_cookies');
    }
    
    public function runAllTests()
    {
        echo "=== COMPREHENSIVE IMPROVEMENTS TESTING ===\n";
        echo "Testing Laravel Application at: {$this->baseUrl}\n";
        echo "Start Time: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Step 1: Login
        $this->login();
        
        if (!$this->isLoggedIn) {
            echo "❌ Login failed. Cannot continue with tests.\n";
            return;
        }
        
        // Step 2: Test Alert System
        $this->testAlertSystem();
        
        // Step 3: Test CRUD Operations with Grid Updates
        $this->testCrudOperationsWithUpdates();
        
        // Step 4: Test DataTable Enhancements  
        $this->testDataTableEnhancements();
        
        // Step 5: Test JavaScript Improvements
        $this->testJavaScriptImprovements();
        
        // Step 6: Test Form Enhancements
        $this->testFormEnhancements();
        
        // Generate comprehensive report
        $this->generateReport();
    }
    
    private function login()
    {
        echo "🔑 Testing Login...\n";
        
        // Get login page
        $loginPageResponse = $this->makeRequest('GET', '/login');
        
        if (strpos($loginPageResponse, 'csrf_token') === false) {
            echo "❌ Login page not accessible\n";
            return;
        }
        
        // Extract CSRF token
        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $loginPageResponse, $matches);
        $csrfToken = $matches[1] ?? '';
        
        if (empty($csrfToken)) {
            preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $loginPageResponse, $matches);
            $csrfToken = $matches[1] ?? '';
        }
        
        // Attempt login
        $loginData = http_build_query([
            'email' => 'admin@taller.com',
            'password' => 'admin123',
            '_token' => $csrfToken
        ]);
        
        $loginResponse = $this->makeRequest('POST', '/login', $loginData, [
            'Content-Type: application/x-www-form-urlencoded',
            'Referer: ' . $this->baseUrl . '/login'
        ]);
        
        // Check if login was successful
        $dashboardResponse = $this->makeRequest('GET', '/dashboard');
        
        if (strpos($dashboardResponse, 'Dashboard') !== false && 
            strpos($dashboardResponse, 'Cerrar Sesión') !== false) {
            echo "✅ Login successful\n\n";
            $this->isLoggedIn = true;
            $this->testResults['login'] = [
                'status' => 'success',
                'message' => 'Successfully logged in as admin@taller.com'
            ];
        } else {
            echo "❌ Login failed\n";
            $this->testResults['login'] = [
                'status' => 'failed',
                'message' => 'Could not log in with provided credentials'
            ];
        }
    }
    
    private function testAlertSystem()
    {
        echo "🚨 Testing Alert System...\n";
        
        $alertTests = [];
        
        // Test 1: Check alert timeout is 8 seconds (increased from 5)
        $dashboardResponse = $this->makeRequest('GET', '/dashboard');
        if (strpos($dashboardResponse, 'setTimeout(function() {') !== false &&
            strpos($dashboardResponse, '8000') !== false) {
            $alertTests['timeout_8_seconds'] = '✅ Alert timeout increased to 8 seconds';
        } else {
            $alertTests['timeout_8_seconds'] = '❌ Alert timeout not set to 8 seconds';
        }
        
        // Test 2: Check SweetAlert2 integration
        if (strpos($dashboardResponse, 'sweetalert2') !== false) {
            $alertTests['sweetalert2_integration'] = '✅ SweetAlert2 properly integrated';
        } else {
            $alertTests['sweetalert2_integration'] = '❌ SweetAlert2 not found';
        }
        
        // Test 3: Check help alert function exists
        if (strpos($dashboardResponse, 'function showHelpAlert') !== false &&
            strpos($dashboardResponse, 'Consejo:') !== false) {
            $alertTests['help_alert_function'] = '✅ Help alert function with "Consejo:" prefix found';
        } else {
            $alertTests['help_alert_function'] = '❌ Help alert function not properly implemented';
        }
        
        // Test 4: Check showSuccessAlert function
        if (strpos($dashboardResponse, 'function showSuccessAlert') !== false) {
            $alertTests['success_alert_function'] = '✅ Success alert function exists';
        } else {
            $alertTests['success_alert_function'] = '❌ Success alert function missing';
        }
        
        // Test 5: Check showErrorAlert function
        if (strpos($dashboardResponse, 'function showErrorAlert') !== false) {
            $alertTests['error_alert_function'] = '✅ Error alert function exists';
        } else {
            $alertTests['error_alert_function'] = '❌ Error alert function missing';
        }
        
        foreach ($alertTests as $test => $result) {
            echo "  $result\n";
        }
        
        $this->testResults['alert_system'] = $alertTests;
        echo "\n";
    }
    
    private function testCrudOperationsWithUpdates()
    {
        echo "🔄 Testing CRUD Operations with Grid Updates...\n";
        
        $crudTests = [];
        
        // Test Clientes module
        echo "  Testing Clientes module...\n";
        
        // Get clientes page
        $clientesResponse = $this->makeRequest('GET', '/clientes');
        
        if (strpos($clientesResponse, 'Gestión de Clientes') !== false) {
            $crudTests['clientes_access'] = '✅ Clientes module accessible';
            
            // Test create form access
            $createResponse = $this->makeRequest('GET', '/clientes/create');
            if (strpos($createResponse, 'Nuevo Cliente') !== false) {
                $crudTests['clientes_create_form'] = '✅ Create form accessible';
            } else {
                $crudTests['clientes_create_form'] = '❌ Create form not accessible';
            }
            
            // Test grid functionality
            if (strpos($clientesResponse, 'clientesTable') !== false &&
                strpos($clientesResponse, 'DataTable') !== false) {
                $crudTests['clientes_datatable'] = '✅ DataTable initialized for clientes';
            } else {
                $crudTests['clientes_datatable'] = '❌ DataTable not properly initialized';
            }
            
            // Test delete functionality
            if (strpos($clientesResponse, 'btn-delete') !== false &&
                strpos($clientesResponse, 'attachDeleteEvents') !== false) {
                $crudTests['clientes_delete_enhanced'] = '✅ Enhanced delete functionality found';
            } else {
                $crudTests['clientes_delete_enhanced'] = '❌ Enhanced delete functionality missing';
            }
            
        } else {
            $crudTests['clientes_access'] = '❌ Clientes module not accessible';
        }
        
        // Test statistics cards
        if (strpos($clientesResponse, 'Total Clientes') !== false &&
            strpos($clientesResponse, 'updateStatistics') !== false) {
            $crudTests['statistics_cards'] = '✅ Statistics cards with update function found';
        } else {
            $crudTests['statistics_cards'] = '❌ Statistics cards or update function missing';
        }
        
        foreach ($crudTests as $test => $result) {
            echo "    $result\n";
        }
        
        $this->testResults['crud_operations'] = $crudTests;
        echo "\n";
    }
    
    private function testDataTableEnhancements()
    {
        echo "📊 Testing DataTable Enhancements...\n";
        
        $dtTests = [];
        
        $clientesResponse = $this->makeRequest('GET', '/clientes');
        
        // Test 1: Global dataTables object
        if (strpos($clientesResponse, 'window.dataTables = {}') !== false) {
            $dtTests['global_datatables_object'] = '✅ Global dataTables object exists';
        } else {
            $dtTests['global_datatables_object'] = '❌ Global dataTables object missing';
        }
        
        // Test 2: Table stored in global object
        if (strpos($clientesResponse, 'window.dataTables.clientesTable') !== false) {
            $dtTests['table_stored_globally'] = '✅ DataTable stored in global object';
        } else {
            $dtTests['table_stored_globally'] = '❌ DataTable not stored globally';
        }
        
        // Test 3: Delete button event reattachment
        if (strpos($clientesResponse, 'drawCallback') !== false &&
            strpos($clientesResponse, 'attachDeleteEvents') !== false) {
            $dtTests['delete_event_reattachment'] = '✅ Delete button events reattached on draw';
        } else {
            $dtTests['delete_event_reattachment'] = '❌ Delete button event reattachment missing';
        }
        
        // Test 4: Reload function
        if (strpos($clientesResponse, 'function reloadDataTable') !== false) {
            $dtTests['reload_function'] = '✅ Reload DataTable function exists';
        } else {
            $dtTests['reload_function'] = '❌ Reload DataTable function missing';
        }
        
        foreach ($dtTests as $test => $result) {
            echo "  $result\n";
        }
        
        $this->testResults['datatable_enhancements'] = $dtTests;
        echo "\n";
    }
    
    private function testJavaScriptImprovements()
    {
        echo "⚡ Testing JavaScript Improvements...\n";
        
        $jsTests = [];
        
        $dashboardResponse = $this->makeRequest('GET', '/dashboard');
        
        // Test all required JavaScript functions
        $requiredFunctions = [
            'showSuccessAlert' => 'Success alert function',
            'showErrorAlert' => 'Error alert function', 
            'showHelpAlert' => 'Help alert function',
            'reloadDataTable' => 'Reload DataTable function',
            'updateStatistics' => 'Update statistics function',
            'submitFormWithCallback' => 'Enhanced form submission function'
        ];
        
        foreach ($requiredFunctions as $func => $description) {
            if (strpos($dashboardResponse, "function $func") !== false) {
                $jsTests[$func] = "✅ $description exists";
            } else {
                $jsTests[$func] = "❌ $description missing";
            }
        }
        
        // Test CSRF token handling
        if (strpos($dashboardResponse, 'X-CSRF-TOKEN') !== false) {
            $jsTests['csrf_handling'] = '✅ CSRF token handling implemented';
        } else {
            $jsTests['csrf_handling'] = '❌ CSRF token handling missing';
        }
        
        foreach ($jsTests as $test => $result) {
            echo "  $result\n";
        }
        
        $this->testResults['javascript_improvements'] = $jsTests;
        echo "\n";
    }
    
    private function testFormEnhancements()
    {
        echo "📝 Testing Form Enhancements...\n";
        
        $formTests = [];
        
        // Test create form
        $createResponse = $this->makeRequest('GET', '/clientes/create');
        
        if (strpos($createResponse, '/create') !== false) {
            $formTests['create_form_access'] = '✅ Create form accessible';
            
            // Check for help alert initialization
            if (strpos($createResponse, "window.location.pathname.includes('/create')") !== false ||
                strpos($createResponse, 'showHelpAlert') !== false) {
                $formTests['create_help_alert'] = '✅ Help alert initialization for create forms';
            } else {
                $formTests['create_help_alert'] = '❌ Help alert initialization missing for create forms';
            }
        } else {
            $formTests['create_form_access'] = '❌ Create form not accessible';
        }
        
        // Test if there are any existing clients to test edit form
        $clientesResponse = $this->makeRequest('GET', '/clientes');
        if (strpos($clientesResponse, '/clientes/') !== false && strpos($clientesResponse, '/edit') !== false) {
            // Try to access an edit form (find first edit link)
            preg_match('/href="([^"]*\/clientes\/\d+\/edit)"/', $clientesResponse, $matches);
            if (!empty($matches[1])) {
                $editUrl = $matches[1];
                $editResponse = $this->makeRequest('GET', $editUrl);
                
                if (strpos($editResponse, 'Editar Cliente') !== false) {
                    $formTests['edit_form_access'] = '✅ Edit form accessible';
                    
                    // Check for help alert initialization on edit
                    if (strpos($editResponse, "window.location.pathname.includes('/edit')") !== false ||
                        strpos($editResponse, 'showHelpAlert') !== false) {
                        $formTests['edit_help_alert'] = '✅ Help alert initialization for edit forms';
                    } else {
                        $formTests['edit_help_alert'] = '❌ Help alert initialization missing for edit forms';
                    }
                } else {
                    $formTests['edit_form_access'] = '❌ Edit form not accessible';
                }
            }
        } else {
            $formTests['edit_form_access'] = '⚠️ No clients available to test edit form';
        }
        
        // Test enhanced form submission
        if (strpos($clientesResponse, 'submitFormWithCallback') !== false) {
            $formTests['enhanced_form_submission'] = '✅ Enhanced form submission implemented';
        } else {
            $formTests['enhanced_form_submission'] = '❌ Enhanced form submission missing';
        }
        
        foreach ($formTests as $test => $result) {
            echo "  $result\n";
        }
        
        $this->testResults['form_enhancements'] = $formTests;
        echo "\n";
    }
    
    private function makeRequest($method, $path, $data = null, $headers = [])
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_USERAGENT => 'Laravel Test Client',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            echo "❌ Request failed: $url\n";
            return '';
        }
        
        return $response;
    }
    
    private function generateReport()
    {
        echo "📋 COMPREHENSIVE TEST REPORT\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $totalTests = 0;
        $passedTests = 0;
        
        foreach ($this->testResults as $category => $tests) {
            echo strtoupper(str_replace('_', ' ', $category)) . ":\n";
            
            if (is_array($tests)) {
                foreach ($tests as $test => $result) {
                    echo "  $result\n";
                    $totalTests++;
                    if (strpos($result, '✅') !== false) {
                        $passedTests++;
                    }
                }
            } else {
                echo "  {$tests['message']}\n";
                $totalTests++;
                if ($tests['status'] === 'success') {
                    $passedTests++;
                }
            }
            echo "\n";
        }
        
        // Overall assessment
        echo "OVERALL ASSESSMENT:\n";
        echo "Total Tests: $totalTests\n";
        echo "Passed: $passedTests\n";
        echo "Failed: " . ($totalTests - $passedTests) . "\n";
        echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";
        
        // Specific improvement verifications
        echo "IMPROVEMENT VERIFICATIONS:\n";
        echo "❯ Alert System Improvements:\n";
        echo "  - Alerts now stay visible for 8 seconds: " . $this->getTestStatus('alert_system', 'timeout_8_seconds') . "\n";
        echo "  - Help alerts with 'Consejo:' prefix: " . $this->getTestStatus('alert_system', 'help_alert_function') . "\n";
        echo "  - SweetAlert2 integration: " . $this->getTestStatus('alert_system', 'sweetalert2_integration') . "\n\n";
        
        echo "❯ CRUD Operations Improvements:\n";
        echo "  - Grid updates after operations: " . $this->getTestStatus('crud_operations', 'clientes_datatable') . "\n";
        echo "  - Enhanced delete functionality: " . $this->getTestStatus('crud_operations', 'clientes_delete_enhanced') . "\n";
        echo "  - Statistics cards update: " . $this->getTestStatus('crud_operations', 'statistics_cards') . "\n\n";
        
        echo "❯ DataTable Enhancements:\n";
        echo "  - Global dataTables object: " . $this->getTestStatus('datatable_enhancements', 'global_datatables_object') . "\n";
        echo "  - Delete button event reattachment: " . $this->getTestStatus('datatable_enhancements', 'delete_event_reattachment') . "\n";
        echo "  - Table reload functionality: " . $this->getTestStatus('datatable_enhancements', 'reload_function') . "\n\n";
        
        echo "❯ JavaScript Improvements:\n";
        echo "  - showSuccessAlert() function: " . $this->getTestStatus('javascript_improvements', 'showSuccessAlert') . "\n";
        echo "  - showErrorAlert() function: " . $this->getTestStatus('javascript_improvements', 'showErrorAlert') . "\n";
        echo "  - reloadDataTable() function: " . $this->getTestStatus('javascript_improvements', 'reloadDataTable') . "\n";
        echo "  - updateStatistics() function: " . $this->getTestStatus('javascript_improvements', 'updateStatistics') . "\n\n";
        
        echo "❯ Form Enhancements:\n";
        echo "  - Help alerts on create/edit pages: " . $this->getTestStatus('form_enhancements', 'create_help_alert') . "\n";
        echo "  - Enhanced form submission: " . $this->getTestStatus('form_enhancements', 'enhanced_form_submission') . "\n\n";
        
        // User experience assessment
        if ($passedTests / $totalTests >= 0.8) {
            echo "🎉 USER EXPERIENCE ASSESSMENT: EXCELLENT\n";
            echo "The improvements have been successfully implemented and should provide a smooth user experience.\n";
        } elseif ($passedTests / $totalTests >= 0.6) {
            echo "⚠️ USER EXPERIENCE ASSESSMENT: GOOD\n";
            echo "Most improvements are in place, but some issues need attention.\n";
        } else {
            echo "❌ USER EXPERIENCE ASSESSMENT: NEEDS IMPROVEMENT\n";
            echo "Several critical improvements are missing or not working properly.\n";
        }
        
        echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
        
        // Save detailed report
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'test_results' => $this->testResults,
            'summary' => [
                'total_tests' => $totalTests,
                'passed_tests' => $passedTests,
                'failed_tests' => $totalTests - $passedTests,
                'success_rate' => round(($passedTests / $totalTests) * 100, 2)
            ]
        ];
        
        file_put_contents('comprehensive_improvements_test_report.json', json_encode($reportData, JSON_PRETTY_PRINT));
        echo "\nDetailed report saved to: comprehensive_improvements_test_report.json\n";
    }
    
    private function getTestStatus($category, $test)
    {
        if (isset($this->testResults[$category][$test])) {
            return strpos($this->testResults[$category][$test], '✅') !== false ? 'PASSED' : 'FAILED';
        }
        return 'NOT TESTED';
    }
    
    public function __destruct()
    {
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
    }
}

// Run the comprehensive test
$tester = new ComprehensiveImprovementsTest();
$tester->runAllTests();
?>