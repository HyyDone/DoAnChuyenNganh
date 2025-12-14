<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Retry updating notifications table enum including 'rental_stopped'
    $sql2 = "ALTER TABLE notifications MODIFY COLUMN type ENUM('request_booking', 'booking_confirmed', 'booking_rejected', 'rental_stopped', 'damage_report', 'damage_confirmed') NOT NULL";
    $pdo->exec($sql2);
    echo "Updated notifications table enum successfully.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
