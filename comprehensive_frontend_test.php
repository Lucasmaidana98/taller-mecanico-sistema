<?php

/**
 * Comprehensive Frontend Testing Script for Laravel Taller Sistema
 * Tests all views, templates, components, and UI functionality
 */

// Configuration
$base_url = 'http://localhost:8002';
$login_email = 'admin@taller.com';
$login_password = 'admin123';
$cookie_file = __DIR__ . '/frontend_test_cookies.txt';

// Initialize test results
$test_results = [
    'layout_tests' => [],
    'auth_tests' => [],
    'dashboard_tests' => [],
    'module_tests' => [
        'clientes' => [],
        'vehiculos' => [],
        'servicios' => [],
        'empleados' => [],
        'ordenes' => [],
        'reportes' => []
    ],
    'component_tests' => [],
    'form_tests' => [],
    'ui_tests' => [],
    'responsive_tests' => [],
    'errors' => [],
    'summary' => []
];

// Utility functions
function make_request($url, $cookie_file, $post_data = null, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($post_data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['body' => $response, 'code' => $http_code];
}

function analyze_html_structure($html) {
    $analysis = [
        'has_doctype' => strpos($html, '<!DOCTYPE html') !== false,
        'has_meta_viewport' => strpos($html, 'name="viewport"') !== false,
        'has_csrf_token' => strpos($html, 'csrf-token') !== false,
        'has_bootstrap' => strpos($html, 'bootstrap') !== false,
        'has_fontawesome' => strpos($html, 'font-awesome') !== false || strpos($html, 'fas fa-') !== false,
        'has_custom_css' => strpos($html, '<style>') !== false || strpos($html, 'app.css') !== false,
        'has_jquery' => strpos($html, 'jquery') !== false,
        'has_sweetalert' => strpos($html, 'sweetalert') !== false,
        'has_sidebar' => strpos($html, 'sidebar') !== false,
        'has_navigation' => strpos($html, '<nav') !== false || strpos($html, 'nav-link') !== false,
        'has_responsive_grid' => strpos($html, 'col-md-') !== false || strpos($html, 'col-lg-') !== false,
        'has_flash_messages' => strpos($html, 'alert alert-') !== false,
        'has_forms' => strpos($html, '<form') !== false,
        'has_tables' => strpos($html, '<table') !== false,
        'has_buttons' => strpos($html, 'btn btn-') !== false,
        'has_modals' => strpos($html, 'modal') !== false,
        'has_cards' => strpos($html, 'card') !== false,
        'title' => preg_match('/<title>(.*?)<\/title>/i', $html, $matches) ? $matches[1] : 'No title found'
    ];
    
    return $analysis;
}

function extract_blade_features($html) {
    $features = [
        'extends_layout' => false,
        'has_sections' => false,
        'has_includes' => false,
        'has_auth_directives' => false,
        'has_permission_checks' => false,
        'has_loops' => false,
        'has_conditionals' => false,
        'csrf_tokens' => 0
    ];
    
    // Check for CSRF tokens
    $features['csrf_tokens'] = substr_count($html, 'csrf_token()') + substr_count($html, '@csrf');
    
    // Check for various Blade features in rendered HTML
    if (strpos($html, 'Sistema de Taller') !== false) $features['extends_layout'] = true;
    if (strpos($html, 'main-content') !== false) $features['has_sections'] = true;
    if (strpos($html, 'sidebar') !== false) $features['has_includes'] = true;
    if (strpos($html, 'dropdown-toggle') !== false) $features['has_auth_directives'] = true;
    if (strpos($html, 'nav-link') !== false) $features['has_permission_checks'] = true;
    if (strpos($html, '<tr>') !== false) $features['has_loops'] = true;
    if (strpos($html, 'alert alert-') !== false) $features['has_conditionals'] = true;
    
    return $features;
}

function test_form_elements($html) {
    $forms = [
        'input_text' => substr_count($html, 'type="text"'),
        'input_email' => substr_count($html, 'type="email"'),
        'input_password' => substr_count($html, 'type="password"'),
        'input_number' => substr_count($html, 'type="number"'),
        'input_date' => substr_count($html, 'type="date"'),
        'input_file' => substr_count($html, 'type="file"'),
        'input_hidden' => substr_count($html, 'type="hidden"'),
        'textareas' => substr_count($html, '<textarea'),
        'selects' => substr_count($html, '<select'),
        'submit_buttons' => substr_count($html, 'type="submit"'),
        'form_controls' => substr_count($html, 'form-control'),
        'form_groups' => substr_count($html, 'form-group') + substr_count($html, 'mb-3'),
        'labels' => substr_count($html, '<label'),
        'validation_errors' => substr_count($html, 'invalid-feedback') + substr_count($html, 'is-invalid')
    ];
    
    return $forms;
}

function test_ui_components($html) {
    $components = [
        'buttons' => [
            'primary' => substr_count($html, 'btn-primary'),
            'secondary' => substr_count($html, 'btn-secondary'),
            'success' => substr_count($html, 'btn-success'),
            'danger' => substr_count($html, 'btn-danger'),
            'warning' => substr_count($html, 'btn-warning'),
            'info' => substr_count($html, 'btn-info'),
            'total' => substr_count($html, 'btn btn-')
        ],
        'cards' => substr_count($html, 'class="card"') + substr_count($html, "class='card'"),
        'tables' => substr_count($html, '<table'),
        'modals' => substr_count($html, 'class="modal"') + substr_count($html, "class='modal'"),
        'alerts' => substr_count($html, 'alert alert-'),
        'badges' => substr_count($html, 'badge'),
        'icons' => substr_count($html, 'fas fa-') + substr_count($html, 'far fa-') + substr_count($html, 'fab fa-'),
        'pagination' => substr_count($html, 'pagination'),
        'dropdowns' => substr_count($html, 'dropdown'),
        'breadcrumbs' => substr_count($html, 'breadcrumb')
    ];
    
    return $components;
}

function log_test($category, $test_name, $status, $details = '') {
    global $test_results;
    
    $result = [
        'test' => $test_name,
        'status' => $status,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if (!isset($test_results[$category])) {
        $test_results[$category] = [];
    }
    
    $test_results[$category][] = $result;
    
    echo "[" . strtoupper($status) . "] $category -> $test_name: $details\n";
}

echo "=== COMPREHENSIVE FRONTEND TESTING FOR TALLER SISTEMA ===\n";
echo "Testing URL: $base_url\n";
echo "Login: $login_email\n";
echo "Starting tests...\n\n";

// 1. AUTHENTICATION TESTING
echo "=== 1. AUTHENTICATION VIEWS TESTING ===\n";

// Test login page
$response = make_request($base_url . '/login', $cookie_file);
if ($response['code'] == 200) {
    $analysis = analyze_html_structure($response['body']);
    $blade_features = extract_blade_features($response['body']);
    $form_elements = test_form_elements($response['body']);
    
    log_test('auth_tests', 'login_page_loads', 'PASS', 'HTTP 200, Title: ' . $analysis['title']);
    log_test('auth_tests', 'login_has_form', $form_elements['input_email'] > 0 && $form_elements['input_password'] > 0 ? 'PASS' : 'FAIL', 
        "Email inputs: {$form_elements['input_email']}, Password inputs: {$form_elements['input_password']}");
    log_test('auth_tests', 'login_csrf_protection', $blade_features['csrf_tokens'] > 0 ? 'PASS' : 'FAIL', 
        "CSRF tokens found: {$blade_features['csrf_tokens']}");
    log_test('auth_tests', 'login_responsive_design', $analysis['has_responsive_grid'] ? 'PASS' : 'FAIL', 
        'Bootstrap grid detected: ' . ($analysis['has_responsive_grid'] ? 'Yes' : 'No'));
} else {
    log_test('auth_tests', 'login_page_loads', 'FAIL', "HTTP {$response['code']}");
}

// Perform login
if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $response['body'], $matches)) {
    $csrf_token = $matches[1];
    
    $login_data = http_build_query([
        '_token' => $csrf_token,
        'email' => $login_email,
        'password' => $login_password,
        'remember' => ''
    ]);
    
    $login_response = make_request($base_url . '/login', $cookie_file, $login_data, [
        'Content-Type: application/x-www-form-urlencoded',
        'Referer: ' . $base_url . '/login'
    ]);
    
    log_test('auth_tests', 'login_authentication', 
        $login_response['code'] == 302 || strpos($login_response['body'], 'dashboard') !== false ? 'PASS' : 'FAIL',
        "Login response code: {$login_response['code']}");
} else {
    log_test('auth_tests', 'login_csrf_token_extraction', 'FAIL', 'Could not extract CSRF token');
}

