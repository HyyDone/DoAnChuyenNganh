<?php
// public/debug_ai.php
// Move header to the very top
header('Content-Type: text/plain');

echo "========== AI DEBUG DIAGNOSTIC ==========\n";
echo "PHP Version: " . phpversion() . "\n";
echo "OS: " . PHP_OS . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'CLI') . "\n";
echo "\n";

echo "--- Configuration ---\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'ON' : 'OFF') . "\n";
echo "curl extension: " . (extension_loaded('curl') ? 'LOADED' : 'NOT LOADED (using stream fallback)') . "\n";
echo "openssl extension: " . (extension_loaded('openssl') ? 'LOADED' : 'MISSING') . "\n";
echo "\n";

echo "--- Connectivity Test (127.0.0.1:11434) ---\n";
$host = '127.0.0.1';
$port = 11434;
$connection = @fsockopen($host, $port, $errno, $errstr, 2);
if (is_resource($connection)) {
    echo "SUCCESS: fsockopen connected to $host:$port\n";
    fclose($connection);
} else {
    echo "FAIL: fsockopen failed. Error $errno: $errstr\n";
    echo "Is Ollama running? Is it blocked by firewall?\n";
}
echo "\n";

echo "--- API Test (GET /api/tags) ---\n";
$url = "http://127.0.0.1:11434/api/tags";
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
    $e = error_get_last();
    echo "FAIL: file_get_contents error: " . ($e['message'] ?? 'Unknown') . "\n";
} else {
    echo "SUCCESS: Retrieved tags.\n";
    echo "Length: " . strlen($response) . " bytes\n";
}
echo "\n";

echo "--- API Test (POST /api/chat) ---\n";
$payload = json_encode([
    "model" => "qwen2.5:3b",
    "messages" => [
        ["role" => "user", "content" => "Hello context test"]
    ],
    "stream" => false
]);
$len = strlen($payload);

$optsPost = [
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Content-Length: $len\r\n",
        "content" => $payload,
        "timeout" => 10,
        "ignore_errors" => true
    ]
];
$contextPost = stream_context_create($optsPost);
$responsePost = @file_get_contents("http://127.0.0.1:11434/api/chat", false, $contextPost);

if ($responsePost === false) {
    $e = error_get_last();
    echo "FAIL: POST error: " . ($e['message'] ?? 'Unknown') . "\n";
} else {
    // Check headers safely for PHP 8.5+
    echo "Response Headers:\n";
    if (function_exists('http_get_last_response_headers')) {
        $headers = http_get_last_response_headers();
        foreach ($headers as $h) {
            echo "  $h\n";
        }
    } elseif (isset($http_response_header)) {
        foreach ($http_response_header as $h) {
            echo "  $h\n";
        }
    } else {
         echo "  (Unable to retrieve headers)\n";
    }

    echo "Response Body:\n";
    echo substr($responsePost, 0, 500) . "...\n";
}

echo "\n========== END DIAGNOSTIC ==========\n";
