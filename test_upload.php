<?php
$url = 'http://localhost:8000/api/posts.php';
$filePath = realpath('public/assets/default-avatar.png');

if (!file_exists($filePath)) {
    die("File not found: $filePath");
}

$cfile = new CURLFile($filePath, 'image/png', 'test.png');

$data = [
    'content' => 'AUTO TEST 5 IMAGES',
    'images[0]' => $cfile,
    'images[1]' => $cfile,
    'images[2]' => $cfile,
    'images[3]' => $cfile,
    'images[4]' => $cfile
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);

echo "Response: $response\n";
?>
