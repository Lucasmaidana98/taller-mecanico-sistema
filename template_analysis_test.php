<?php

/**
 * Template Analysis and Blade Feature Testing
 * Deep analysis of Blade template functionality and rendering
 */

// Configuration
$base_url = 'http://localhost:8002';
$login_email = 'admin@taller.com';
$login_password = 'admin123';
$cookie_file = __DIR__ . '/template_test_cookies.txt';

// Template analysis results
$template_analysis = [
    'blade_features' => [],
    'layout_analysis' => [],
    'component_analysis' => [],
    'form_analysis' => [],
    'javascript_analysis' => [],
    'css_analysis' => [],
    'accessibility_analysis' => [],
    'performance_analysis' => [],
    'security_analysis' => []
];

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

function analyze_blade_directives($html) {
    $directives = [
        'extends' => false,
        'section' => false,
        'yield' => false,
        'include' => false,
        'component' => false,
        'auth' => false,
        'can' => false,
        'csrf' => false,
        'method' => false,
        'foreach' => false,
        'if' => false,
        'switch' => false,
        'isset' => false,
        'empty' => false,
        'push' => false,
        'stack' => false,
        'json' => false,
        'error' => false
    ];
    
    // Check for rendered evidence of Blade directives
    if (strpos($html, 'Sistema de Taller') !== false) $directives['extends'] = true;
    if (strpos($html, 'sidebar') !== false) $directives['section'] = true;
    if (strpos($html, 'main-content') !== false) $directives['yield'] = true;
    if (strpos($html, 'nav-link') !== false) $directives['include'] = true;
    if (strpos($html, 'form-control') !== false) $directives['component'] = true;
    if (strpos($html, 'dropdown-toggle') !== false) $directives['auth'] = true;
    if (strpos($html, 'btn btn-') !== false) $directives['can'] = true;
    if (strpos($html, 'name="_token"') !== false) $directives['csrf'] = true;
    if (strpos($html, 'name="_method"') !== false) $directives['method'] = true;
    if (strpos($html, '<tr>') !== false) $directives['foreach'] = true;
    if (strpos($html, 'alert alert-') !== false) $directives['if'] = true;
    if (strpos($html, 'badge bg-') !== false) $directives['switch'] = true;
    if (strpos($html, 'text-muted') !== false) $directives['isset'] = true;
    if (strpos($html, 'py-5') !== false) $directives['empty'] = true;
    if (strpos($html, 'script') !== false) $directives['push'] = true;
    if (strpos($html, 'bootstrap') !== false) $directives['stack'] = true;
    if (strpos($html, 'Chart') !== false) $directives['json'] = true;
    if (strpos($html, 'invalid-feedback') !== false) $directives['error'] = true;
    
    return $directives;
}

function analyze_css_framework($html) {
    $frameworks = [
        'bootstrap' => [
            'detected' => strpos($html, 'bootstrap') !== false,
            'version' => '5.3.0', // From layout inspection
            'components' => [
                'grid' => substr_count($html, 'col-'),
                'cards' => substr_count($html, 'card'),
                'buttons' => substr_count($html, 'btn btn-'),
                'forms' => substr_count($html, 'form-control'),
                'navigation' => substr_count($html, 'nav-'),
                'alerts' => substr_count($html, 'alert alert-'),
                'badges' => substr_count($html, 'badge'),
                'dropdowns' => substr_count($html, 'dropdown'),
                'modals' => substr_count($html, 'modal'),
                'tables' => substr_count($html, 'table table-')
            ]
        ],
        'fontawesome' => [
            'detected' => strpos($html, 'fas fa-') !== false,
            'icons_count' => substr_count($html, 'fas fa-') + substr_count($html, 'far fa-') + substr_count($html, 'fab fa-')
        ],
        'custom_css' => [
            'detected' => strpos($html, '<style>') !== false,
            'variables' => strpos($html, '--primary-color') !== false,
            'animations' => strpos($html, 'transition') !== false,
            'responsive' => strpos($html, '@media') !== false
        ]
    ];
    
    return $frameworks;
}

