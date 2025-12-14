<?php
require_once __DIR__ . '/config/db.php'; // Correct path
if (!isset($pdo)) {
    // Fallback if config path varies
    require_once __DIR__ . '/config/db.php';
}

$userId = 1; // Assuming admin/first user exists. 
// If unsure, we can fetch one.
$stmtU = $pdo->query("SELECT id FROM users LIMIT 1");
$user = $stmtU->fetch();
if ($user) $userId = $user['id'];

$content = "Test Layout: 5 Images (Custom Grid)";
// We can use the same image repeated 5 times for testing
$image = "assets/default-avatar.png"; 

// Insert Post
$stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
$stmt->execute([$userId, $content, $image]);
$postId = $pdo->lastInsertId();

// Insert 5 images into post_images
$images = [
    "assets/default-avatar.png",
    "assets/default-avatar.png",
    "assets/default-avatar.png", 
    "assets/default-avatar.png",
    "assets/default-avatar.png"
];

$stmtImg = $pdo->prepare("INSERT INTO post_images (post_id, file_path) VALUES (?, ?)");
foreach ($images as $img) {
    $stmtImg->execute([$postId, $img]);
}

echo "Created Test Post ID: $postId with 5 images.\n";
?>