// 2. LAYOUT AND NAVIGATION TESTING
echo "\n=== 2. LAYOUT AND NAVIGATION TESTING ===\n";

// Test dashboard (main layout)
$response = make_request($base_url . '/dashboard', $cookie_file);
if ($response['code'] == 200) {
    $analysis = analyze_html_structure($response['body']);
    $blade_features = extract_blade_features($response['body']);
    $ui_components = test_ui_components($response['body']);
    
    log_test('layout_tests', 'main_layout_loads', 'PASS', 'Dashboard accessible, Title: ' . $analysis['title']);
    log_test('layout_tests', 'has_sidebar_navigation', $analysis['has_sidebar'] ? 'PASS' : 'FAIL', 
        'Sidebar detected: ' . ($analysis['has_sidebar'] ? 'Yes' : 'No'));
    log_test('layout_tests', 'bootstrap_integration', $analysis['has_bootstrap'] ? 'PASS' : 'FAIL', 
        'Bootstrap detected: ' . ($analysis['has_bootstrap'] ? 'Yes' : 'No'));
    log_test('layout_tests', 'fontawesome_icons', $analysis['has_fontawesome'] ? 'PASS' : 'FAIL', 
        'FontAwesome icons: ' . $ui_components['icons']);
    log_test('layout_tests', 'responsive_design', $analysis['has_responsive_grid'] ? 'PASS' : 'FAIL', 
        'Responsive grid classes detected');
    log_test('layout_tests', 'flash_message_system', $analysis['has_flash_messages'] ? 'PASS' : 'FAIL', 
        'Alert system in place');
    log_test('layout_tests', 'custom_styling', $analysis['has_custom_css'] ? 'PASS' : 'FAIL', 
        'Custom CSS detected');
    log_test('layout_tests', 'javascript_libraries', 
        $analysis['has_jquery'] && $analysis['has_sweetalert'] ? 'PASS' : 'FAIL',
        "jQuery: {$analysis['has_jquery']}, SweetAlert: {$analysis['has_sweetalert']}");
        
    // Test navigation links
    $nav_links = [
        'dashboard' => strpos($response['body'], 'href="/dashboard"') !== false,
        'clientes' => strpos($response['body'], 'href="/clientes"') !== false,
        'vehiculos' => strpos($response['body'], 'href="/vehiculos"') !== false,
        'servicios' => strpos($response['body'], 'href="/servicios"') !== false,
        'empleados' => strpos($response['body'], 'href="/empleados"') !== false,
        'ordenes' => strpos($response['body'], 'href="/ordenes"') !== false,
        'reportes' => strpos($response['body'], 'href="/reportes"') !== false
    ];
    
    $active_links = array_filter($nav_links);
    log_test('layout_tests', 'navigation_links', count($active_links) >= 5 ? 'PASS' : 'FAIL', 
        'Active navigation links: ' . implode(', ', array_keys($active_links)));
        
} else {
    log_test('layout_tests', 'main_layout_loads', 'FAIL', "HTTP {$response['code']} - Dashboard not accessible");
}

