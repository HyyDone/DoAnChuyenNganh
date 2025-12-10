<?php
// Script to test Ollama connection independent of the app logic

function testConnection($host, $port) {
    $url = "http://$host:$port/api/tags"; // Lightweight endpoint
    echo "Testing connection to $url...\n";
    
    $opts = [
        "http" => [
            "method" => "GET",
            "timeout" => 5,
            "ignore_errors" => true
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        echo "FAIL: Connection failed. Error: " . ($error['message'] ?? 'Unknown') . "\n";
        return false;
    }
    
    // Check status code
    $status = 0;
    if (function_exists('http_get_last_response_headers')) {
        $headers = http_get_last_response_headers();
        if ($headers) {
             preg_match('{HTTP\/\S*\s(\d{3})}', $headers[0], $match);
             $status = $match[1] ?? 0;
        }
    } else if (isset($http_response_header)) {
        preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $match);
        $status = $match[1] ?? 0;
    }

    if ($status >= 200 && $status < 300) {
        echo "SUCCESS: Connected! HTTP Code: $status\n";
        echo "Response excerpt: " . substr($response, 0, 100) . "...\n";
        return true;
    } else {
        echo "FAIL: HTTP Code $status. Response: $response\n";
        return false;
    }
}

echo "--- DIAGNOSTIC START ---\n";

// Test localhost
testConnection('localhost', 11434);

// Test 127.0.0.1 (IPv4 force)
testConnection('127.0.0.1', 11434);

echo "\n--- CHECKING MODEL ---\n";
// Try to generate a tiny response
$payload = json_encode([
    "model" => "qwen2.5:3b",
    "prompt" => "Hi",
    "stream" => false
]);

$opts = [
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/json\r\n" .
                    "Content-Length: " . strlen($payload) . "\r\n",
        "content" => $payload,
        "timeout" => 10,
        "ignore_errors" => true
    ]
];

$context = stream_context_create($opts);
$response = @file_get_contents("http://127.0.0.1:11434/api/generate", false, $context);

if ($response === false) {
    $err = error_get_last();
    echo "Model Test FAIL: " . ($err['message'] ?? 'Unknown') . "\n";
} else {
    echo "Model Test Response: " . substr($response, 0, 200) . "\n";
}

echo "--- DIAGNOSTIC END ---\n";
