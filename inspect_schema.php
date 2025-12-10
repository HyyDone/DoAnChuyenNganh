<?php
require_once __DIR__ . '/config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE listings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if ($col['Field'] === 'room_type') {
            print_r($col);
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
