<?php

// Manual test script to test specific functionality
echo "🔧 Manual Testing of Taller Sistema Application\n";
echo "================================================\n\n";

$baseUrl = 'http://127.0.0.1:8001';
$cookieFile = '/tmp/manual_test_cookies.txt';

// Clean up cookies
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

function makeRequest($url, $postData = null, $headers = []) {
    global $cookieFile;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Manual Test Agent 1.0');
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    return [
        'body' => $body,
        'headers' => $headers,
        'http_code' => $httpCode
    ];
}

// Get CSRF token
function extractCsrfToken($html) {
    preg_match('/name="_token" value="([^"]+)"/', $html, $matches);
    return $matches[1] ?? null;
}

echo "1. Testing Authentication Flow\n";
echo "==============================\n";

// Step 1: Get login page
$response = makeRequest($baseUrl . '/login');
echo "Login page status: " . $response['http_code'] . "\n";

if ($response['http_code'] == 200) {
    $csrfToken = extractCsrfToken($response['body']);
    if ($csrfToken) {
        echo "✅ CSRF token extracted: " . substr($csrfToken, 0, 10) . "...\n";
        
        // Step 2: Perform login
        $loginData = http_build_query([
            '_token' => $csrfToken,
            'email' => 'admin@taller.com',
            'password' => 'password'
        ]);
        
        $response = makeRequest($baseUrl . '/login', $loginData);
        echo "Login attempt status: " . $response['http_code'] . "\n";
        
        if (strpos($response['headers'], 'Location: ' . $baseUrl . '/dashboard') !== false) {
            echo "✅ Login successful - redirected to dashboard\n";
        } elseif (strpos($response['headers'], 'Location:') !== false) {
            echo "✅ Login successful - redirected elsewhere\n";
        } else {
            echo "❌ Login may have failed\n";
            echo "Response preview: " . substr($response['body'], 0, 300) . "\n";
        }
    } else {
        echo "❌ Could not extract CSRF token\n";
    }
} else {
    echo "❌ Login page not accessible\n";
}

echo "\n2. Testing Protected Routes\n";
echo "===========================\n";

// Test ordenes index
$response = makeRequest($baseUrl . '/ordenes');
echo "Ordenes index status: " . $response['http_code'] . "\n";

if ($response['http_code'] == 200) {
    echo "✅ Ordenes index accessible\n";
    
    // Check for key elements in the response
    $body = $response['body'];
    
    // Check for Spanish content that indicates the correct page
    if (strpos($body, 'Gestión de Órdenes') !== false) {
        echo "✅ Correct Spanish content found\n";
    } elseif (strpos($body, 'Órdenes') !== false) {
        echo "✅ Spanish orders content found\n";
    } else {
        echo "❌ Expected Spanish content not found\n";
    }
    
    // Check for table structure
    if (strpos($body, '<table') !== false) {
        echo "✅ Table structure found\n";
    } else {
        echo "❌ Table structure not found\n";
    }
    
    // Check for form elements
    if (strpos($body, 'name="search"') !== false) {
        echo "✅ Search form found\n";
    } else {
        echo "❌ Search form not found\n";
    }
    
} elseif ($response['http_code'] == 302) {
    echo "❌ Redirected (authentication failed?)\n";
} else {
    echo "❌ Ordenes index not accessible\n";
}

// Test ordenes create
echo "\nTesting ordenes create page:\n";
$response = makeRequest($baseUrl . '/ordenes/create');
echo "Ordenes create status: " . $response['http_code'] . "\n";

if ($response['http_code'] == 200) {
    echo "✅ Ordenes create page accessible\n";
    
    $body = $response['body'];
    if (strpos($body, 'cliente_id') !== false) {
        echo "✅ Cliente dropdown found\n";
    } else {
        echo "❌ Cliente dropdown not found\n";
    }
    
} else {
    echo "❌ Ordenes create page not accessible\n";
}

echo "\n3. Testing Profile Section\n";
echo "==========================\n";

$response = makeRequest($baseUrl . '/profile');
echo "Profile page status: " . $response['http_code'] . "\n";

if ($response['http_code'] == 200) {
    echo "✅ Profile page accessible\n";
    
    $body = $response['body'];
    if (strpos($body, 'Profile Information') !== false) {
        echo "✅ Profile Information section found\n";
    } else {
        echo "❌ Profile Information section not found\n";
    }
    
    if (strpos($body, 'Update Password') !== false) {
        echo "✅ Update Password section found\n";
    } else {
        echo "❌ Update Password section not found\n";
    }
    
} else {
    echo "❌ Profile page not accessible\n";
}

echo "\n4. Testing Data Presence\n";
echo "========================\n";

// Test if we can see actual data in the ordenes list
$response = makeRequest($baseUrl . '/ordenes');
if ($response['http_code'] == 200) {
    $body = $response['body'];
    
    // Look for actual data from the seeder
    if (strpos($body, 'Carlos Rodríguez') !== false) {
        echo "✅ Sample client data found in ordenes\n";
    } else {
        echo "❌ Sample client data not found\n";
    }
    
    if (strpos($body, 'Toyota') !== false || strpos($body, 'Honda') !== false) {
        echo "✅ Vehicle data found\n";
    } else {
        echo "❌ Vehicle data not found\n";
    }
    
    if (strpos($body, 'Completada') !== false || strpos($body, 'Pendiente') !== false) {
        echo "✅ Status badges found\n";
    } else {
        echo "❌ Status badges not found\n";
    }
}

echo "\n5. Final Status\n";
echo "===============\n";

// Check if we're properly authenticated by testing dashboard
$response = makeRequest($baseUrl . '/dashboard');
echo "Dashboard access status: " . $response['http_code'] . "\n";

if ($response['http_code'] == 200) {
    echo "✅ Authenticated and can access dashboard\n";
} else {
    echo "❌ Cannot access dashboard - authentication issue\n";
}

// Cleanup
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "\nTesting completed.\n";
?>