<?php
require_once __DIR__ . '/config/db.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('request_booking', 'booking_confirmed', 'booking_rejected') NOT NULL,
        reference_id INT NOT NULL,
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table notifications created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
