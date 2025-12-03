<?php
require_once __DIR__ . '/config/db.php';

try {
    $stmt = $pdo->query("DESCRIBE posts");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in posts table: " . implode(", ", $columns) . "\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
