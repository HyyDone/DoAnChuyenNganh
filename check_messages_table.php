<?php
require_once __DIR__ . '/config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE messages");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo in_array('image', $columns) ? "Column 'image' exists." : "Column 'image' missing.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
