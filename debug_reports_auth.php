<?php

$baseUrl = 'http://0.0.0.0:8001';
$cookieFile = 'debug_cookies.txt';

// Initialize cURL
function initCurl($url, $cookieFile) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    return $ch;
}

// Clean up previous test
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "=== DEBUGGING AUTHENTICATION ===\n";

// Step 1: Get login page
$ch = initCurl("$baseUrl/login", $cookieFile);
$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "1. Login page status: $status\n";

// Extract CSRF token
if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $body, $matches)) {
    $csrfToken = $matches[1];
    echo "2. CSRF token found: " . substr($csrfToken, 0, 10) . "...\n";
} else {
    echo "2. CSRF token NOT found\n";
    echo "Body preview: " . substr($body, 0, 500) . "\n";
    exit;
}

curl_close($ch);

// Step 2: Try login
$ch = initCurl("$baseUrl/login", $cookieFile);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'email' => 'admin@admin.com',
    'password' => 'password'
]));

$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "3. Login attempt status: $status\n";
echo "4. Response headers:\n" . substr($headers, 0, 300) . "\n";

if (strpos($headers, 'Location:') !== false) {
    echo "5. Redirect detected - login likely successful\n";
} else {
    echo "5. No redirect - login might have failed\n";
    echo "6. Response body preview:\n" . substr($body, 0, 500) . "\n";
}

curl_close($ch);

// Step 3: Try to access reports page
$ch = initCurl("$baseUrl/reportes", $cookieFile);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "7. Access /reportes status: $status\n";

if ($status == 200) {
    echo "8. Successfully authenticated and can access reports!\n";
} else {
    echo "8. Cannot access reports - authentication issue\n";
}

curl_close($ch);

// Cleanup
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

?>