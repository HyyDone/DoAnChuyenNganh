<?php
require_once __DIR__ . '/../config/db.php';

try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM listings LIKE 'district'");
    if ($colCheck->rowCount() == 0) {
        $pdo->exec("ALTER TABLE listings ADD COLUMN district VARCHAR(100) AFTER city");
        echo "Added 'district' column to listings table.<br>";
    } else {
        echo "'district' column already exists in listings.<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
