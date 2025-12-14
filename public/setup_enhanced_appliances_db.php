<?php
require_once __DIR__ . '/../config/db.php';

try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM appliances LIKE 'city'");
    if ($colCheck->rowCount() == 0) {
        $pdo->exec("ALTER TABLE appliances ADD COLUMN city VARCHAR(50) DEFAULT 'Ho Chi Minh' AFTER name");
        echo "Added 'city' column to appliances table.<br>";
    } else {
        echo "'city' column already exists in appliances.<br>";
    }

    $colCheckImg = $pdo->query("SHOW COLUMNS FROM appliances LIKE 'image_path'");
    if ($colCheckImg->rowCount() == 0) {
        $pdo->exec("ALTER TABLE appliances ADD COLUMN image_path VARCHAR(255) DEFAULT NULL");
        echo "Added 'image_path' column to appliances table.<br>";
    } else {
         echo "'image_path' column already exists in appliances.<br>";
    }

    $sqlBooking = "CREATE TABLE IF NOT EXISTS appliance_bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        appliance_id INT NOT NULL,
        renter_id INT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        address VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (appliance_id) REFERENCES appliances(id) ON DELETE CASCADE,
        FOREIGN KEY (renter_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sqlBooking);
    echo "Created/Verified appliance_bookings table.<br>";

    $sqlNotif = "ALTER TABLE notifications MODIFY COLUMN type ENUM(
        'request_booking', 
        'booking_confirmed', 
        'booking_rejected', 
        'rental_stopped', 
        'damage_report', 
        'damage_confirmed', 
        'contract_created', 
        'contract_signed',
        'appliance_request',
        'appliance_approved',
        'appliance_rejected'
    ) NOT NULL";
    $pdo->exec($sqlNotif);
    echo "Updated notifications table ENUM.<br>";
    
    $pdo->exec("UPDATE appliances SET city = 'Ho Chi Minh' WHERE city IS NULL OR city = ''");
    echo "Updated default cities.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
