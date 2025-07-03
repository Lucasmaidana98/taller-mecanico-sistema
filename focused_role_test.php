<?php

/**
 * Focused Role-Based Permission Testing Script
 */

class FocusedRolePermissionTester
{
    private $baseUrl = 'http://localhost:8003';
    private $cookieJar;
    private $testResults = [];
    
    private $testUsers = [
        'administrator' => ['email' => 'admin@taller.com', 'password' => 'admin123', 'role' => 'Administrador'],
        'mechanic' => ['email' => 'mecanico@taller.com', 'password' => 'mecanico123', 'role' => 'Mecánico'],
        'receptionist' => ['email' => 'recepcion@taller.com', 'password' => 'recepcion123', 'role' => 'Recepcionista']
    ];

    public function __construct()
    {
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'focused_role_test');
    }

    public function runTests()
    {
        echo "🔧 PRUEBAS DE PERMISOS BASADOS EN ROLES - REPORTE ENFOCADO\n";
        echo str_repeat("=", 70) . "\n\n";

        foreach ($this->testUsers as $userType => $userData) {
            echo "👤 PROBANDO ROLE: {$userData['role']}\n";
            echo str_repeat("-", 50) . "\n";
            
            if ($this->loginUser($userData)) {
                $this->testKeyPermissions($userType, $userData['role']);
                $this->testSecurityGaps($userType, $userData['role']);
            }
            
            echo "\n";
        }
        
        $this->generateFinalReport();
    }

    private function loginUser($userData)
    {
        echo "🔐 Login: {$userData['email']} ... ";
        
        // Get login page
        $loginPage = $this->makeRequest('GET', '/login');
        if (!$loginPage) {
            echo "❌ FALLO - No acceso a login\n";
            return false;
        }

        // Extract CSRF token
        preg_match('/<input[^>]*name=["\']_token["\'][^>]*value=["\']([^"\']*)["\']/', $loginPage, $matches);
        $csrfToken = $matches[1] ?? null;

        if (!$csrfToken) {
            echo "❌ FALLO - Sin token CSRF\n";
            return false;
        }

        // Login attempt
        $loginData = [
            '_token' => $csrfToken,
            'email' => $userData['email'],
            'password' => $userData['password']
        ];

        $response = $this->makeRequest('POST', '/login', $loginData);
        
        if (strpos($response, 'dashboard') !== false || strpos($response, 'Dashboard') !== false) {
            echo "✅ EXITOSO\n";
            return true;
        } else {
            echo "❌ FALLO - Login incorrecto\n";
            return false;
        }
    }

    private function testKeyPermissions($userType, $role)
    {
        echo "\n📋 Probando permisos clave:\n";
        
        $keyTests = [
            'clientes' => '/clientes',
            'vehiculos' => '/vehiculos', 
            'servicios' => '/servicios',
            'empleados' => '/empleados',
            'ordenes' => '/ordenes',
            'reportes' => '/reportes'
        ];

        $results = [];
        
        foreach ($keyTests as $module => $route) {
            $hasAccess = $this->testAccess($route);
            $expected = $this->shouldHaveAccess($role, $module);
            
            $status = ($hasAccess === $expected) ? '✅' : '❌';
            $accessText = $hasAccess ? 'PERMITIDO' : 'DENEGADO';
            $expectedText = $expected ? 'PERMITIDO' : 'DENEGADO';
            
            echo "  {$status} {$module}: {$accessText} (esperado: {$expectedText})\n";
            
            $results[$module] = [
                'actual' => $hasAccess,
                'expected' => $expected,
                'correct' => $hasAccess === $expected
            ];
        }
        
        $this->testResults[$userType] = $results;
    }

    private function testSecurityGaps($userType, $role)
    {
        echo "\n🛡️ Probando vulnerabilidades de seguridad:\n";
        
        // Test unauthorized admin access
        if ($role !== 'Administrador') {
            $adminAccess = $this->testAccess('/empleados');
            if ($adminAccess) {
                echo "  ⚠️ VULNERABILIDAD: {$role} puede acceder a /empleados\n";
            } else {
                echo "  ✅ Acceso admin correctamente restringido\n";
            }
        }

        // Test create/edit restrictions
        $createTests = [
            'clientes/create' => '/clientes/create',
            'vehiculos/create' => '/vehiculos/create', 
            'servicios/create' => '/servicios/create'
        ];

        foreach ($createTests as $test => $route) {
            $hasAccess = $this->testAccess($route);
            $shouldHave = $this->shouldHaveCreateAccess($role, explode('/', $test)[0]);
            
            if ($hasAccess && !$shouldHave) {
                echo "  ⚠️ PROBLEMA: {$role} puede acceder a {$test}\n";
            } elseif (!$hasAccess && $shouldHave) {
                echo "  ⚠️ PROBLEMA: {$role} NO puede acceder a {$test} (debería poder)\n";
            } else {
                echo "  ✅ Acceso {$test} correctamente configurado\n";
            }
        }
    }

    private function shouldHaveAccess($role, $module)
    {
        $permissions = [
            'Administrador' => ['clientes', 'vehiculos', 'servicios', 'empleados', 'ordenes', 'reportes'],
            'Mecánico' => ['clientes', 'vehiculos', 'servicios', 'ordenes'],
            'Recepcionista' => ['clientes', 'vehiculos', 'servicios', 'ordenes', 'reportes']
        ];
        
        return in_array($module, $permissions[$role] ?? []);
    }

    private function shouldHaveCreateAccess($role, $module)
    {
        $createPermissions = [
            'Administrador' => ['clientes', 'vehiculos', 'servicios', 'empleados', 'ordenes'],
            'Mecánico' => [],
            'Recepcionista' => ['clientes', 'vehiculos', 'ordenes']
        ];
        
        return in_array($module, $createPermissions[$role] ?? []);
    }

    private function testAccess($route)
    {
        $response = $this->makeRequest('GET', $route);
        if (!$response) return false;
        
        $httpCode = $this->getLastHttpCode();
        
        // Check for denial indicators
        $denialIndicators = ['403', 'Unauthorized', 'login', 'Access denied', 'Permission denied'];
        
        foreach ($denialIndicators as $indicator) {
            if (stripos($response, $indicator) !== false) {
                return false;
            }
        }
        
        // Check for successful access indicators
        $successIndicators = ['table', 'form', 'data-table', 'btn-primary', 'create', 'edit'];
        
        foreach ($successIndicators as $indicator) {
            if (stripos($response, $indicator) !== false) {
                return true;
            }
        }
        
        return $httpCode >= 200 && $httpCode < 400;
    }

    private function makeRequest($method, $path, $data = null)
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
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

    private function generateFinalReport()
    {
        echo str_repeat("=", 70) . "\n";
        echo "📊 REPORTE FINAL - PERMISOS BASADOS EN ROLES\n";
        echo str_repeat("=", 70) . "\n\n";

        $totalTests = 0;
        $passedTests = 0;
        $securityIssues = [];
        $roleAnalysis = [];

        foreach ($this->testResults as $userType => $results) {
            $userData = $this->testUsers[$userType];
            $correct = 0;
            $total = count($results);
            
            echo "🏷️ {$userData['role']}:\n";
            
            foreach ($results as $module => $result) {
                $totalTests++;
                $status = $result['correct'] ? '✅' : '❌';
                $access = $result['actual'] ? 'PERMITIDO' : 'DENEGADO';
                $expected = $result['expected'] ? 'PERMITIDO' : 'DENEGADO';
                
                echo "  {$status} {$module}: {$access} (esperado: {$expected})\n";
                
                if ($result['correct']) {
                    $passedTests++;
                    $correct++;
                } else {
                    $securityIssues[] = [
                        'role' => $userData['role'],
                        'module' => $module,
                        'actual' => $access,
                        'expected' => $expected
                    ];
                }
            }
            
            $roleScore = $total > 0 ? round(($correct / $total) * 100, 1) : 0;
            echo "  📈 Puntuación: {$correct}/{$total} ({$roleScore}%)\n\n";
            
            $roleAnalysis[$userData['role']] = [
                'score' => $roleScore,
                'correct' => $correct,
                'total' => $total
            ];
        }

        // Summary
        echo "📈 RESUMEN GENERAL:\n";
        echo str_repeat("-", 30) . "\n";
        echo "Total pruebas: {$totalTests}\n";
        echo "Exitosas: {$passedTests}\n";
        echo "Fallidas: " . ($totalTests - $passedTests) . "\n";
        $overallScore = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
        echo "Puntuación general: {$overallScore}%\n\n";

        // Role analysis
        echo "👥 ANÁLISIS POR ROLE:\n";
        echo str_repeat("-", 25) . "\n";
        foreach ($roleAnalysis as $role => $analysis) {
            $status = $analysis['score'] >= 80 ? '✅' : ($analysis['score'] >= 60 ? '⚠️' : '❌');
            echo "{$status} {$role}: {$analysis['score']}% ({$analysis['correct']}/{$analysis['total']})\n";
        }

        // Security issues
        if (!empty($securityIssues)) {
            echo "\n⚠️ PROBLEMAS DE SEGURIDAD:\n";
            echo str_repeat("-", 30) . "\n";
            
            foreach ($securityIssues as $issue) {
                echo "• {$issue['role']} - {$issue['module']}: ";
                echo "obtuvo {$issue['actual']}, esperaba {$issue['expected']}\n";
            }
        } else {
            echo "\n✅ No se detectaron problemas de seguridad\n";
        }

        // Recommendations
        echo "\n🔧 RECOMENDACIONES:\n";
        echo str_repeat("-", 20) . "\n";
        
        if ($overallScore < 80) {
            echo "1. ⚠️ Revisar y corregir permisos identificados como problemáticos\n";
            echo "2. 🛡️ Implementar middleware de permisos más estricto\n";
            echo "3. 🔍 Auditar controladores para validación de permisos\n";
            echo "4. 📝 Documentar roles y permisos esperados\n";
        } else {
            echo "1. ✅ Sistema de permisos funciona correctamente\n";
            echo "2. 🔄 Continuar monitoreando accesos regulares\n";
            echo "3. 📊 Implementar logs de auditoría de permisos\n";
        }

        // Save report
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'overall_score' => $overallScore,
            'total_tests' => $totalTests,
            'passed_tests' => $passedTests,
            'role_analysis' => $roleAnalysis,
            'security_issues' => $securityIssues,
            'detailed_results' => $this->testResults
        ];

        file_put_contents('focused_role_permission_report.json', json_encode($reportData, JSON_PRETTY_PRINT));
        echo "\n💾 Reporte guardado: focused_role_permission_report.json\n";
    }

    public function __destruct()
    {
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
    }
}

// Run focused role-based permission tests
$tester = new FocusedRolePermissionTester();
$tester->runTests();