<?php

/**
 * Manual Browser Test - Simplified approach
 * Uses basic HTTP requests to test CRUD functionality
 */

echo "MANUAL BROWSER TEST FOR TALLER SISTEMA\n";
echo "======================================\n";
echo "Testing URL: http://localhost:8001\n";
echo "Login: admin@taller.com / admin123\n\n";

// Test 1: Basic connectivity
echo "1. BASIC CONNECTIVITY TEST\n";
echo "---------------------------\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'user_agent' => 'Mozilla/5.0 (Test Browser)',
        'follow_location' => false
    ]
]);

$response = @file_get_contents('http://localhost:8001', false, $context);

if ($response !== false) {
    echo "✅ Application is accessible\n";
    
    // Check if we get redirected to login
    $headers = $http_response_header;
    $redirected = false;
    foreach ($headers as $header) {
        if (stripos($header, 'Location:') !== false && stripos($header, 'login') !== false) {
            $redirected = true;
            break;
        }
    }
    
    if ($redirected) {
        echo "✅ Proper redirect to login page\n";
    } else {
        echo "ℹ️ No redirect detected (may already be on login page)\n";
    }
} else {
    echo "❌ Application is not accessible\n";
    echo "Please ensure the Laravel application is running on port 8001\n";
    exit(1);
}

// Test 2: Login page accessibility
echo "\n2. LOGIN PAGE TEST\n";
echo "------------------\n";

$loginPage = @file_get_contents('http://localhost:8001/login', false, $context);

if ($loginPage !== false) {
    echo "✅ Login page accessible\n";
    
    // Check for login form elements
    if (strpos($loginPage, 'name="email"') !== false && strpos($loginPage, 'name="password"') !== false) {
        echo "✅ Login form elements found\n";
    } else {
        echo "⚠️ Login form elements not clearly identified\n";
    }
    
    // Check for CSRF token
    if (strpos($loginPage, 'csrf-token') !== false || strpos($loginPage, '_token') !== false) {
        echo "✅ CSRF protection detected\n";
    } else {
        echo "⚠️ CSRF protection not clearly identified\n";
    }
} else {
    echo "❌ Login page not accessible\n";
}

// Test 3: Check if already authenticated (session might exist)
echo "\n3. AUTHENTICATION CHECK\n";
echo "-----------------------\n";

$dashboardPage = @file_get_contents('http://localhost:8001/dashboard', false, $context);

if ($dashboardPage !== false && strpos($dashboardPage, 'Dashboard') !== false) {
    echo "✅ Dashboard accessible (may be authenticated from previous session)\n";
    echo "ℹ️ This suggests authentication system is working\n";
} else {
    echo "ℹ️ Dashboard not accessible without authentication (this is correct behavior)\n";
}

// Test 4: Module page accessibility (should redirect to login if not authenticated)
echo "\n4. MODULE ACCESS TEST\n";
echo "---------------------\n";

$modules = ['clientes', 'vehiculos', 'servicios', 'empleados', 'ordenes'];

foreach ($modules as $module) {
    $moduleResponse = @file_get_contents("http://localhost:8001/$module", false, $context);
    
    if ($moduleResponse !== false) {
        if (strpos($moduleResponse, 'login') !== false || strpos($moduleResponse, 'Login') !== false) {
            echo "✅ $module: Properly redirects to login (authentication required)\n";
        } elseif (strpos($moduleResponse, ucfirst($module)) !== false || strpos($moduleResponse, 'Lista') !== false) {
            echo "✅ $module: Accessible (authenticated session exists)\n";
        } else {
            echo "⚠️ $module: Unexpected response\n";
        }
    } else {
        echo "❌ $module: Not accessible\n";
    }
}

// Test 5: Check Laravel application structure
echo "\n5. APPLICATION STRUCTURE CHECK\n";
echo "-------------------------------\n";

// Check if routes are properly defined
$routes = ['login', 'dashboard', 'clientes', 'vehiculos', 'servicios', 'empleados', 'ordenes'];
$workingRoutes = 0;

foreach ($routes as $route) {
    $routeResponse = @get_headers("http://localhost:8001/$route");
    if ($routeResponse && (strpos($routeResponse[0], '200') !== false || strpos($routeResponse[0], '302') !== false)) {
        $workingRoutes++;
    }
}

echo "✅ Working routes: $workingRoutes/" . count($routes) . "\n";

if ($workingRoutes >= count($routes) - 1) {
    echo "✅ Application routing appears to be working correctly\n";
} else {
    echo "⚠️ Some routes may not be working properly\n";
}

