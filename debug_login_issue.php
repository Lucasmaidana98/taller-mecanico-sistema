<?php

/**
 * DEBUG LOGIN ISSUE
 * Detailed debugging of login functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseUrl = 'http://localhost:8003';
$cookieFile = '/tmp/debug_login_cookies.txt';

function makeCurlRequest($url, $method = 'GET', $data = null, $cookieFile = null, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
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
    
    return ['response' => $response, 'http_code' => $httpCode, 'error' => $error];
}

if (file_exists($cookieFile)) unlink($cookieFile);

echo "=== DEBUGGING LOGIN PROCESS ===\n\n";

// Step 1: Check if server is responding
echo "1. Testing server connectivity...\n";
$homeResponse = makeCurlRequest($baseUrl);
echo "Home page HTTP Code: {$homeResponse['http_code']}\n";
if ($homeResponse['error']) {
    echo "cURL Error: {$homeResponse['error']}\n";
}
echo "Response length: " . strlen($homeResponse['response']) . " characters\n\n";

// Step 2: Get login page
echo "2. Accessing login page...\n";
$loginPageResponse = makeCurlRequest("$baseUrl/login", 'GET', null, $cookieFile);
echo "Login page HTTP Code: {$loginPageResponse['http_code']}\n";
if ($loginPageResponse['error']) {
    echo "cURL Error: {$loginPageResponse['error']}\n";
}

// Extract CSRF token
$csrfToken = null;
if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $loginPageResponse['response'], $matches)) {
    $csrfToken = $matches[1];
    echo "CSRF Token found: " . substr($csrfToken, 0, 20) . "...\n";
} elseif (preg_match('/<meta name="csrf-token" content="([^"]*)"/', $loginPageResponse['response'], $matches)) {
    $csrfToken = $matches[1];
    echo "CSRF Token found in meta: " . substr($csrfToken, 0, 20) . "...\n";
} else {
    echo "❌ No CSRF token found in login page\n";
    echo "Response excerpt:\n" . substr($loginPageResponse['response'], 0, 1000) . "\n\n";
}

// Step 3: Check if login form exists
if (strpos($loginPageResponse['response'], 'form') !== false) {
    echo "✅ Form found in login page\n";
} else {
    echo "❌ No form found in login page\n";
}

if (strpos($loginPageResponse['response'], 'name="email"') !== false) {
    echo "✅ Email field found\n";
} else {
    echo "❌ Email field not found\n";
}

if (strpos($loginPageResponse['response'], 'name="password"') !== false) {
    echo "✅ Password field found\n";
} else {
    echo "❌ Password field not found\n";
}

echo "\n";

// Step 4: Attempt login if we have a CSRF token
if ($csrfToken) {
    echo "3. Attempting login with admin credentials...\n";
    
    $loginData = http_build_query([
        '_token' => $csrfToken,
        'email' => 'admin@taller.com',
        'password' => 'admin123'
    ]);
    
    echo "Login data: $loginData\n";
    
    $loginResponse = makeCurlRequest("$baseUrl/login", 'POST', $loginData, $cookieFile, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    echo "Login attempt HTTP Code: {$loginResponse['http_code']}\n";
    if ($loginResponse['error']) {
        echo "cURL Error: {$loginResponse['error']}\n";
    }
    
    // Check response for success/failure indicators
    if ($loginResponse['http_code'] === 302) {
        echo "✅ Got redirect (likely successful login)\n";
        // Check redirect location
        if (preg_match('/Location: (.+)/i', $loginResponse['response'], $matches)) {
            echo "Redirect location: " . trim($matches[1]) . "\n";
        }
    } elseif ($loginResponse['http_code'] === 200) {
        echo "❌ Got 200 (likely failed login - returned to login page)\n";
        // Check for error messages
        if (strpos($loginResponse['response'], 'error') !== false) {
            echo "Found error in response\n";
        }
        if (strpos($loginResponse['response'], 'invalid') !== false) {
            echo "Found 'invalid' in response\n";
        }
    } else {
        echo "❌ Unexpected HTTP code: {$loginResponse['http_code']}\n";
    }
    
    echo "\n";
    
    // Step 5: Test accessing dashboard after login
    echo "4. Testing dashboard access after login...\n";
    $dashboardResponse = makeCurlRequest("$baseUrl/dashboard", 'GET', null, $cookieFile);
    echo "Dashboard HTTP Code: {$dashboardResponse['http_code']}\n";
    
    if ($dashboardResponse['http_code'] === 200) {
        echo "✅ Dashboard accessible - login successful\n";
    } elseif ($dashboardResponse['http_code'] === 302) {
        echo "❌ Dashboard redirected - login likely failed\n";
        if (preg_match('/Location: (.+)/i', $dashboardResponse['response'], $matches)) {
            echo "Dashboard redirect location: " . trim($matches[1]) . "\n";
        }
    } else {
        echo "❌ Dashboard access failed - HTTP {$dashboardResponse['http_code']}\n";
    }
    
} else {
    echo "❌ Cannot attempt login without CSRF token\n";
}

// Step 6: Check database users
echo "\n5. Checking database users...\n";
echo "Running artisan command to list users:\n";
exec('php artisan tinker --execute="echo App\\Models\\User::all()->toJson();"', $output, $returnCode);
if ($returnCode === 0) {
    echo "Users in database:\n";
    foreach ($output as $line) {
        echo "$line\n";
    }
} else {
    echo "❌ Could not query database users\n";
}

// Clean up
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "\n=== DEBUG COMPLETE ===\n";

?>