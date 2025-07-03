<?php

/**
 * TEST HARD DELETE FUNCTIONALITY
 * Verify that delete button removes records completely
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseUrl = 'http://localhost:8003';
$cookieFile = '/tmp/hard_delete_test_cookies.txt';

function makeCurlRequest($url, $method = 'GET', $data = null, $cookieFile = null, $headers = [], $followRedirects = true) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirects);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    curl_close($ch);
    
    return ['body' => $body, 'http_code' => $httpCode];
}

function extractCsrfToken($html) {
    if (preg_match('/<meta name="csrf-token" content="([^"]*)"/', $html, $matches)) {
        return $matches[1];
    }
    return null;
}

function authenticateUser() {
    global $baseUrl, $cookieFile;
    
    if (file_exists($cookieFile)) unlink($cookieFile);
    
    $loginPage = makeCurlRequest("$baseUrl/login", 'GET', null, $cookieFile);
    $csrfToken = extractCsrfToken($loginPage['body']);
    
    if (!$csrfToken) return false;
    
    $loginData = http_build_query([
        '_token' => $csrfToken,
        'email' => 'admin@taller.com',
        'password' => 'admin123'
    ]);
    
    $loginResponse = makeCurlRequest("$baseUrl/login", 'POST', $loginData, $cookieFile, [
        'Content-Type: application/x-www-form-urlencoded'
    ], false);
    
    return $loginResponse['http_code'] === 302;
}

echo "🗑️ TESTING HARD DELETE FUNCTIONALITY\n";
echo "==========================================\n\n";

// Step 1: Authenticate
echo "1. Authenticating...\n";
if (!authenticateUser()) {
    echo "❌ Authentication failed\n";
    exit(1);
}
echo "✅ Authentication successful\n\n";

// Step 2: Create a test client for deletion
echo "2. Creating test client for deletion...\n";
exec('php artisan tinker --execute="
\$cliente = App\\\\Models\\\\Cliente::create([
    \'name\' => \'Cliente HARD DELETE Test\',
    \'email\' => \'hard-delete-test@example.com\',
    \'phone\' => \'555123456\',
    \'address\' => \'Test Address 123\',
    \'document_number\' => \'HARDDELETE123\',
    \'status\' => true
]);
echo \'Created client ID: \' . \$cliente->id . PHP_EOL;
"', $output, $return);

if ($return === 0 && !empty($output)) {
    $clientId = null;
    foreach ($output as $line) {
        if (preg_match('/Created client ID: (\d+)/', $line, $matches)) {
            $clientId = $matches[1];
            break;
        }
    }
    
    if ($clientId) {
        echo "✅ Test client created with ID: $clientId\n\n";
        
        // Step 3: Verify client exists in database before deletion
        echo "3. Verifying client exists in database...\n";
        exec("php artisan tinker --execute=\"echo App\\\\Models\\\\Cliente::find($clientId) ? 'EXISTS' : 'NOT_FOUND';\"", $checkOutput, $checkReturn);
        
        if ($checkReturn === 0 && in_array('EXISTS', $checkOutput)) {
            echo "✅ Client exists in database\n\n";
            
            // Step 4: Check if client appears in clientes list
            echo "4. Checking if client appears in clientes list...\n";
            $clientesResponse = makeCurlRequest("$baseUrl/clientes", 'GET', null, $cookieFile);
            
            if ($clientesResponse['http_code'] === 200) {
                $appearsBefore = strpos($clientesResponse['body'], 'hard-delete-test@example.com') !== false;
                echo ($appearsBefore ? "✅" : "❌") . " Client appears in list before deletion\n\n";
                
                if ($appearsBefore) {
                    // Step 5: Perform hard delete
                    echo "5. Performing hard delete operation...\n";
                    $csrfToken = extractCsrfToken($clientesResponse['body']);
                    
                    if ($csrfToken) {
                        $deleteData = http_build_query([
                            '_token' => $csrfToken,
                            '_method' => 'DELETE'
                        ]);
                        
                        $deleteResponse = makeCurlRequest("$baseUrl/clientes/$clientId", 'POST', $deleteData, $cookieFile, [
                            'Content-Type: application/x-www-form-urlencoded',
                            'X-Requested-With: XMLHttpRequest'
                        ], false);
                        
                        echo "Delete response HTTP: {$deleteResponse['http_code']}\n";
                        
                        if ($deleteResponse['http_code'] === 200) {
                            $responseData = json_decode($deleteResponse['body'], true);
                            echo "✅ Delete operation successful\n";
                            echo "Response: " . ($responseData['message'] ?? 'No message') . "\n\n";
                            
                            // Step 6: Verify client is completely removed from database
                            echo "6. Verifying client is removed from database...\n";
                            exec("php artisan tinker --execute=\"echo App\\\\Models\\\\Cliente::find($clientId) ? 'EXISTS' : 'DELETED';\"", $verifyOutput, $verifyReturn);
                            
                            if ($verifyReturn === 0 && in_array('DELETED', $verifyOutput)) {
                                echo "✅ Client completely removed from database\n\n";
                                
                                // Step 7: Verify client no longer appears in clientes list
                                echo "7. Verifying client no longer appears in list...\n";
                                $clientesAfterResponse = makeCurlRequest("$baseUrl/clientes", 'GET', null, $cookieFile);
                                
                                if ($clientesAfterResponse['http_code'] === 200) {
                                    $appearsAfter = strpos($clientesAfterResponse['body'], 'hard-delete-test@example.com') !== false;
                                    echo ($appearsAfter ? "❌" : "✅") . " Client " . ($appearsAfter ? "still appears" : "no longer appears") . " in list\n\n";
                                    
                                    // Final Results
                                    echo "==========================================\n";
                                    echo "🎯 HARD DELETE TEST RESULTS\n";
                                    echo "==========================================\n";
                                    echo "✅ Client created successfully\n";
                                    echo "✅ Client appeared in list before deletion\n";
                                    echo "✅ Delete operation returned HTTP 200\n";
                                    echo "✅ Client completely removed from database\n";
                                    echo ($appearsAfter ? "❌" : "✅") . " Client " . ($appearsAfter ? "still visible" : "disappeared") . " from list\n\n";
                                    
                                    if (!$appearsAfter) {
                                        echo "🎉 HARD DELETE WORKING PERFECTLY! 🎉\n";
                                        echo "✅ Records are now being permanently deleted\n";
                                        echo "✅ Deleted clients disappear from the list immediately\n";
                                    } else {
                                        echo "❌ Issue: Client still appears in list after deletion\n";
                                    }
                                } else {
                                    echo "❌ Could not verify list after deletion\n";
                                }
                            } else {
                                echo "❌ Client still exists in database after deletion\n";
                            }
                        } else {
                            echo "❌ Delete operation failed (HTTP {$deleteResponse['http_code']})\n";
                            echo "Response: " . substr($deleteResponse['body'], 0, 200) . "...\n";
                        }
                    } else {
                        echo "❌ Could not get CSRF token\n";
                    }
                } else {
                    echo "❌ Client does not appear in list - cannot test deletion\n";
                }
            } else {
                echo "❌ Could not access clientes list\n";
            }
        } else {
            echo "❌ Client was not created successfully\n";
        }
    } else {
        echo "❌ Could not extract client ID from creation output\n";
    }
} else {
    echo "❌ Failed to create test client\n";
}

// Cleanup
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "\n✅ TEST COMPLETED\n";

?>