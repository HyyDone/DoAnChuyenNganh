<?php
require_once __DIR__ . '/config/db.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM comments LIKE 'parent_id'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $pdo->exec("ALTER TABLE comments ADD COLUMN parent_id INT NULL DEFAULT NULL");
        echo "Added parent_id column to comments table.\n";
    } else {
        echo "parent_id column already exists.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