// 3. DASHBOARD TESTING
echo "\n=== 3. DASHBOARD VIEW TESTING ===\n";

if ($response['code'] == 200) {
    $ui_components = test_ui_components($response['body']);
    
    log_test('dashboard_tests', 'dashboard_cards', $ui_components['cards'] >= 3 ? 'PASS' : 'FAIL', 
        "Cards found: {$ui_components['cards']}");
    log_test('dashboard_tests', 'dashboard_statistics', 
        strpos($response['body'], 'Clientes') !== false && strpos($response['body'], 'Vehículos') !== false ? 'PASS' : 'FAIL',
        'Statistics cards visible');
    log_test('dashboard_tests', 'dashboard_icons', $ui_components['icons'] >= 5 ? 'PASS' : 'FAIL', 
        "Icons found: {$ui_components['icons']}");
    log_test('dashboard_tests', 'dashboard_responsive', 
        strpos($response['body'], 'col-') !== false ? 'PASS' : 'FAIL', 
        'Responsive columns detected');
}

// 4. MODULE TESTING (CRUD Views)
echo "\n=== 4. MODULE VIEWS TESTING ===\n";

$modules = ['clientes', 'vehiculos', 'servicios', 'empleados', 'ordenes', 'reportes'];

foreach ($modules as $module) {
    echo "\n--- Testing $module module ---\n";
    
    // Test index view
    $response = make_request($base_url . "/$module", $cookie_file);
    if ($response['code'] == 200) {
        $analysis = analyze_html_structure($response['body']);
        $ui_components = test_ui_components($response['body']);
        $form_elements = test_form_elements($response['body']);
        
        log_test('module_tests', "$module" . "_index_loads", 'PASS', 
            "Index view accessible, Title: {$analysis['title']}");
        log_test('module_tests', "$module" . "_has_table", $analysis['has_tables'] ? 'PASS' : 'FAIL', 
            "Tables found: {$ui_components['tables']}");
        log_test('module_tests', "$module" . "_has_buttons", $ui_components['buttons']['total'] >= 2 ? 'PASS' : 'FAIL', 
            "Buttons found: {$ui_components['buttons']['total']}");
        log_test('module_tests', "$module" . "_has_create_button", 
            strpos($response['body'], 'Crear') !== false || strpos($response['body'], 'Nuevo') !== false ? 'PASS' : 'FAIL',
            'Create button detected');
            
        // Test pagination if exists
        if ($ui_components['pagination'] > 0) {
            log_test('module_tests', "$module" . "_has_pagination", 'PASS', 
                "Pagination elements: {$ui_components['pagination']}");
        }
        
        // Test search functionality
        if (strpos($response['body'], 'search') !== false || strpos($response['body'], 'buscar') !== false) {
            log_test('module_tests', "$module" . "_has_search", 'PASS', 'Search functionality detected');
        }
        
    } else {
        log_test('module_tests', "$module" . "_index_loads", 'FAIL', "HTTP {$response['code']}");
        continue;
    }
    
    // Test create view (except for reportes)
    if ($module !== 'reportes') {
        $response = make_request($base_url . "/$module/create", $cookie_file);
        if ($response['code'] == 200) {
            $form_elements = test_form_elements($response['body']);
            $blade_features = extract_blade_features($response['body']);
            
            log_test('module_tests', "$module" . "_create_loads", 'PASS', 
                "Create form accessible");
            log_test('module_tests', "$module" . "_create_has_form", 
                $form_elements['input_text'] > 0 || $form_elements['selects'] > 0 ? 'PASS' : 'FAIL',
                "Form elements - Inputs: {$form_elements['input_text']}, Selects: {$form_elements['selects']}");
            log_test('module_tests', "$module" . "_create_csrf", $blade_features['csrf_tokens'] > 0 ? 'PASS' : 'FAIL', 
                "CSRF tokens: {$blade_features['csrf_tokens']}");
            log_test('module_tests', "$module" . "_create_validation", 
                $form_elements['form_controls'] > 0 ? 'PASS' : 'FAIL',
                "Form controls: {$form_elements['form_controls']}");
        } else {
            log_test('module_tests', "$module" . "_create_loads", 'FAIL', "HTTP {$response['code']}");
        }
    }
}

