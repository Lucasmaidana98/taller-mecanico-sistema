<?php

/**
 * Comprehensive Role-Based Permission Testing Script
 * Tests all user roles against system modules and operations
 */

require_once 'vendor/autoload.php';

class RoleBasedPermissionTester
{
    private $baseUrl = 'http://localhost:8003';
    private $cookieJar;
    private $testResults = [];
    
    // Test users with different roles
    private $testUsers = [
        'administrator' => [
            'email' => 'admin@taller.com',
            'password' => 'admin123',
            'role' => 'Administrador'
        ],
        'mechanic' => [
            'email' => 'mecanico@taller.com',
            'password' => 'mecanico123',
            'role' => 'Mecánico'
        ],
        'receptionist' => [
            'email' => 'recepcion@taller.com',
            'password' => 'recepcion123',  
            'role' => 'Recepcionista'
        ]
    ];
    
    // System modules and expected permissions
    private $modules = [
        'clientes' => [
            'name' => 'Clientes',
            'routes' => [
                'index' => '/clientes',
                'create' => '/clientes/create',
                'store' => '/clientes',
                'show' => '/clientes/1',
                'edit' => '/clientes/1/edit',
                'update' => '/clientes/1',
                'destroy' => '/clientes/1'
            ]
        ],
        'vehiculos' => [
            'name' => 'Vehículos',
            'routes' => [
                'index' => '/vehiculos',
                'create' => '/vehiculos/create',
                'store' => '/vehiculos',
                'show' => '/vehiculos/1',
                'edit' => '/vehiculos/1/edit',
                'update' => '/vehiculos/1',
                'destroy' => '/vehiculos/1'
            ]
        ],
        'servicios' => [
            'name' => 'Servicios',
            'routes' => [
                'index' => '/servicios',
                'create' => '/servicios/create',
                'store' => '/servicios',
                'show' => '/servicios/1',
                'edit' => '/servicios/1/edit',
                'update' => '/servicios/1',
                'destroy' => '/servicios/1'
            ]
        ],
        'empleados' => [
            'name' => 'Empleados',
            'routes' => [
                'index' => '/empleados',
                'create' => '/empleados/create',
                'store' => '/empleados',
                'show' => '/empleados/1',
                'edit' => '/empleados/1/edit',
                'update' => '/empleados/1',
                'destroy' => '/empleados/1'
            ]
        ],
        'ordenes' => [
            'name' => 'Órdenes de Trabajo',
            'routes' => [
                'index' => '/ordenes',
                'create' => '/ordenes/create',
                'store' => '/ordenes',
                'show' => '/ordenes/1',
                'edit' => '/ordenes/1/edit',
                'update' => '/ordenes/1',
                'destroy' => '/ordenes/1'
            ]
        ],
        'reportes' => [
            'name' => 'Reportes',
            'routes' => [
                'index' => '/reportes',
                'generar' => '/reportes/generar',
                'exportar' => '/reportes/exportar/1'
            ]
        ]
    ];
    
    // Expected permissions per role
    private $rolePermissions = [
        'Administrador' => [
            'clientes' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'],
            'vehiculos' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'],
            'servicios' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'],
            'empleados' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'],
            'ordenes' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'],
            'reportes' => ['index', 'generar', 'exportar']
        ],
        'Mecánico' => [
            'clientes' => ['index', 'show'],
            'vehiculos' => ['index', 'show', 'edit', 'update'],
            'servicios' => ['index', 'show'],
            'empleados' => [],
            'ordenes' => ['index', 'show', 'edit', 'update'],
            'reportes' => []
        ],
        'Recepcionista' => [
            'clientes' => ['index', 'create', 'store', 'show', 'edit', 'update'],
            'vehiculos' => ['index', 'create', 'store', 'show', 'edit', 'update'],
            'servicios' => ['index', 'show'],
            'empleados' => [],
            'ordenes' => ['index', 'create', 'store', 'show', 'edit', 'update'],
            'reportes' => ['index']
        ]
    ];

