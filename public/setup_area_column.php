<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("ALTER TABLE listings ADD COLUMN area DECIMAL(10,2) DEFAULT NULL AFTER price");
    echo "Successfully added 'area' column to listings table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column 'area' already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
