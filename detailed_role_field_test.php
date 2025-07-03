<?php

/**
 * Detailed Role-Based Field-Level Testing Script
 * Tests CRUD operations and field restrictions by role
 */

class DetailedRoleFieldTester
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
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'detailed_role_test');
    }

    public function runDetailedTests()
    {
        echo "🔍 PRUEBAS DETALLADAS DE ROLES Y PERMISOS DE CAMPO\n";
        echo str_repeat("=", 70) . "\n\n";

        foreach ($this->testUsers as $userType => $userData) {
            echo "👤 ANÁLISIS DETALLADO: {$userData['role']}\n";
            echo str_repeat("-", 50) . "\n";
            
            if ($this->loginUser($userData)) {
                $this->testCRUDOperations($userType, $userData['role']);
                $this->testFieldLevelRestrictions($userType, $userData['role']);
                $this->testBusinessLogicValidation($userType, $userData['role']);
            }
            
            echo "\n";
        }
        
        $this->generateDetailedReport();
    }

    private function loginUser($userData)
    {
        echo "🔐 Iniciando sesión como {$userData['email']}... ";
        
        $loginPage = $this->makeRequest('GET', '/login');
        if (!$loginPage) {
            echo "❌ FALLO\n";
            return false;
        }

        preg_match('/<input[^>]*name=["\']_token["\'][^>]*value=["\']([^"\']*)["\']/', $loginPage, $matches);
        $csrfToken = $matches[1] ?? null;

        if (!$csrfToken) {
            echo "❌ FALLO - Sin CSRF\n";
            return false;
        }

        $loginData = [
            '_token' => $csrfToken,
            'email' => $userData['email'],
            'password' => $userData['password']
        ];

        $response = $this->makeRequest('POST', '/login', $loginData);
        
        if (strpos($response, 'dashboard') !== false) {
            echo "✅ EXITOSO\n";
            return true;
        } else {
            echo "❌ FALLO\n";
            return false;
        }
    }

    private function testCRUDOperations($userType, $role)
    {
        echo "\n📝 Probando operaciones CRUD:\n";
        
        $modules = ['clientes', 'vehiculos', 'servicios', 'empleados', 'ordenes'];
        $crudResults = [];
        
        foreach ($modules as $module) {
            echo "\n  📁 Módulo: {$module}\n";
            
            $moduleResults = [
                'read' => $this->testReadOperation($module),
                'create' => $this->testCreateOperation($module),
                'update' => $this->testUpdateOperation($module),
                'delete' => $this->testDeleteOperation($module)
            ];
            
            foreach ($moduleResults as $operation => $result) {
                $status = $result ? '✅' : '❌';
                $access = $result ? 'PERMITIDO' : 'DENEGADO';
                echo "    {$status} {$operation}: {$access}\n";
            }
            
            $crudResults[$module] = $moduleResults;
        }
        
        $this->testResults[$userType]['crud'] = $crudResults;
    }

    private function testReadOperation($module)
    {
        $response = $this->makeRequest('GET', "/{$module}");
        return $this->isSuccessResponse($response);
    }

    private function testCreateOperation($module)
    {
        $response = $this->makeRequest('GET', "/{$module}/create");
        return $this->isSuccessResponse($response);
    }

    private function testUpdateOperation($module)
    {
        // Try to access edit form for first available record
        $listResponse = $this->makeRequest('GET', "/{$module}");
        if (!$this->isSuccessResponse($listResponse)) {
            return false;
        }
        
        // Extract first ID from the response
        preg_match('/href="[^"]*\/' . $module . '\/(\d+)\/edit"/', $listResponse, $matches);
        $id = $matches[1] ?? '1';
        
        $response = $this->makeRequest('GET', "/{$module}/{$id}/edit");
        return $this->isSuccessResponse($response);
    }

    private function testDeleteOperation($module)
    {
        // For delete, we check if delete buttons/forms are present in the list view
        $response = $this->makeRequest('GET', "/{$module}");
        if (!$this->isSuccessResponse($response)) {
            return false;
        }
        
        // Look for delete buttons or forms
        return strpos($response, 'delete') !== false || 
               strpos($response, 'eliminar') !== false ||
               strpos($response, 'btn-danger') !== false;
    }

    private function testFieldLevelRestrictions($userType, $role)
    {
        echo "\n🔒 Probando restricciones de campo:\n";
        
        $fieldTests = [
            'pricing_fields' => $this->testPricingFields($role),
            'salary_fields' => $this->testSalaryFields($role),
            'status_fields' => $this->testStatusFields($role),
            'sensitive_data' => $this->testSensitiveDataAccess($role)
        ];
        
        foreach ($fieldTests as $testType => $result) {
            $status = $result['status'] ? '✅' : '❌';
            echo "  {$status} {$result['description']}: {$result['details']}\n";
        }
        
        $this->testResults[$userType]['field_restrictions'] = $fieldTests;
    }

    private function testPricingFields($role)
    {
        $response = $this->makeRequest('GET', '/servicios/create');
        
        if (!$this->isSuccessResponse($response)) {
            return [
                'status' => false,
                'description' => 'Campos de precio',
                'details' => 'No puede acceder al formulario de servicios'
            ];
        }
        
        $hasPriceField = strpos($response, 'price') !== false || 
                        strpos($response, 'precio') !== false;
        
        if ($role === 'Mecánico') {
            // Mechanics should not be able to modify prices
            $isReadonly = strpos($response, 'readonly') !== false || 
                         strpos($response, 'disabled') !== false;
            
            if ($hasPriceField && !$isReadonly) {
                return [
                    'status' => false,
                    'description' => 'Campos de precio',
                    'details' => 'Mecánico puede modificar precios (problema de seguridad)'
                ];
            } else {
                return [
                    'status' => true,
                    'description' => 'Campos de precio',
                    'details' => 'Correctamente restringido para mecánico'
                ];
            }
        } else {
            return [
                'status' => true,
                'description' => 'Campos de precio',
                'details' => 'Acceso apropiado para el rol'
            ];
        }
    }

    private function testSalaryFields($role)
    {
        $response = $this->makeRequest('GET', '/empleados');
        
        if (!$this->isSuccessResponse($response)) {
            return [
                'status' => true,
                'description' => 'Campos de salario',
                'details' => 'No puede acceder a empleados (apropiado)'
            ];
        }
        
        $hasSalaryData = strpos($response, 'salary') !== false || 
                        strpos($response, 'salario') !== false ||
                        strpos($response, '$') !== false;
        
        if ($role !== 'Administrador' && $hasSalaryData) {
            return [
                'status' => false,
                'description' => 'Campos de salario',
                'details' => 'Puede ver información salarial (problema de seguridad)'
            ];
        } else {
            return [
                'status' => true,
                'description' => 'Campos de salario',
                'details' => 'Acceso apropiado para el rol'
            ];
        }
    }

    private function testStatusFields($role)
    {
        $response = $this->makeRequest('GET', '/ordenes');
        
        if (!$this->isSuccessResponse($response)) {
            return [
                'status' => false,
                'description' => 'Campos de estado',
                'details' => 'No puede acceder a órdenes'
            ];
        }
        
        $hasStatusFields = strpos($response, 'status') !== false || 
                          strpos($response, 'estado') !== false;
        
        return [
            'status' => true,
            'description' => 'Campos de estado',
            'details' => $hasStatusFields ? 'Puede ver/modificar estados' : 'Sin acceso a estados'
        ];
    }

    private function testSensitiveDataAccess($role)
    {
        // Test access to customer sensitive data
        $response = $this->makeRequest('GET', '/clientes');
        
        if (!$this->isSuccessResponse($response)) {
            return [
                'status' => false,
                'description' => 'Datos sensibles',
                'details' => 'No puede acceder a datos de clientes'
            ];
        }
        
        // Check for potentially sensitive information
        $hasSensitiveData = strpos($response, 'document_number') !== false || 
                           strpos($response, 'cedula') !== false ||
                           strpos($response, 'telefono') !== false;
        
        return [
            'status' => true,
            'description' => 'Datos sensibles',
            'details' => $hasSensitiveData ? 'Puede ver datos sensibles' : 'Acceso limitado a datos'
        ];
    }

    private function testBusinessLogicValidation($userType, $role)
    {
        echo "\n⚖️ Probando lógica de negocio:\n";
        
        $businessTests = [
            'order_status_modification' => $this->testOrderStatusModification($role),
            'price_modification' => $this->testPriceModificationLogic($role),
            'employee_management' => $this->testEmployeeManagementLogic($role),
            'report_generation' => $this->testReportGenerationLogic($role)
        ];
        
        foreach ($businessTests as $testType => $result) {
            $status = $result['status'] ? '✅' : '❌';
            echo "  {$status} {$result['description']}: {$result['details']}\n";
        }
        
        $this->testResults[$userType]['business_logic'] = $businessTests;
    }

    private function testOrderStatusModification($role)
    {
        $response = $this->makeRequest('GET', '/ordenes');
        
        if (!$this->isSuccessResponse($response)) {
            return [
                'status' => ($role === 'Administrador'),
                'description' => 'Modificación estado órdenes',
                'details' => 'No puede acceder a órdenes'
            ];
        }
        
        // Check if can access order edit
        preg_match('/href="[^"]*\/ordenes\/(\d+)\/edit"/', $response, $matches);
        $id = $matches[1] ?? '1';
        
        $editResponse = $this->makeRequest('GET', "/ordenes/{$id}/edit");
        $canEdit = $this->isSuccessResponse($editResponse);
        
        if ($role === 'Mecánico' || $role === 'Administrador') {
            return [
                'status' => $canEdit,
                'description' => 'Modificación estado órdenes',
                'details' => $canEdit ? 'Puede modificar estados' : 'No puede modificar estados'
            ];
        } else {
            return [
                'status' => true,
                'description' => 'Modificación estado órdenes',
                'details' => $canEdit ? 'Acceso limitado apropiado' : 'Sin acceso (apropiado)'
            ];
        }
    }

    private function testPriceModificationLogic($role)
    {
        $response = $this->makeRequest('GET', '/servicios/create');
        
        if (!$this->isSuccessResponse($response)) {
            return [
                'status' => ($role !== 'Mecánico'),
                'description' => 'Modificación de precios',
                'details' => 'No puede acceder a creación de servicios'
            ];
        }
        
        $hasPriceField = strpos($response, 'price') !== false;
        
        if ($role === 'Mecánico' && $hasPriceField) {
            return [
                'status' => false,
                'description' => 'Modificación de precios',
                'details' => 'Mecánico puede modificar precios (problema)'
            ];
        } else {
            return [
                'status' => true,
                'description' => 'Modificación de precios',
                'details' => 'Restricción de precios apropiada'
            ];
        }
    }

    private function testEmployeeManagementLogic($role)
    {
        $response = $this->makeRequest('GET', '/empleados');
        $canAccess = $this->isSuccessResponse($response);
        
        if ($role === 'Administrador') {
            return [
                'status' => $canAccess,
                'description' => 'Gestión de empleados',
                'details' => $canAccess ? 'Admin puede gestionar empleados' : 'Admin sin acceso (problema)'
            ];
        } else {
            return [
                'status' => !$canAccess,
                'description' => 'Gestión de empleados',
                'details' => $canAccess ? 'Acceso no autorizado (problema)' : 'Correctamente restringido'
            ];
        }
    }

    private function testReportGenerationLogic($role)
    {
        $response = $this->makeRequest('GET', '/reportes');
        $canAccess = $this->isSuccessResponse($response);
        
        if ($role === 'Administrador' || $role === 'Recepcionista') {
            return [
                'status' => $canAccess,
                'description' => 'Generación de reportes',
                'details' => $canAccess ? 'Puede generar reportes' : 'Sin acceso a reportes'
            ];
        } else {
            return [
                'status' => !$canAccess,
                'description' => 'Generación de reportes',
                'details' => $canAccess ? 'Acceso no autorizado (problema)' : 'Correctamente restringido'
            ];
        }
    }

    private function isSuccessResponse($response)
    {
        if (!$response) return false;
        
        $httpCode = $this->getLastHttpCode();
        
        // Check for error indicators
        $errorIndicators = ['403', '401', 'Unauthorized', 'Forbidden', 'login'];
        foreach ($errorIndicators as $indicator) {
            if (stripos($response, $indicator) !== false) {
                return false;
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

    private function generateDetailedReport()
    {
        echo str_repeat("=", 80) . "\n";
        echo "📊 REPORTE DETALLADO - ANÁLISIS COMPLETO DE ROLES Y PERMISOS\n";
        echo str_repeat("=", 80) . "\n\n";

        $overallIssues = [];
        $roleScores = [];

        foreach ($this->testResults as $userType => $results) {
            $userData = $this->testUsers[$userType];
            echo "🏷️ ANÁLISIS COMPLETO: {$userData['role']}\n";
            echo str_repeat("-", 50) . "\n";

            // CRUD Analysis
            if (isset($results['crud'])) {
                echo "\n📝 OPERACIONES CRUD:\n";
                $crudScore = 0;
                $crudTotal = 0;
                
                foreach ($results['crud'] as $module => $operations) {
                    echo "  📁 {$module}:\n";
                    foreach ($operations as $operation => $allowed) {
                        $crudTotal++;
                        $status = $allowed ? '✅' : '❌';
                        $access = $allowed ? 'PERMITIDO' : 'DENEGADO';
                        echo "    {$status} {$operation}: {$access}\n";
                        
                        // Score based on expected permissions
                        $expected = $this->shouldHaveOperation($userData['role'], $module, $operation);
                        if ($allowed === $expected) {
                            $crudScore++;
                        } else {
                            $overallIssues[] = [
                                'role' => $userData['role'],
                                'type' => 'CRUD',
                                'issue' => "{$module} - {$operation}",
                                'expected' => $expected ? 'PERMITIDO' : 'DENEGADO',
                                'actual' => $allowed ? 'PERMITIDO' : 'DENEGADO'
                            ];
                        }
                    }
                }
                
                $crudPercentage = $crudTotal > 0 ? round(($crudScore / $crudTotal) * 100, 1) : 0;
                echo "  📈 Puntuación CRUD: {$crudScore}/{$crudTotal} ({$crudPercentage}%)\n";
            }

            // Field Restrictions Analysis
            if (isset($results['field_restrictions'])) {
                echo "\n🔒 RESTRICCIONES DE CAMPO:\n";
                $fieldScore = 0;
                $fieldTotal = 0;
                
                foreach ($results['field_restrictions'] as $testType => $result) {
                    $fieldTotal++;
                    $status = $result['status'] ? '✅' : '❌';
                    echo "  {$status} {$result['description']}: {$result['details']}\n";
                    
                    if ($result['status']) {
                        $fieldScore++;
                    } else {
                        $overallIssues[] = [
                            'role' => $userData['role'],
                            'type' => 'FIELD',
                            'issue' => $result['description'],
                            'details' => $result['details']
                        ];
                    }
                }
                
                $fieldPercentage = $fieldTotal > 0 ? round(($fieldScore / $fieldTotal) * 100, 1) : 0;
                echo "  📈 Puntuación Campos: {$fieldScore}/{$fieldTotal} ({$fieldPercentage}%)\n";
            }

            // Business Logic Analysis
            if (isset($results['business_logic'])) {
                echo "\n⚖️ LÓGICA DE NEGOCIO:\n";
                $businessScore = 0;
                $businessTotal = 0;
                
                foreach ($results['business_logic'] as $testType => $result) {
                    $businessTotal++;
                    $status = $result['status'] ? '✅' : '❌';
                    echo "  {$status} {$result['description']}: {$result['details']}\n";
                    
                    if ($result['status']) {
                        $businessScore++;
                    } else {
                        $overallIssues[] = [
                            'role' => $userData['role'],
                            'type' => 'BUSINESS',
                            'issue' => $result['description'],
                            'details' => $result['details']
                        ];
                    }
                }
                
                $businessPercentage = $businessTotal > 0 ? round(($businessScore / $businessTotal) * 100, 1) : 0;
                echo "  📈 Puntuación Negocio: {$businessScore}/{$businessTotal} ({$businessPercentage}%)\n";
            }

            // Calculate overall role score
            $totalTests = ($crudTotal ?? 0) + ($fieldTotal ?? 0) + ($businessTotal ?? 0);
            $totalScore = ($crudScore ?? 0) + ($fieldScore ?? 0) + ($businessScore ?? 0);
            $overallPercentage = $totalTests > 0 ? round(($totalScore / $totalTests) * 100, 1) : 0;
            
            echo "\n📊 PUNTUACIÓN GENERAL: {$totalScore}/{$totalTests} ({$overallPercentage}%)\n\n";
            
            $roleScores[$userData['role']] = [
                'score' => $overallPercentage,
                'total_score' => $totalScore,
                'total_tests' => $totalTests
            ];
        }

        // Overall System Analysis
        echo "🌟 ANÁLISIS GENERAL DEL SISTEMA:\n";
        echo str_repeat("-", 40) . "\n";
        
        $systemScore = 0;
        $systemTotal = 0;
        
        foreach ($roleScores as $role => $scores) {
            $systemScore += $scores['total_score'];
            $systemTotal += $scores['total_tests'];
            
            $status = $scores['score'] >= 80 ? '✅' : ($scores['score'] >= 60 ? '⚠️' : '❌');
            echo "{$status} {$role}: {$scores['score']}%\n";
        }
        
        $overallSystemScore = $systemTotal > 0 ? round(($systemScore / $systemTotal) * 100, 1) : 0;
        echo "\n🎯 PUNTUACIÓN GENERAL DEL SISTEMA: {$overallSystemScore}%\n";

        // Critical Issues Summary
        if (!empty($overallIssues)) {
            echo "\n🚨 PROBLEMAS CRÍTICOS IDENTIFICADOS:\n";
            echo str_repeat("-", 40) . "\n";
            
            $criticalCount = 0;
            $warningCount = 0;
            
            foreach ($overallIssues as $issue) {
                if ($issue['type'] === 'CRUD' && strpos($issue['issue'], 'empleados') !== false) {
                    $criticalCount++;
                    echo "🔴 CRÍTICO: {$issue['role']} - {$issue['issue']}\n";
                } else {
                    $warningCount++;
                    echo "🟡 ADVERTENCIA: {$issue['role']} - {$issue['issue']}\n";
                }
            }
            
            echo "\n📈 Resumen de problemas:\n";
            echo "  🔴 Críticos: {$criticalCount}\n";
            echo "  🟡 Advertencias: {$warningCount}\n";
        } else {
            echo "\n✅ No se identificaron problemas críticos\n";
        }

        // Security Recommendations
        echo "\n🛡️ RECOMENDACIONES DE SEGURIDAD:\n";
        echo str_repeat("-", 35) . "\n";
        
        if ($overallSystemScore < 70) {
            echo "1. 🚨 URGENTE: Revisar y corregir permisos críticos\n";
            echo "2. 🔒 Implementar middleware más estricto\n";
            echo "3. 🧪 Realizar pruebas de penetración\n";
            echo "4. 📝 Documentar matriz de permisos\n";
        } elseif ($overallSystemScore < 85) {
            echo "1. ⚠️ Corregir permisos identificados como problemáticos\n";
            echo "2. 🔍 Auditar controladores regularmente\n";
            echo "3. 📊 Implementar logging de permisos\n";
            echo "4. 🔄 Revisar roles trimestralmente\n";
        } else {
            echo "1. ✅ Sistema de permisos bien configurado\n";
            echo "2. 🔄 Mantener monitoreo regular\n";
            echo "3. 📊 Implementar alertas de seguridad\n";
            echo "4. 🎓 Capacitar usuarios en sus roles\n";
        }

        // Save comprehensive report
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'system_score' => $overallSystemScore,
            'role_scores' => $roleScores,
            'critical_issues' => array_filter($overallIssues, fn($i) => $i['type'] === 'CRUD'),
            'warnings' => array_filter($overallIssues, fn($i) => $i['type'] !== 'CRUD'),
            'detailed_results' => $this->testResults,
            'recommendations' => $this->generateRecommendations($overallSystemScore)
        ];

        file_put_contents('detailed_role_permission_report.json', json_encode($reportData, JSON_PRETTY_PRINT));
        echo "\n💾 Reporte detallado guardado: detailed_role_permission_report.json\n";
    }

    private function shouldHaveOperation($role, $module, $operation)
    {
        $permissions = [
            'Administrador' => [
                'clientes' => ['read', 'create', 'update', 'delete'],
                'vehiculos' => ['read', 'create', 'update', 'delete'],
                'servicios' => ['read', 'create', 'update', 'delete'],
                'empleados' => ['read', 'create', 'update', 'delete'],
                'ordenes' => ['read', 'create', 'update', 'delete']
            ],
            'Mecánico' => [
                'clientes' => ['read'],
                'vehiculos' => ['read', 'update'],
                'servicios' => ['read'],
                'empleados' => [],
                'ordenes' => ['read', 'update']
            ],
            'Recepcionista' => [
                'clientes' => ['read', 'create', 'update'],
                'vehiculos' => ['read', 'create', 'update'],
                'servicios' => ['read'],
                'empleados' => [],
                'ordenes' => ['read', 'create', 'update']
            ]
        ];
        
        return in_array($operation, $permissions[$role][$module] ?? []);
    }

    private function generateRecommendations($score)
    {
        if ($score < 70) {
            return [
                'priority' => 'CRÍTICA',
                'actions' => [
                    'Revisar inmediatamente permisos de empleados',
                    'Implementar middleware de autorización estricto',
                    'Realizar auditoría de seguridad completa',
                    'Capacitar personal en roles y responsabilidades'
                ]
            ];
        } elseif ($score < 85) {
            return [
                'priority' => 'ALTA',
                'actions' => [
                    'Corregir permisos problemáticos identificados',
                    'Implementar logging de acciones sensibles',
                    'Revisar roles cada trimestre',
                    'Documentar matriz de permisos'
                ]
            ];
        } else {
            return [
                'priority' => 'MANTENIMIENTO',
                'actions' => [
                    'Mantener sistema de permisos actual',
                    'Monitorear accesos regularmente',
                    'Actualizar documentación según cambios',
                    'Capacitar nuevos empleados'
                ]
            ];
        }
    }

    public function __destruct()
    {
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
    }
}

// Run detailed role-based permission tests
$tester = new DetailedRoleFieldTester();
$tester->runDetailedTests();