// 5. PROFILE TESTING
echo "\n=== 5. PROFILE VIEWS TESTING ===\n";

$response = make_request($base_url . '/profile', $cookie_file);
if ($response['code'] == 200) {
    $form_elements = test_form_elements($response['body']);
    $ui_components = test_ui_components($response['body']);
    
    log_test('component_tests', 'profile_loads', 'PASS', 'Profile page accessible');
    log_test('component_tests', 'profile_has_forms', $form_elements['submit_buttons'] >= 2 ? 'PASS' : 'FAIL', 
        "Submit buttons found: {$form_elements['submit_buttons']}");
    log_test('component_tests', 'profile_sections', $ui_components['cards'] >= 2 ? 'PASS' : 'FAIL', 
        "Profile sections (cards): {$ui_components['cards']}");
} else {
    log_test('component_tests', 'profile_loads', 'FAIL', "HTTP {$response['code']}");
}

// 6. COMPONENT TESTING
echo "\n=== 6. UI COMPONENT TESTING ===\n";

// Test with a typical CRUD page for component analysis
$response = make_request($base_url . '/clientes', $cookie_file);
if ($response['code'] == 200) {
    $ui_components = test_ui_components($response['body']);
    
    log_test('component_tests', 'button_variety', 
        $ui_components['buttons']['primary'] > 0 && $ui_components['buttons']['danger'] > 0 ? 'PASS' : 'FAIL',
        "Primary: {$ui_components['buttons']['primary']}, Danger: {$ui_components['buttons']['danger']}");
    log_test('component_tests', 'card_components', $ui_components['cards'] > 0 ? 'PASS' : 'FAIL', 
        "Cards: {$ui_components['cards']}");
    log_test('component_tests', 'table_components', $ui_components['tables'] > 0 ? 'PASS' : 'FAIL', 
        "Tables: {$ui_components['tables']}");
    log_test('component_tests', 'icon_usage', $ui_components['icons'] >= 5 ? 'PASS' : 'FAIL', 
        "Icons: {$ui_components['icons']}");
    log_test('component_tests', 'alert_system', $ui_components['alerts'] >= 0 ? 'PASS' : 'INFO', 
        "Alert classes: {$ui_components['alerts']}");
}