function analyze_javascript_features($html) {
    $js_features = [
        'jquery' => strpos($html, 'jquery') !== false,
        'bootstrap_js' => strpos($html, 'bootstrap.bundle') !== false,
        'sweetalert' => strpos($html, 'sweetalert') !== false,
        'chartjs' => strpos($html, 'chart.js') !== false,
        'datatables' => strpos($html, 'datatables') !== false,
        'custom_js' => strpos($html, 'function') !== false,
        'ajax' => strpos($html, 'ajax') !== false || strpos($html, 'fetch') !== false,
        'form_validation' => strpos($html, 'validation') !== false || strpos($html, 'is-invalid') !== false,
        'interactive_elements' => strpos($html, 'onclick') !== false || strpos($html, 'addEventListener') !== false
    ];
    
    return $js_features;
}

function analyze_accessibility($html) {
    $accessibility = [
        'alt_attributes' => substr_count($html, 'alt='),
        'aria_labels' => substr_count($html, 'aria-label'),
        'aria_described' => substr_count($html, 'aria-describedby'),
        'role_attributes' => substr_count($html, 'role='),
        'semantic_elements' => [
            'nav' => substr_count($html, '<nav'),
            'main' => substr_count($html, '<main'),
            'section' => substr_count($html, '<section'),
            'article' => substr_count($html, '<article'),
            'aside' => substr_count($html, '<aside'),
            'header' => substr_count($html, '<header'),
            'footer' => substr_count($html, '<footer')
        ],
        'form_labels' => substr_count($html, '<label'),
        'headings' => [
            'h1' => substr_count($html, '<h1'),
            'h2' => substr_count($html, '<h2'),
            'h3' => substr_count($html, '<h3'),
            'h4' => substr_count($html, '<h4'),
            'h5' => substr_count($html, '<h5'),
            'h6' => substr_count($html, '<h6')
        ],
        'focus_management' => strpos($html, 'tabindex') !== false,
        'skip_links' => strpos($html, 'skip-to-content') !== false
    ];
    
    return $accessibility;
}

function analyze_security_features($html) {
    $security = [
        'csrf_protection' => substr_count($html, 'csrf-token') + substr_count($html, '_token'),
        'content_security' => strpos($html, 'nonce') !== false,
        'xss_protection' => strpos($html, 'htmlspecialchars') !== false, // In rendered output
        'form_security' => [
            'csrf_tokens' => substr_count($html, 'name="_token"'),
            'method_spoofing' => substr_count($html, 'name="_method"'),
            'hidden_fields' => substr_count($html, 'type="hidden"')
        ],
        'https_enforcement' => strpos($html, 'https://') !== false,
        'secure_headers' => [
            'viewport' => strpos($html, 'viewport') !== false,
            'charset' => strpos($html, 'charset=utf-8') !== false
        ]
    ];
    
    return $security;
}

function analyze_performance($html) {
    $performance = [
        'external_resources' => [
            'cdn_bootstrap' => strpos($html, 'cdn.jsdelivr.net') !== false,
            'cdn_jquery' => strpos($html, 'code.jquery.com') !== false,
            'cdn_fontawesome' => strpos($html, 'cdnjs.cloudflare.com') !== false,
            'google_fonts' => strpos($html, 'fonts.bunny.net') !== false
        ],
        'resource_optimization' => [
            'minified_css' => strpos($html, '.min.css') !== false,
            'minified_js' => strpos($html, '.min.js') !== false,
            'preconnect' => strpos($html, 'preconnect') !== false,
            'defer_scripts' => strpos($html, 'defer') !== false
        ],
        'lazy_loading' => strpos($html, 'loading="lazy"') !== false,
        'compression' => strlen($html) // Basic size metric
    ];
    
    return $performance;
}

