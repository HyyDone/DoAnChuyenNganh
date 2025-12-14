<?php
require_once __DIR__ . '/config/db.php';

// List Databases
try {
    $stmt = $pdo->query("SHOW DATABASES");
    $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Databases found: \n";
    print_r($dbs);
} catch (Exception $e) {
    echo "Error listing DBs: " . $e->getMessage();
}

// Count posts
$count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
echo "Total Posts: $count\n";

// List ALL IDs
$ids = $pdo->query("SELECT id FROM posts ORDER BY id DESC")->fetchAll(PDO::FETCH_COLUMN);
echo "All Post IDs: " . implode(', ', $ids) . "\n";
?>