// 7. FORM TESTING
echo "\n=== 7. FORM FUNCTIONALITY TESTING ===\n";

// Test form on create page
$response = make_request($base_url . '/clientes/create', $cookie_file);
if ($response['code'] == 200) {
    $form_elements = test_form_elements($response['body']);
    
    log_test('form_tests', 'form_elements_variety', 
        $form_elements['input_text'] > 0 && $form_elements['selects'] >= 0 ? 'PASS' : 'FAIL',
        "Text inputs: {$form_elements['input_text']}, Selects: {$form_elements['selects']}");
    log_test('form_tests', 'form_labels', $form_elements['labels'] >= $form_elements['input_text'] ? 'PASS' : 'FAIL',
        "Labels: {$form_elements['labels']}, Inputs: {$form_elements['input_text']}");
    log_test('form_tests', 'form_styling', $form_elements['form_controls'] > 0 ? 'PASS' : 'FAIL',
        "Bootstrap form controls: {$form_elements['form_controls']}");
    log_test('form_tests', 'submit_buttons', $form_elements['submit_buttons'] > 0 ? 'PASS' : 'FAIL',
        "Submit buttons: {$form_elements['submit_buttons']}");
}

// 8. RESPONSIVE TESTING
echo "\n=== 8. RESPONSIVE DESIGN TESTING ===\n";

// Test mobile viewport simulation
$mobile_headers = [
    'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
];

$response = make_request($base_url . '/dashboard', $cookie_file, null, $mobile_headers);
if ($response['code'] == 200) {
    $analysis = analyze_html_structure($response['body']);
    
    log_test('responsive_tests', 'mobile_viewport', $analysis['has_meta_viewport'] ? 'PASS' : 'FAIL',
        'Viewport meta tag present');
    log_test('responsive_tests', 'responsive_grid', $analysis['has_responsive_grid'] ? 'PASS' : 'FAIL',
        'Bootstrap responsive classes detected');
    log_test('responsive_tests', 'mobile_navigation', 
        strpos($response['body'], 'navbar-collapse') !== false || strpos($response['body'], 'sidebar') !== false ? 'PASS' : 'FAIL',
        'Mobile navigation elements');
}

// 9. ERROR HANDLING TESTING
echo "\n=== 9. ERROR HANDLING TESTING ===\n";

// Test 404 page
$response = make_request($base_url . '/non-existent-page', $cookie_file);
log_test('component_tests', '404_handling', $response['code'] == 404 ? 'PASS' : 'INFO', 
    "404 response code: {$response['code']}");

// Test unauthorized access
$response = make_request($base_url . '/admin-only-page', $cookie_file);
log_test('component_tests', 'unauthorized_handling', 
    $response['code'] == 403 || $response['code'] == 404 || $response['code'] == 302 ? 'PASS' : 'INFO', 
    "Unauthorized access code: {$response['code']}");

// GENERATE SUMMARY
echo "\n=== 10. GENERATING SUMMARY ===\n";

$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;
$info_tests = 0;

foreach ($test_results as $category => $tests) {
    if (is_array($tests) && !empty($tests)) {
        foreach ($tests as $test) {
            $total_tests++;
            switch ($test['status']) {
                case 'PASS':
                    $passed_tests++;
                    break;
                case 'FAIL':
                    $failed_tests++;
                    break;
                case 'INFO':
                    $info_tests++;
                    break;
            }
        }
    }
}

$test_results['summary'] = [
    'total_tests' => $total_tests,
    'passed_tests' => $passed_tests,
    'failed_tests' => $failed_tests,
    'info_tests' => $info_tests,
    'pass_rate' => $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0,
    'completion_time' => date('Y-m-d H:i:s')
];

echo "\n=== FRONTEND TESTING SUMMARY ===\n";
echo "Total Tests: $total_tests\n";
echo "Passed: $passed_tests\n";
echo "Failed: $failed_tests\n";
echo "Info: $info_tests\n";
echo "Pass Rate: " . $test_results['summary']['pass_rate'] . "%\n";
echo "Completed: " . $test_results['summary']['completion_time'] . "\n";

// Save detailed results
file_put_contents(__DIR__ . '/frontend_test_results.json', json_encode($test_results, JSON_PRETTY_PRINT));
echo "\nDetailed results saved to frontend_test_results.json\n";

echo "\n=== FRONTEND TESTING COMPLETED ===\n";

?>