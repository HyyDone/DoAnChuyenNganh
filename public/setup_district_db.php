<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Add district column to listings table if not exists
    $colCheck = $pdo->query("SHOW COLUMNS FROM listings LIKE 'district'");
    if ($colCheck->rowCount() == 0) {
        // Add after city
        $pdo->exec("ALTER TABLE listings ADD COLUMN district VARCHAR(100) AFTER city");
        echo "Added 'district' column to listings table.<br>";
    } else {
        echo "'district' column already exists in listings.<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