    public function __construct()
    {
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'role_test_cookies');
        echo "🔧 Iniciando pruebas de permisos basados en roles...\n\n";
    }

    public function runComprehensiveTests()
    {
        foreach ($this->testUsers as $userType => $userData) {
            echo "🧪 PROBANDO ROLE: {$userData['role']} ({$userData['email']})\n";
            echo str_repeat("=", 60) . "\n";
            
            // Login with user
            if ($this->loginUser($userData)) {
                $this->testUserPermissions($userType, $userData['role']);
                $this->testSecurityVulnerabilities($userType, $userData['role']);
                $this->testFieldLevelRestrictions($userType, $userData['role']);
            }
            
            echo "\n";
        }
        
        $this->generateReport();
    }

    private function loginUser($userData)
    {
        echo "🔐 Intentando login como {$userData['email']}...\n";
        
        // Get CSRF token first
        $loginPage = $this->makeRequest('GET', '/login');
        if (!$loginPage) {
            echo "❌ Error: No se pudo acceder a la página de login\n";
            return false;
        }

        preg_match('/<input[^>]*name=["\']_token["\'][^>]*value=["\']([^"\']*)["\']/', $loginPage, $matches);
        $csrfToken = $matches[1] ?? null;

        if (!$csrfToken) {
            echo "❌ Error: No se pudo obtener el token CSRF\n";
            return false;
        }

        // Attempt login
        $loginData = [
            '_token' => $csrfToken,
            'email' => $userData['email'],
            'password' => $userData['password']
        ];

        $response = $this->makeRequest('POST', '/login', $loginData);
        
        if (strpos($response, 'dashboard') !== false || strpos($response, 'Dashboard') !== false) {
            echo "✅ Login exitoso para {$userData['role']}\n";
            return true;
        } else {
            echo "❌ Error en login para {$userData['email']}\n";
            return false;
        }
    }

    private function testUserPermissions($userType, $role)
    {
        echo "\n📋 Probando permisos de módulos para {$role}:\n";
        
        $roleResults = [];
        
        foreach ($this->modules as $moduleKey => $moduleData) {
            echo "\n📁 Módulo: {$moduleData['name']}\n";
            
            $moduleResults = [];
            $expectedPermissions = $this->rolePermissions[$role][$moduleKey] ?? [];
            
            foreach ($moduleData['routes'] as $action => $route) {
                $shouldHaveAccess = in_array($action, $expectedPermissions);
                $hasAccess = $this->testModuleAccess($route, $action);
                
                $status = $hasAccess ? '✅' : '❌';
                $expected = $shouldHaveAccess ? 'PERMITIDO' : 'DENEGADO';
                $actual = $hasAccess ? 'PERMITIDO' : 'DENEGADO';
                
                echo "  {$status} {$action}: {$actual} (esperado: {$expected})\n";
                
                $moduleResults[$action] = [
                    'expected' => $shouldHaveAccess,
                    'actual' => $hasAccess,
                    'correct' => $shouldHaveAccess === $hasAccess
                ];
            }
            
            $roleResults[$moduleKey] = $moduleResults;
        }
        
        $this->testResults[$userType] = $roleResults;
    }

    private function testModuleAccess($route, $action)
    {
        $method = in_array($action, ['store', 'update']) ? 'POST' : 
                 ($action === 'destroy' ? 'DELETE' : 'GET');
        
        if ($method === 'DELETE') {
            // Test DELETE via POST with _method parameter
            $response = $this->makeRequest('POST', $route, ['_method' => 'DELETE', '_token' => 'test']);
        } else {
            $response = $this->makeRequest($method, $route);
        }
        
        if (!$response) return false;
        
        // Check for various indicators of access denial
        $httpCode = $this->getLastHttpCode();
        $denialIndicators = [
            'Access denied',
            'Unauthorized',
            'Forbidden',
            '403',
            'No tienes permisos',
            'Permission denied',
            'login', // Redirect to login
            'middleware'
        ];
        
        // If HTTP code indicates success (2xx) and no denial indicators, assume access granted
        if ($httpCode >= 200 && $httpCode < 300) {
            foreach ($denialIndicators as $indicator) {
                if (stripos($response, $indicator) !== false) {
                    return false;
                }
            }
            return true;
        }
        
        return false;
    }

    private function testSecurityVulnerabilities($userType, $role)
    {
        echo "\n🛡️ Probando vulnerabilidades de seguridad para {$role}:\n";
        
        // Test privilege escalation attempts
        $this->testPrivilegeEscalation($userType, $role);
        
        // Test unauthorized access to admin functions
        $this->testUnauthorizedAdminAccess($userType, $role);
        
        // Test API endpoint restrictions
        $this->testApiRestrictions($userType, $role);
    }

    private function testPrivilegeEscalation($userType, $role)
    {
        echo "  🔓 Probando escalación de privilegios...\n";
        
        // Try to access admin-only endpoints
        $adminEndpoints = [
            '/empleados',
            '/empleados/create',
            '/reportes/generar'
        ];
        
        foreach ($adminEndpoints as $endpoint) {
            $response = $this->makeRequest('GET', $endpoint);
            $hasAccess = $this->testModuleAccess($endpoint, 'index');
            
            if ($role !== 'Administrador' && $hasAccess) {
                echo "    ⚠️ VULNERABILIDAD: {$role} puede acceder a {$endpoint}\n";
            } else {
                echo "    ✅ Acceso a {$endpoint} correctamente restringido\n";
            }
        }
    }

    private function testUnauthorizedAdminAccess($userType, $role)
    {
        echo "  👤 Probando acceso no autorizado a funciones admin...\n";
        
        if ($role === 'Administrador') {
            echo "    ℹ️ Saltando prueba para rol administrador\n";
            return;
        }
        
        // Test user management access
        $response = $this->makeRequest('GET', '/empleados');
        if (strpos($response, 'empleados') !== false && !strpos($response, 'login')) {
            echo "    ⚠️ VULNERABILIDAD: {$role} puede acceder a gestión de empleados\n";
        } else {
            echo "    ✅ Acceso a gestión de empleados correctamente denegado\n";
        }
    }

    private function testApiRestrictions($userType, $role)
    {
        echo "  🔗 Probando restricciones de API...\n";
        
        // Test AJAX endpoints with different permissions
        $apiEndpoints = [
            '/clientes' => 'GET',
            '/vehiculos' => 'GET', 
            '/servicios' => 'GET',
            '/empleados' => 'GET',
            '/ordenes' => 'GET'
        ];
        
        foreach ($apiEndpoints as $endpoint => $method) {
            $headers = ['X-Requested-With: XMLHttpRequest'];
            $response = $this->makeRequest($method, $endpoint, null, $headers);
            
            // Check if JSON response indicates proper permission handling
            $jsonData = json_decode($response, true);
            if ($jsonData && isset($jsonData['success'])) {
                echo "    ✅ API {$endpoint} maneja permisos correctamente\n";
            }
        }
    }

    private function testFieldLevelRestrictions($userType, $role)
    {
        echo "\n🔒 Probando restricciones a nivel de campo para {$role}:\n";
        
        // Test if certain fields are read-only based on role
        $this->testPriceFieldRestrictions($userType, $role);
        $this->testSalaryFieldRestrictions($userType, $role);
        $this->testStatusFieldRestrictions($userType, $role);
    }

    private function testPriceFieldRestrictions($userType, $role)
    {
        echo "  💰 Probando restricciones de campos de precio...\n";
        
        $response = $this->makeRequest('GET', '/servicios/create');
        if ($response) {
            $hasPriceField = strpos($response, 'price') !== false;
            
            if ($role === 'Mecánico' && $hasPriceField) {
                // Check if price field is readonly
                $isReadonly = strpos($response, 'readonly') !== false || 
                             strpos($response, 'disabled') !== false;
                
                if (!$isReadonly) {
                    echo "    ⚠️ POSIBLE PROBLEMA: Mecánico puede modificar precios\n";
                } else {
                    echo "    ✅ Campo precio correctamente restringido para mecánico\n";
                }
            }
        }
    }

    private function testSalaryFieldRestrictions($userType, $role)
    {
        echo "  💵 Probando restricciones de campos de salario...\n";
        
        if ($role !== 'Administrador') {
            $response = $this->makeRequest('GET', '/empleados');
            if (strpos($response, 'salary') !== false || strpos($response, 'salario') !== false) {
                echo "    ⚠️ VULNERABILIDAD: {$role} puede ver campos de salario\n";
            } else {
                echo "    ✅ Campos de salario correctamente ocultos\n";
            }
        }
    }

    private function testStatusFieldRestrictions($userType, $role)
    {
        echo "  📊 Probando restricciones de campos de estado...\n";
        
        $response = $this->makeRequest('GET', '/ordenes');
        if ($response && strpos($response, 'status') !== false) {
            if ($role === 'Mecánico') {
                echo "    ℹ️ Mecánico puede ver y modificar estados de órdenes (esperado)\n";
            } elseif ($role === 'Recepcionista') {
                echo "    ℹ️ Recepcionista puede ver estados de órdenes (esperado)\n";
            }
        }
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
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $this->lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response;
    }

    private function getLastHttpCode()
    {
        return $this->lastHttpCode ?? 0;
    }

    private function generateReport()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "📊 REPORTE FINAL DE PERMISOS BASADOS EN ROLES\n";
        echo str_repeat("=", 80) . "\n";

        $totalTests = 0;
        $passedTests = 0;
        $securityIssues = [];

        foreach ($this->testResults as $userType => $roleResults) {
            $userData = $this->testUsers[$userType];
            echo "\n🏷️ ROLE: {$userData['role']}\n";
            echo str_repeat("-", 40) . "\n";

            foreach ($roleResults as $module => $moduleResults) {
                echo "\n📁 {$this->modules[$module]['name']}:\n";
                
                foreach ($moduleResults as $action => $result) {
                    $totalTests++;
                    $status = $result['correct'] ? '✅' : '❌';
                    $expected = $result['expected'] ? 'PERMITIDO' : 'DENEGADO';
                    $actual = $result['actual'] ? 'PERMITIDO' : 'DENEGADO';
                    
                    echo "  {$status} {$action}: {$actual} (esperado: {$expected})\n";
                    
                    if ($result['correct']) {
                        $passedTests++;
                    } else {
                        $securityIssues[] = [
                            'role' => $userData['role'],
                            'module' => $module,
                            'action' => $action,
                            'expected' => $expected,
                            'actual' => $actual
                        ];
                    }
                }
            }
        }

        // Summary statistics
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📈 ESTADÍSTICAS GENERALES:\n";
        echo str_repeat("=", 50) . "\n";
        echo "Total de pruebas: {$totalTests}\n";
        echo "Pruebas exitosas: {$passedTests}\n";
        echo "Pruebas fallidas: " . ($totalTests - $passedTests) . "\n";
        $successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
        echo "Porcentaje de éxito: {$successRate}%\n";

        // Security issues
        if (!empty($securityIssues)) {
            echo "\n⚠️ PROBLEMAS DE SEGURIDAD IDENTIFICADOS:\n";
            echo str_repeat("-", 50) . "\n";
            
            foreach ($securityIssues as $issue) {
                echo "• {$issue['role']} - {$issue['module']}.{$issue['action']}: ";
                echo "Esperado {$issue['expected']}, obtuvo {$issue['actual']}\n";
            }
        } else {
            echo "\n✅ No se identificaron problemas de seguridad\n";
        }

        // Recommendations
        echo "\n🔧 RECOMENDACIONES:\n";
        echo str_repeat("-", 30) . "\n";
        
        if (!empty($securityIssues)) {
            echo "1. Revisar y corregir los permisos identificados como problemáticos\n";
            echo "2. Implementar middleware de permisos en todas las rutas\n";
            echo "3. Agregar validación de permisos en los controladores\n";
            echo "4. Implementar restricciones a nivel de vista para campos sensibles\n";
        } else {
            echo "1. El sistema de permisos funciona correctamente\n";
            echo "2. Continuar monitoreando los accesos de usuarios\n";
            echo "3. Considerar auditorías regulares de permisos\n";
        }

        // Save detailed report to file
        $this->saveDetailedReport();
    }

    private function saveDetailedReport()
    {
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'total_tests' => 0,
                'passed_tests' => 0,
                'failed_tests' => 0,
                'success_rate' => 0
            ],
            'detailed_results' => $this->testResults,
            'test_users' => $this->testUsers,
            'role_permissions' => $this->rolePermissions
        ];

        // Calculate summary stats
        $totalTests = 0;
        $passedTests = 0;
        
        foreach ($this->testResults as $roleResults) {
            foreach ($roleResults as $moduleResults) {
                foreach ($moduleResults as $result) {
                    $totalTests++;
                    if ($result['correct']) $passedTests++;
                }
            }
        }

        $reportData['summary'] = [
            'total_tests' => $totalTests,
            'passed_tests' => $passedTests,
            'failed_tests' => $totalTests - $passedTests,
            'success_rate' => $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0
        ];

        file_put_contents('role_based_permission_test_report.json', json_encode($reportData, JSON_PRETTY_PRINT));
        echo "\n💾 Reporte detallado guardado en: role_based_permission_test_report.json\n";
    }

    public function __destruct()
    {
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
    }
}

// Run the comprehensive role-based permission tests
$tester = new RoleBasedPermissionTester();
$tester->runComprehensiveTests();