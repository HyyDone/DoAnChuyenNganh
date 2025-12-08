<?php
require_once __DIR__ . '/config/db.php';
try {
    $sql = "ALTER TABLE messages ADD COLUMN image VARCHAR(255) NULL AFTER content";
    $pdo->exec($sql);
    echo "Column 'image' added successfully.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column 'image' already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