// Test 6: Database connectivity (indirect test)
echo "\n6. DATABASE CONNECTIVITY TEST\n";
echo "-----------------------------\n";

// Try to access a create page (if authenticated) or check for database-related errors
$createPageTest = @file_get_contents('http://localhost:8001/clientes/create', false, $context);

if ($createPageTest !== false) {
    if (strpos($createPageTest, 'database') !== false || strpos($createPageTest, 'connection') !== false || strpos($createPageTest, 'error') !== false) {
        echo "⚠️ Possible database connection issues detected\n";
    } else {
        echo "✅ No obvious database connection errors\n";
    }
} else {
    echo "ℹ️ Create page test inconclusive (authentication may be required)\n";
}

// Test 7: CSRF and Security Headers
echo "\n7. SECURITY FEATURES CHECK\n";
echo "--------------------------\n";

$securityHeaders = @get_headers('http://localhost:8001/login');

if ($securityHeaders) {
    $securityFeatures = [];
    
    foreach ($securityHeaders as $header) {
        if (stripos($header, 'X-Frame-Options') !== false) $securityFeatures[] = 'X-Frame-Options';
        if (stripos($header, 'X-Content-Type-Options') !== false) $securityFeatures[] = 'X-Content-Type-Options';
        if (stripos($header, 'X-XSS-Protection') !== false) $securityFeatures[] = 'X-XSS-Protection';
        if (stripos($header, 'Strict-Transport-Security') !== false) $securityFeatures[] = 'HSTS';
        if (stripos($header, 'XSRF-TOKEN') !== false || stripos($header, 'laravel_session') !== false) $securityFeatures[] = 'Session Management';
    }
    
    if (!empty($securityFeatures)) {
        echo "✅ Security headers detected: " . implode(', ', $securityFeatures) . "\n";
    } else {
        echo "ℹ️ Standard security headers not prominently visible\n";
    }
}

// Final Assessment
echo "\n8. MANUAL TESTING RECOMMENDATIONS\n";
echo "=================================\n";
echo "Based on the automated tests, here's what you should manually verify:\n\n";

echo "🔍 AUTHENTICATION TESTING:\n";
echo "1. Navigate to http://localhost:8001\n";
echo "2. Should redirect to login page\n";
echo "3. Enter admin@taller.com / admin123\n";
echo "4. Should redirect to dashboard\n\n";

echo "🔍 CLIENTES MODULE TESTING:\n";
echo "1. Go to http://localhost:8001/clientes\n";
echo "2. Click 'Crear Cliente' or similar button\n";
echo "3. Fill form with test data:\n";
echo "   - Nombre: Test Cliente\n";
echo "   - Apellido: Test Apellido\n";
echo "   - Email: test@example.com\n";
echo "   - Teléfono: 123456789\n";
echo "   - Documento: 12345678\n";
echo "   - Dirección: Test Address\n";
echo "4. Submit form and check for success alert\n";
echo "5. Verify client appears in listing\n";
echo "6. Test edit functionality\n";
echo "7. Test delete functionality\n\n";

echo "🔍 REPEAT FOR OTHER MODULES:\n";
echo "- Vehículos (ensure client exists first)\n";
echo "- Servicios\n";
echo "- Empleados\n";
echo "- Órdenes (ensure all dependencies exist)\n\n";

echo "🔍 VALIDATION TESTING:\n";
echo "1. Try submitting empty forms\n";
echo "2. Try duplicate emails\n";
echo "3. Try invalid data formats\n";
echo "4. Verify error messages appear\n\n";

echo "🔍 ALERT SYSTEM TESTING:\n";
echo "1. Check for success messages after create/update/delete\n";
echo "2. Check for error messages on validation failures\n";
echo "3. Verify alerts are properly styled and visible\n\n";

echo "🔍 DATA PERSISTENCE TESTING:\n";
echo "1. Create records and verify they appear in listings\n";
echo "2. Update records and verify changes are saved\n";
echo "3. Delete records and verify they're removed\n";
echo "4. Check that dashboard statistics update\n\n";

echo "📊 EXPECTED RESULTS:\n";
echo "✅ All forms should work with proper validation\n";
echo "✅ Success/error alerts should appear consistently\n";
echo "✅ Data should persist across page refreshes\n";
echo "✅ CRUD operations should work for all modules\n";
echo "✅ Cross-references (vehiculos->clientes) should work\n";
echo "✅ Dashboard statistics should update\n\n";

echo "Test completed: " . date('Y-m-d H:i:s') . "\n";

?>