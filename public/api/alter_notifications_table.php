<?php
require_once __DIR__ . '/../../config/db.php';

try {
    $sql = "ALTER TABLE notifications MODIFY COLUMN type VARCHAR(100) NOT NULL";
    
    $pdo->exec($sql);
    echo "Table 'notifications' updated successfully. Column 'type' is now VARCHAR(100).";
} catch (PDOException $e) {
    echo "Error updating table: " . $e->getMessage();
}
