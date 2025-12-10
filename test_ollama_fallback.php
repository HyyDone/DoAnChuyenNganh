<?php
// Script to test Ollama connection using file_get_contents (stream context)

function testConnection($host, $port) {
    $url = "http://$host:$port/api/tags"; 
    echo "Testing connection to $url via file_get_contents...\n";
    
    $options = [
        "http" => [
            "method" => "GET",
            "timeout" => 5
        ]
    ];
    $context = stream_context_create($options);
    
    // Suppress warnings to handle errors manually
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        echo "FAIL: Error: " . ($error['message'] ?? 'Unknown error') . "\n";
        return false;
    }
    
    echo "SUCCESS: Connected!\n";
    echo "Response excerpt: " . substr($response, 0, 100) . "...\n";
    return true;
}

echo "--- DIAGNOSTIC START (Stream Context) ---\n";

// Test localhost
testConnection('localhost', 11434);

// Test 127.0.0.1
testConnection('127.0.0.1', 11434);

echo "\n--- CHECKING MODEL (api/chat) ---\n";
$payload = json_encode([
    "model" => "qwen2.5:3b",
    "messages" => [
        ["role" => "user", "content" => "Hello"]
    ],
    "stream" => false
]);

$payloadLen = strlen($payload);
$options = [
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/json\r\n" .
                    "Content-Length: $payloadLen\r\n",
        "content" => $payload,
        "timeout" => 10,
        "ignore_errors" => true
    ]
];
$context = stream_context_create($options);
$response = @file_get_contents("http://127.0.0.1:11434/api/chat", false, $context);

if ($response === false) {
    $error = error_get_last();
    echo "Model Test FAIL: " . ($error['message'] ?? 'Unknown error') . "\n";
} else {
    // Check status code if possible or just output
    if (isset($http_response_header)) {
        echo "Status: " . $http_response_header[0] . "\n";
    }
    echo "Model Test Response: " . substr($response, 0, 200) . "\n";
}

echo "--- DIAGNOSTIC END ---\n";
