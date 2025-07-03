<?php

/**
 * Debug Vehicle Module - Check actual page content
 */

function makeRequest($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "http://0.0.0.0:8001$url",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'DebugTester/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'body' => $response,
        'error' => $error
    ];
}

echo "🔍 Debugging Vehicle Module Responses\n";
echo "====================================\n\n";

$endpoints = [
    '/vehiculos' => 'Vehicle Index',
    '/vehiculos/create' => 'Vehicle Create Form',
    '/vehiculos/1' => 'Vehicle Show',
    '/vehiculos/1/edit' => 'Vehicle Edit'
];

foreach ($endpoints as $endpoint => $description) {
    echo "Testing: $description ($endpoint)\n";
    echo str_repeat('-', 50) . "\n";
    
    $response = makeRequest($endpoint);
    
    echo "HTTP Code: {$response['http_code']}\n";
    
    if ($response['error']) {
        echo "Error: {$response['error']}\n";
    } else {
        // Extract title
        if (preg_match('/<title>(.*?)<\/title>/', $response['body'], $matches)) {
            echo "Title: " . trim($matches[1]) . "\n";
        }
        
        // Check if it's a login page
        if (strpos($response['body'], 'login') !== false || strpos($response['body'], 'Log in') !== false) {
            echo "⚠️  Appears to be a login page\n";
        }
        
        // Check if it's the vehicle page
        if (strpos($response['body'], 'Vehículo') !== false || strpos($response['body'], 'Vehicle') !== false) {
            echo "✅ Contains vehicle-related content\n";
        }
        
        // Check for Laravel error
        if (strpos($response['body'], 'Whoops') !== false) {
            echo "❌ Laravel error page detected\n";
        }
        
        // Check first 500 characters of body
        echo "Content preview:\n";
        echo substr(strip_tags($response['body']), 0, 200) . "...\n";
        
        // Check for specific elements
        $elements = ['form', 'table', 'nav', 'button'];
        foreach ($elements as $element) {
            $count = substr_count(strtolower($response['body']), "<$element");
            if ($count > 0) {
                echo "$element elements: $count\n";
            }
        }
    }
    
    echo "\n" . str_repeat('=', 50) . "\n\n";
}