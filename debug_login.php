<?php

// Debug login process
$baseUrl = 'http://localhost:8002';
$cookieFile = 'debug_login_cookies.txt';

if (file_exists($cookieFile)) {
    unlink($cookieFile);
}
touch($cookieFile);

function makeRequest($url, $data = null, $method = 'GET', $cookieFile) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_VERBOSE => true,
        CURLOPT_STDERR => fopen('php://output', 'w'),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

echo "=== DEBUG: Getting login page ===\n";
$loginPage = makeRequest($baseUrl . '/login', null, 'GET', $cookieFile);
echo "Login page HTTP code: " . $loginPage['http_code'] . "\n";

if ($loginPage['http_code'] === 200) {
    echo "Login page content preview:\n";
    echo substr($loginPage['response'], 0, 500) . "\n...\n";
    
    // Look for CSRF token
    if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $loginPage['response'], $matches)) {
        echo "Found CSRF token: " . $matches[1] . "\n";
        $csrfToken = $matches[1];
        
        echo "\n=== DEBUG: Attempting login ===\n";
        $loginData = http_build_query([
            '_token' => $csrfToken,
            'email' => 'admin@taller.com',
            'password' => 'admin123'
        ]);
        
        $loginResult = makeRequest($baseUrl . '/login', $loginData, 'POST', $cookieFile);
        echo "Login attempt HTTP code: " . $loginResult['http_code'] . "\n";
        
        if ($loginResult['http_code'] === 302) {
            echo "Login successful - checking dashboard access\n";
            $dashboardResult = makeRequest($baseUrl . '/dashboard', null, 'GET', $cookieFile);
            echo "Dashboard HTTP code: " . $dashboardResult['http_code'] . "\n";
            
            if ($dashboardResult['http_code'] === 200) {
                echo "✓ Successfully authenticated and can access dashboard\n";
            } else {
                echo "✗ Authentication may have failed - cannot access dashboard\n";
            }
        } else {
            echo "Login response preview:\n";
            echo substr($loginResult['response'], 0, 500) . "\n...\n";
        }
    } else {
        echo "CSRF token not found in login page\n";
    }
} else {
    echo "Cannot access login page\n";
}

?>