<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Change 'type' column to VARCHAR(50) to support new notification types
    // Using MODIFY COLUMN is standard MySQL syntax
    $sql = "ALTER TABLE notifications MODIFY COLUMN type VARCHAR(50) NOT NULL";

    $pdo->exec($sql);
    echo "Table 'notifications' updated successfully: 'type' column is now VARCHAR(50).";
} catch (PDOException $e) {
    echo "Error updating table: " . $e->getMessage();
}
?>
