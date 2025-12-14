<?php
require_once __DIR__ . '/config/db.php';
$stmt = $pdo->query("SELECT id, content FROM posts ORDER BY id DESC LIMIT 5");
$posts = $stmt->fetchAll();

foreach ($posts as $post) {
    echo "ID: " . $post['id'] . " | Content: " . $post['content'] . "\n";
    $stmtImg = $pdo->prepare("SELECT COUNT(*) FROM post_images WHERE post_id = ?");
    $stmtImg->execute([$post['id']]);
    $count = $stmtImg->fetchColumn();
    echo "  -> Images: " . $count . "\n";
    
    $stmtFiles = $pdo->prepare("SELECT file_path FROM post_images WHERE post_id = ?");
    $stmtFiles->execute([$post['id']]);
    $files = $stmtFiles->fetchAll(PDO::FETCH_COLUMN);
    print_r($files);
    echo "-----------------\n";
}
?>
