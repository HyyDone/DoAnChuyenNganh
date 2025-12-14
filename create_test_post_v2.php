<?php
require_once __DIR__ . '/config/db.php';

// Fetch the most recent post's user (likely the current active user)
$stmtLast = $pdo->query("SELECT user_id FROM posts ORDER BY created_at DESC LIMIT 1");
$lastPost = $stmtLast->fetch();
$userId = $lastPost ? $lastPost['user_id'] : 1;
// Fallback to 1 if no posts exist

$content = "Test Layout: 5 Images (Ownership Fixed - Try #3)";
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

echo "Created Test Post ID: $postId for User ID: $userId with 5 images.\n";
?>
