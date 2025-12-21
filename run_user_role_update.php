<?php
require_once __DIR__ . '/config/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/database/update_users_role.sql');
    if (!$sql) {
        die("Error reading SQL file.\n");
    }

    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            echo "Executing: " . substr($stmt, 0, 50) . "...\n";
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                // If column already exists (code 42S21), ignore
                if ($e->getCode() == '42S21') {
                     echo "Column already exists, skipping.\n";
                } else {
                     throw $e;
                }
            }
        }
    }
    
    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
