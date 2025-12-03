<?php
require_once __DIR__ . '/config/db.php';
session_start();

// Mock session if needed, or test as guest
// $_SESSION['user_id'] = 1;

$userId = $_SESSION['user_id'] ?? 0;

try {
    $sql = "
        SELECT p.*, 
               u.username, u.full_name, u.avatar,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT 50
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Successfully fetched " . count($rows) . " posts.\n";
    if (count($rows) > 0) {
        print_r($rows[0]);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
