<?php
require_once __DIR__ . '/../config/db.php';

try {
    // 1. Create damage_reports table
    $sql1 = "CREATE TABLE IF NOT EXISTS damage_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        reporter_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        cost DECIMAL(12,2),
        status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql1);
    echo "Created damage_reports table.<br>";

    // 2. Update notifications table enum
    // Check if enum contains new types first to avoid error or just run alter
    // Simple way: just run ALTER. Note: This replaces the enum list, so we must include ALL existing types.
    // Existing types based on user request/file exploration: 'request_booking', 'booking_confirmed', 'booking_rejected'
    // New types: 'damage_report', 'damage_confirmed'
    
    $sql2 = "ALTER TABLE notifications MODIFY COLUMN type ENUM('request_booking', 'booking_confirmed', 'booking_rejected', 'damage_report', 'damage_confirmed') NOT NULL";
    $pdo->exec($sql2);
    echo "Updated notifications table enum.<br>";

    echo "Database setup completed successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
