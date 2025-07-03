<?php
/**
 * Comprehensive Test Script for Empleados Module CRUD Operations
 * Tests alerts, database persistence, and UI updates
 */

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class EmpleadosModuleTest
{
    private $client;
    private $cookieJar;
    private $baseUrl = 'http://localhost:8001';
    private $testResults = [];
    private $testEmployeeId = null;
    
    public function __construct()
    {
        $this->cookieJar = new CookieJar();
        $this->client = new Client([
            'cookies' => $this->cookieJar,
            'verify' => false,
            'http_errors' => false,
            'timeout' => 30
        ]);
    }
    
    public function runAllTests()
    {
        echo "🧪 Starting Empleados Module CRUD Tests\n";
        echo "======================================\n\n";
        
        try {
            // Step 1: Login
            $this->login();
            
            // Step 2: Test CREATE operation
            $this->testCreateEmployee();
            
            // Step 3: Test READ operations (index and show)
            $this->testReadOperations();
            
            // Step 4: Test UPDATE operation
            $this->testUpdateEmployee();
            
            // Step 5: Test validation
            $this->testValidation();
            
            // Step 6: Test show page with statistics
            $this->testShowPageStatistics();
            
            // Generate final report
            $this->generateReport();
            
        } catch (Exception $e) {
            echo "❌ Test execution failed: " . $e->getMessage() . "\n";
            $this->testResults['execution_error'] = $e->getMessage();
        }
    }
    
    private function login()
    {
        echo "🔐 Attempting to log in...\n";
        
        // Get login page to obtain CSRF token
        $response = $this->client->get($this->baseUrl . '/login');
        
        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to access login page");
        }
        
        $html = (string) $response->getBody();
        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches);
        
        if (empty($matches[1])) {
            preg_match('/name="_token" value="([^"]+)"/', $html, $matches);
        }
        
        if (empty($matches[1])) {
            throw new Exception("CSRF token not found");
        }
        
        $csrfToken = $matches[1];
        
        // Perform login
        $response = $this->client->post($this->baseUrl . '/login', [
            'form_params' => [
                'email' => 'admin@taller.com',
                'password' => 'admin123',
                '_token' => $csrfToken
            ],
            'allow_redirects' => false
        ]);
        
        if ($response->getStatusCode() === 302) {
            $location = $response->getHeader('Location')[0] ?? '';
            
            // Check if redirect is not back to login (which would indicate failure)
            if (strpos($location, '/login') === false) {
                echo "✅ Login successful (redirected to: $location)\n\n";
                $this->testResults['login'] = [
                    'status' => 'success',
                    'message' => 'Successfully logged in with admin credentials',
                    'redirect_location' => $location
                ];
            } else {
                throw new Exception("Login failed - redirected back to login");
            }
        } else {
            // Check response body for errors
            $body = (string) $response->getBody();
            if (strpos($body, 'error') !== false || strpos($body, 'invalid') !== false) {
                throw new Exception("Login failed - invalid credentials");
            }
            throw new Exception("Login failed - status code: " . $response->getStatusCode());
        }
    }
    
    private function testCreateEmployee()
    {
        echo "📝 Testing CREATE operation...\n";
        
        // Get create form page
        $response = $this->client->get($this->baseUrl . '/empleados/create');
        
        if ($response->getStatusCode() !== 200) {
            $this->testResults['create_form_access'] = [
                'status' => 'failed',
                'message' => 'Could not access create form'
            ];
            return;
        }
        
        $html = (string) $response->getBody();
        preg_match('/name="_token" value="([^"]+)"/', $html, $matches);
        
        if (empty($matches[1])) {
            throw new Exception("CSRF token not found in create form");
        }
        
        $csrfToken = $matches[1];
        
        // Test data
        $testData = [
            'name' => 'Test Employee',
            'email' => 'test.employee@example.com',
            'phone' => '555-9999',
            'position' => 'Test Position',
            'salary' => '50000',
            'hire_date' => date('Y-m-d'),
            'status' => '1',
            '_token' => $csrfToken
        ];
        
        // Submit create form
        $response = $this->client->post($this->baseUrl . '/empleados', [
            'form_params' => $testData
        ]);
        
        // Check response
        if ($response->getStatusCode() === 302) {
            $location = $response->getHeader('Location')[0] ?? '';
            if (strpos($location, 'empleados') !== false) {
                echo "✅ Employee creation successful\n";
                $this->testResults['create_employee'] = [
                    'status' => 'success',
                    'message' => 'Employee created successfully',
                    'redirect_location' => $location
                ];
                
                // Follow redirect to check for success alert
                $redirectResponse = $this->client->get($location);
                $redirectHtml = (string) $redirectResponse->getBody();
                
                if (strpos($redirectHtml, 'exitosamente') !== false || 
                    strpos($redirectHtml, 'success') !== false) {
                    echo "✅ Success alert detected on redirect\n";
                    $this->testResults['create_alert'] = [
                        'status' => 'success',
                        'message' => 'Success alert displayed correctly'
                    ];
                } else {
                    echo "⚠️ Success alert not clearly detected\n";
                    $this->testResults['create_alert'] = [
                        'status' => 'warning',
                        'message' => 'Success alert not clearly visible'
                    ];
                }
                
                // Extract employee ID from URL or response
                if (preg_match('/empleados\/(\d+)/', $redirectHtml, $matches)) {
                    $this->testEmployeeId = $matches[1];
                } elseif (preg_match('/Test Employee.*?empleados\/(\d+)/', $redirectHtml, $matches)) {
                    $this->testEmployeeId = $matches[1];
                }
                
            } else {
                echo "❌ Unexpected redirect after creation\n";
                $this->testResults['create_employee'] = [
                    'status' => 'failed',
                    'message' => 'Unexpected redirect location: ' . $location
                ];
            }
        } else {
            echo "❌ Employee creation failed\n";
            $body = (string) $response->getBody();
            $this->testResults['create_employee'] = [
                'status' => 'failed',
                'message' => 'Creation failed with status: ' . $response->getStatusCode(),
                'response_body' => substr($body, 0, 500)
            ];
        }
        
        echo "\n";
    }
    
    private function testReadOperations()
    {
        echo "👀 Testing READ operations...\n";
        
        // Test index page
        $response = $this->client->get($this->baseUrl . '/empleados');
        
        if ($response->getStatusCode() === 200) {
            $html = (string) $response->getBody();
            
            // Check if test employee appears in index
            if (strpos($html, 'Test Employee') !== false && 
                strpos($html, 'test.employee@example.com') !== false) {
                echo "✅ Test employee appears in index page\n";
                $this->testResults['index_display'] = [
                    'status' => 'success',
                    'message' => 'Test employee correctly displayed in index'
                ];
                
                // Try to extract employee ID from the index page
                if (!$this->testEmployeeId && preg_match('/empleados\/(\d+).*?Test Employee/s', $html, $matches)) {
                    $this->testEmployeeId = $matches[1];
                    echo "✅ Employee ID extracted: {$this->testEmployeeId}\n";
                }
                
            } else {
                echo "❌ Test employee not found in index page\n";
                $this->testResults['index_display'] = [
                    'status' => 'failed',
                    'message' => 'Test employee not visible in index page'
                ];
            }
        } else {
            echo "❌ Could not access empleados index\n";
            $this->testResults['index_access'] = [
                'status' => 'failed',
                'message' => 'Could not access empleados index page'
            ];
        }
        
        echo "\n";
    }
    
    private function testUpdateEmployee()
    {
        echo "✏️ Testing UPDATE operation...\n";
        
        if (!$this->testEmployeeId) {
            echo "⚠️ Cannot test update - employee ID not found\n";
            $this->testResults['update_employee'] = [
                'status' => 'skipped',
                'message' => 'Employee ID not available for update test'
            ];
            return;
        }
        
        // Get edit form
        $response = $this->client->get($this->baseUrl . "/empleados/{$this->testEmployeeId}/edit");
        
        if ($response->getStatusCode() !== 200) {
            echo "❌ Could not access edit form\n";
            $this->testResults['edit_form_access'] = [
                'status' => 'failed',
                'message' => 'Could not access edit form'
            ];
            return;
        }
        
        $html = (string) $response->getBody();
        preg_match('/name="_token" value="([^"]+)"/', $html, $matches);
        
        if (empty($matches[1])) {
            throw new Exception("CSRF token not found in edit form");
        }
        
        $csrfToken = $matches[1];
        
        // Updated test data
        $updateData = [
            'name' => 'Test Employee',
            'email' => 'test.employee@example.com',
            'phone' => '555-9999',
            'position' => 'Senior Test Position',
            'salary' => '55000',
            'hire_date' => date('Y-m-d'),
            'status' => '1',
            '_token' => $csrfToken,
            '_method' => 'PUT'
        ];
        
        // Submit update
        $response = $this->client->post($this->baseUrl . "/empleados/{$this->testEmployeeId}", [
            'form_params' => $updateData
        ]);
        
        if ($response->getStatusCode() === 302) {
            $location = $response->getHeader('Location')[0] ?? '';
            if (strpos($location, 'empleados') !== false) {
                echo "✅ Employee update successful\n";
                $this->testResults['update_employee'] = [
                    'status' => 'success',
                    'message' => 'Employee updated successfully'
                ];
                
                // Follow redirect to check for success alert and verify changes
                $redirectResponse = $this->client->get($location);
                $redirectHtml = (string) $redirectResponse->getBody();
                
                if (strpos($redirectHtml, 'exitosamente') !== false || 
                    strpos($redirectHtml, 'success') !== false) {
                    echo "✅ Update success alert detected\n";
                    $this->testResults['update_alert'] = [
                        'status' => 'success',
                        'message' => 'Update success alert displayed correctly'
                    ];
                }
                
                // Check if changes persist
                if (strpos($redirectHtml, 'Senior Test Position') !== false && 
                    strpos($redirectHtml, '55000') !== false) {
                    echo "✅ Changes persist in view\n";
                    $this->testResults['update_persistence'] = [
                        'status' => 'success',
                        'message' => 'Updated data persists correctly'
                    ];
                } else {
                    echo "❌ Changes not visible in view\n";
                    $this->testResults['update_persistence'] = [
                        'status' => 'failed',
                        'message' => 'Updated data not visible in view'
                    ];
                }
                
            } else {
                echo "❌ Unexpected redirect after update\n";
                $this->testResults['update_employee'] = [
                    'status' => 'failed',
                    'message' => 'Unexpected redirect after update'
                ];
            }
        } else {
            echo "❌ Employee update failed\n";
            $this->testResults['update_employee'] = [
                'status' => 'failed',
                'message' => 'Update failed with status: ' . $response->getStatusCode()
            ];
        }
        
        echo "\n";
    }
    
    private function testShowPageStatistics()
    {
        echo "📊 Testing SHOW page with statistics...\n";
        
        if (!$this->testEmployeeId) {
            echo "⚠️ Cannot test show page - employee ID not found\n";
            $this->testResults['show_page'] = [
                'status' => 'skipped',
                'message' => 'Employee ID not available for show page test'
            ];
            return;
        }
        
        // Access show page
        $response = $this->client->get($this->baseUrl . "/empleados/{$this->testEmployeeId}");
        
        if ($response->getStatusCode() === 200) {
            $html = (string) $response->getBody();
            
            // Check for employee data
            $checks = [
                'name' => strpos($html, 'Test Employee') !== false,
                'email' => strpos($html, 'test.employee@example.com') !== false,
                'position' => strpos($html, 'Senior Test Position') !== false,
                'salary' => strpos($html, '55000') !== false,
                'statistics_section' => strpos($html, 'estadística') !== false || strpos($html, 'Estadística') !== false,
                'orders_info' => strpos($html, 'orden') !== false || strpos($html, 'Orden') !== false
            ];
            
            $passedChecks = array_filter($checks);
            $totalChecks = count($checks);
            $passedCount = count($passedChecks);
            
            echo "✅ Show page accessed successfully\n";
            echo "📈 Data accuracy: {$passedCount}/{$totalChecks} checks passed\n";
            
            if ($passedCount >= ($totalChecks * 0.8)) {
                $this->testResults['show_page'] = [
                    'status' => 'success',
                    'message' => "Show page displays correctly ({$passedCount}/{$totalChecks} checks passed)",
                    'checks' => $checks
                ];
            } else {
                $this->testResults['show_page'] = [
                    'status' => 'partial',
                    'message' => "Show page partially working ({$passedCount}/{$totalChecks} checks passed)",
                    'checks' => $checks
                ];
            }
            
        } else {
            echo "❌ Could not access show page\n";
            $this->testResults['show_page'] = [
                'status' => 'failed',
                'message' => 'Could not access employee show page'
            ];
        }
        
        echo "\n";
    }
    
    private function testValidation()
    {
        echo "🔍 Testing VALIDATION...\n";
        
        // Get create form for validation tests
        $response = $this->client->get($this->baseUrl . '/empleados/create');
        
        if ($response->getStatusCode() !== 200) {
            echo "⚠️ Cannot test validation - create form not accessible\n";
            return;
        }
        
        $html = (string) $response->getBody();
        preg_match('/name="_token" value="([^"]+)"/', $html, $matches);
        $csrfToken = $matches[1] ?? '';
        
        // Test 1: Duplicate email validation
        echo "🔸 Testing duplicate email validation...\n";
        $duplicateEmailData = [
            'name' => 'Another Employee',
            'email' => 'test.employee@example.com', // Same email as existing
            'phone' => '555-8888',
            'position' => 'Another Position',
            'salary' => '45000',
            'hire_date' => date('Y-m-d'),
            'status' => '1',
            '_token' => $csrfToken
        ];
        
        $response = $this->client->post($this->baseUrl . '/empleados', [
            'form_params' => $duplicateEmailData
        ]);
        
        if ($response->getStatusCode() === 422 || 
            ($response->getStatusCode() === 302 && 
             strpos($response->getHeader('Location')[0] ?? '', 'create') !== false)) {
            
            // Follow redirect to check for validation errors
            if ($response->getStatusCode() === 302) {
                $redirectResponse = $this->client->get($response->getHeader('Location')[0]);
                $redirectHtml = (string) $redirectResponse->getBody();
                
                if (strpos($redirectHtml, 'ya está registrado') !== false ||
                    strpos($redirectHtml, 'already been taken') !== false ||
                    strpos($redirectHtml, 'unique') !== false) {
                    echo "✅ Duplicate email validation working\n";
                    $this->testResults['duplicate_email_validation'] = [
                        'status' => 'success',
                        'message' => 'Duplicate email validation working correctly'
                    ];
                } else {
                    echo "❌ Duplicate email validation error not clearly shown\n";
                    $this->testResults['duplicate_email_validation'] = [
                        'status' => 'failed',
                        'message' => 'Duplicate email validation error not visible'
                    ];
                }
            }
        } else {
            echo "❌ Duplicate email was allowed\n";
            $this->testResults['duplicate_email_validation'] = [
                'status' => 'failed',
                'message' => 'Duplicate email was not rejected'
            ];
        }
        
        // Test 2: Required field validation
        echo "🔸 Testing required field validation...\n";
        $emptyData = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'position' => '',
            'salary' => '',
            'hire_date' => '',
            '_token' => $csrfToken
        ];
        
        $response = $this->client->post($this->baseUrl . '/empleados', [
            'form_params' => $emptyData
        ]);
        
        if ($response->getStatusCode() === 422 || 
            ($response->getStatusCode() === 302 && 
             strpos($response->getHeader('Location')[0] ?? '', 'create') !== false)) {
            
            echo "✅ Required field validation triggered\n";
            $this->testResults['required_field_validation'] = [
                'status' => 'success',
                'message' => 'Required field validation working'
            ];
        } else {
            echo "❌ Required field validation not working\n";
            $this->testResults['required_field_validation'] = [
                'status' => 'failed',
                'message' => 'Required fields were not validated'
            ];
        }
        
        echo "\n";
    }
    
    private function generateReport()
    {
        echo "📋 EMPLEADOS MODULE TEST REPORT\n";
        echo "===============================\n\n";
        
        $totalTests = count($this->testResults);
        $successCount = 0;
        $failedCount = 0;
        $warningCount = 0;
        $skippedCount = 0;
        
        foreach ($this->testResults as $testName => $result) {
            $status = $result['status'] ?? 'unknown';
            $message = $result['message'] ?? 'No message';
            
            switch ($status) {
                case 'success':
                    $successCount++;
                    $icon = '✅';
                    break;
                case 'failed':
                    $failedCount++;
                    $icon = '❌';
                    break;
                case 'warning':
                case 'partial':
                    $warningCount++;
                    $icon = '⚠️';
                    break;
                case 'skipped':
                    $skippedCount++;
                    $icon = '⏭️';
                    break;
                default:
                    $icon = '❓';
            }
            
            echo "{$icon} {$testName}: {$message}\n";
        }
        
        echo "\n📊 SUMMARY:\n";
        echo "- Total Tests: {$totalTests}\n";
        echo "- Successful: {$successCount}\n";
        echo "- Failed: {$failedCount}\n";
        echo "- Warnings: {$warningCount}\n";
        echo "- Skipped: {$skippedCount}\n\n";
        
        $successRate = $totalTests > 0 ? ($successCount / $totalTests) * 100 : 0;
        echo "🎯 Success Rate: " . number_format($successRate, 1) . "%\n\n";
        
        // Detailed analysis
        echo "📋 DETAILED ANALYSIS:\n";
        echo "====================\n\n";
        
        echo "🔐 AUTHENTICATION:\n";
        if (isset($this->testResults['login'])) {
            echo "- Login functionality: " . $this->testResults['login']['status'] . "\n";
        }
        
        echo "\n📝 CREATE OPERATIONS:\n";
        if (isset($this->testResults['create_employee'])) {
            echo "- Employee creation: " . $this->testResults['create_employee']['status'] . "\n";
        }
        if (isset($this->testResults['create_alert'])) {
            echo "- Success alert display: " . $this->testResults['create_alert']['status'] . "\n";
        }
        
        echo "\n👀 READ OPERATIONS:\n";
        if (isset($this->testResults['index_display'])) {
            echo "- Index page display: " . $this->testResults['index_display']['status'] . "\n";
        }
        if (isset($this->testResults['show_page'])) {
            echo "- Show page with statistics: " . $this->testResults['show_page']['status'] . "\n";
        }
        
        echo "\n✏️ UPDATE OPERATIONS:\n";
        if (isset($this->testResults['update_employee'])) {
            echo "- Employee update: " . $this->testResults['update_employee']['status'] . "\n";
        }
        if (isset($this->testResults['update_alert'])) {
            echo "- Update alert display: " . $this->testResults['update_alert']['status'] . "\n";
        }
        if (isset($this->testResults['update_persistence'])) {
            echo "- Data persistence: " . $this->testResults['update_persistence']['status'] . "\n";
        }
        
        echo "\n🔍 VALIDATION:\n";
        if (isset($this->testResults['duplicate_email_validation'])) {
            echo "- Duplicate email validation: " . $this->testResults['duplicate_email_validation']['status'] . "\n";
        }
        if (isset($this->testResults['required_field_validation'])) {
            echo "- Required field validation: " . $this->testResults['required_field_validation']['status'] . "\n";
        }
        
        // Save detailed report to file
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'total_tests' => $totalTests,
                'successful' => $successCount,
                'failed' => $failedCount,
                'warnings' => $warningCount,
                'skipped' => $skippedCount,
                'success_rate' => $successRate
            ],
            'detailed_results' => $this->testResults,
            'test_employee_id' => $this->testEmployeeId
        ];
        
        file_put_contents('EMPLEADOS_TEST_REPORT.json', json_encode($reportData, JSON_PRETTY_PRINT));
        echo "\n💾 Detailed report saved to: EMPLEADOS_TEST_REPORT.json\n";
    }
}

// Run the tests
$tester = new EmpleadosModuleTest();
$tester->runAllTests();