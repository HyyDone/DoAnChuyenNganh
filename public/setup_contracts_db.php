<?php
require_once __DIR__ . '/../config/db.php';

try {
    // 1. Create contracts table
    $sql1 = "CREATE TABLE IF NOT EXISTS contracts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        content TEXT,
        status ENUM('pending', 'signed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        signed_at TIMESTAMP NULL,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql1);
    echo "Created contracts table.<br>";

    // 2. Update notifications table enum
    // Existing types: 'request_booking', 'booking_confirmed', 'booking_rejected', 'rental_stopped', 'damage_report', 'damage_confirmed'
    // New types: 'contract_created', 'contract_signed'
    
    $sql2 = "ALTER TABLE notifications MODIFY COLUMN type ENUM('request_booking', 'booking_confirmed', 'booking_rejected', 'rental_stopped', 'damage_report', 'damage_confirmed', 'contract_created', 'contract_signed') NOT NULL";
    $pdo->exec($sql2);
    echo "Updated notifications table enum.<br>";

    echo "Database setup completed successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