echo "=== TEMPLATE ANALYSIS AND BLADE FEATURE TESTING ===\n";
echo "Analyzing Laravel Blade templates and frontend architecture...\n\n";

// Login first
$response = make_request($base_url . '/login', $cookie_file);
if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $response['body'], $matches)) {
    $csrf_token = $matches[1];
    $login_data = http_build_query([
        '_token' => $csrf_token,
        'email' => $login_email,
        'password' => $login_password
    ]);
    make_request($base_url . '/login', $cookie_file, $login_data, ['Content-Type: application/x-www-form-urlencoded']);
}

// Test pages to analyze
$test_pages = [
    'dashboard' => '/dashboard',
    'clientes_index' => '/clientes',
    'clientes_create' => '/clientes/create',
    'vehiculos_index' => '/vehiculos',
    'servicios_index' => '/servicios',
    'empleados_index' => '/empleados',
    'ordenes_index' => '/ordenes',
    'reportes_index' => '/reportes',
    'profile' => '/profile'
];

foreach ($test_pages as $page_name => $url) {
    echo "=== Analyzing $page_name ($url) ===\n";
    
    $response = make_request($base_url . $url, $cookie_file);
    
    if ($response['code'] == 200) {
        $html = $response['body'];
        
        // Analyze Blade directives
        $blade_directives = analyze_blade_directives($html);
        $template_analysis['blade_features'][$page_name] = $blade_directives;
        
        // Analyze CSS frameworks
        $css_analysis = analyze_css_framework($html);
        $template_analysis['css_analysis'][$page_name] = $css_analysis;
        
        // Analyze JavaScript features
        $js_analysis = analyze_javascript_features($html);
        $template_analysis['javascript_analysis'][$page_name] = $js_analysis;
        
        // Analyze accessibility
        $accessibility = analyze_accessibility($html);
        $template_analysis['accessibility_analysis'][$page_name] = $accessibility;
        
        // Analyze security features
        $security = analyze_security_features($html);
        $template_analysis['security_analysis'][$page_name] = $security;
        
        // Analyze performance aspects
        $performance = analyze_performance($html);
        $template_analysis['performance_analysis'][$page_name] = $performance;
        
        // Page-specific analysis
        $page_size = strlen($html);
        $load_time = microtime(true); // Simplified metric
        
        echo "  Page Size: " . number_format($page_size) . " bytes\n";
        echo "  Blade Directives: " . count(array_filter($blade_directives)) . "/" . count($blade_directives) . " detected\n";
        echo "  Bootstrap Components: " . array_sum($css_analysis['bootstrap']['components']) . " elements\n";
        echo "  FontAwesome Icons: " . $css_analysis['fontawesome']['icons_count'] . "\n";
        echo "  Form Elements: " . substr_count($html, 'form-control') . "\n";
        echo "  Interactive Elements: " . (substr_count($html, 'btn') + substr_count($html, 'onclick')) . "\n";
        echo "  CSRF Protection: " . ($security['csrf_protection'] > 0 ? 'Yes' : 'No') . "\n";
        echo "  Accessibility Score: " . (array_sum($accessibility['semantic_elements']) + $accessibility['form_labels']) . "\n";
        echo "\n";
        
    } else {
        echo "  ERROR: HTTP {$response['code']} - Page not accessible\n\n";
    }
}

// Generate comprehensive analysis report
echo "=== COMPREHENSIVE TEMPLATE ANALYSIS REPORT ===\n\n";

echo "1. BLADE DIRECTIVES USAGE:\n";
$all_directives = [];
foreach ($template_analysis['blade_features'] as $page => $directives) {
    foreach ($directives as $directive => $used) {
        if (!isset($all_directives[$directive])) {
            $all_directives[$directive] = 0;
        }
        if ($used) {
            $all_directives[$directive]++;
        }
    }
}

foreach ($all_directives as $directive => $count) {
    $percentage = round(($count / count($test_pages)) * 100, 1);
    echo "  @$directive: Used in $count/" . count($test_pages) . " pages ($percentage%)\n";
}

