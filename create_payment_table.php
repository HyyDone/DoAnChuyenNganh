<?php
require_once __DIR__ . '/config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS rental_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        payment_date DATE NOT NULL,
        note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Table rental_payments created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
