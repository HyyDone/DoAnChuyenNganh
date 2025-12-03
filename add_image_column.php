<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo->exec("ALTER TABLE posts ADD COLUMN image VARCHAR(255) DEFAULT NULL");
    echo "Added 'image' column to 'posts' table.\n";
} catch (PDOException $e) {
    echo "Error (maybe column exists): " . $e->getMessage() . "\n";
}
