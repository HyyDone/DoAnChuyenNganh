<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Check distribution
    echo "Current Status Distribution:\n";
    $stmt = $pdo->query("SELECT status, COUNT(*) as c FROM listings GROUP BY status");
    while ($row = $stmt->fetch()) {
        echo $row['status'] . ": " . $row['c'] . "\n";
    }

    // Update 'pending' or NULL to 'available'
    $update = $pdo->prepare("UPDATE listings SET status = 'available' WHERE status IS NULL OR status = '' OR status = 'pending'");
    $update->execute();
    echo "\nUpdated " . $update->rowCount() . " listings to 'available'.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
