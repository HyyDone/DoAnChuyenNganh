<?php
require_once __DIR__ . '/config/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/sql_add_payment_for_month.sql');
    if (!$sql) {
        die("Error reading SQL file.\n");
    }

    // Split multiple queries if any (basic split by ;)
    // But my SQL file has 2 statements.
    
    // PDO doesn't support multiple queries in one execute() call by default in some setups, 
    // but usually exec() handles it if emulation is on.
    // Safer to split.
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            echo "Executing: " . substr($stmt, 0, 50) . "...\n";
            $pdo->exec($stmt);
        }
    }
    
    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
