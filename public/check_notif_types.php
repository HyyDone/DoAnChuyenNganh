<?php
require_once __DIR__ . '/../config/db.php';
try {
    $stmt = $pdo->query("SELECT DISTINCT type FROM notifications");
    $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Current types in DB: " . implode(', ', $types) . "<br>";
    
    $stmt2 = $pdo->query("DESCRIBE notifications type");
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);
    echo "Current column Type: " . $row['Type'];
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