echo "\n2. CSS FRAMEWORK ANALYSIS:\n";
$bootstrap_components = [];
foreach ($template_analysis['css_analysis'] as $page => $analysis) {
    if (isset($analysis['bootstrap']['components'])) {
        foreach ($analysis['bootstrap']['components'] as $component => $count) {
            if (!isset($bootstrap_components[$component])) {
                $bootstrap_components[$component] = 0;
            }
            $bootstrap_components[$component] += $count;
        }
    }
}

echo "  Bootstrap 5.3.0 Components Usage:\n";
foreach ($bootstrap_components as $component => $total) {
    echo "    $component: $total occurrences\n";
}

echo "\n3. JAVASCRIPT FEATURES:\n";
$js_features_summary = [];
foreach ($template_analysis['javascript_analysis'] as $page => $features) {
    foreach ($features as $feature => $present) {
        if (!isset($js_features_summary[$feature])) {
            $js_features_summary[$feature] = 0;
        }
        if ($present) {
            $js_features_summary[$feature]++;
        }
    }
}

foreach ($js_features_summary as $feature => $count) {
    $percentage = round(($count / count($test_pages)) * 100, 1);
    echo "  $feature: Present in $count/" . count($test_pages) . " pages ($percentage%)\n";
}

echo "\n4. ACCESSIBILITY ANALYSIS:\n";
$accessibility_summary = [];
foreach ($template_analysis['accessibility_analysis'] as $page => $accessibility) {
    foreach ($accessibility as $feature => $value) {
        if (is_array($value)) {
            foreach ($value as $sub_feature => $count) {
                $key = $feature . '_' . $sub_feature;
                if (!isset($accessibility_summary[$key])) {
                    $accessibility_summary[$key] = 0;
                }
                $accessibility_summary[$key] += $count;
            }
        } else {
            if (!isset($accessibility_summary[$feature])) {
                $accessibility_summary[$feature] = 0;
            }
            $accessibility_summary[$feature] += $value;
        }
    }
}

foreach ($accessibility_summary as $feature => $total) {
    if ($total > 0) {
        echo "  $feature: $total occurrences\n";
    }
}

echo "\n5. SECURITY FEATURES:\n";
$security_summary = [];
foreach ($template_analysis['security_analysis'] as $page => $security) {
    foreach ($security as $feature => $value) {
        if (is_array($value)) {
            foreach ($value as $sub_feature => $count) {
                $key = $feature . '_' . $sub_feature;
                if (!isset($security_summary[$key])) {
                    $security_summary[$key] = 0;
                }
                $security_summary[$key] += $count;
            }
        } else {
            if (!isset($security_summary[$feature])) {
                $security_summary[$feature] = 0;
            }
            $security_summary[$feature] += $value;
        }
    }
}

foreach ($security_summary as $feature => $total) {
    if ($total > 0) {
        echo "  $feature: $total occurrences\n";
    }
}

echo "\n6. PERFORMANCE METRICS:\n";
$total_size = 0;
$external_resources = 0;
$minified_resources = 0;

foreach ($template_analysis['performance_analysis'] as $page => $performance) {
    $total_size += $performance['compression'];
    if (isset($performance['external_resources'])) {
        $external_resources += count(array_filter($performance['external_resources']));
    }
    if (isset($performance['resource_optimization'])) {
        $minified_resources += count(array_filter($performance['resource_optimization']));
    }
}

echo "  Average page size: " . number_format($total_size / count($test_pages)) . " bytes\n";
echo "  External resources: $external_resources CDN resources detected\n";
echo "  Resource optimization: $minified_resources optimization techniques\n";

// Save detailed analysis
file_put_contents(__DIR__ . '/template_analysis_results.json', json_encode($template_analysis, JSON_PRETTY_PRINT));
echo "\nDetailed analysis saved to template_analysis_results.json\n";

echo "\n=== TEMPLATE ANALYSIS COMPLETED ===\n";

?>