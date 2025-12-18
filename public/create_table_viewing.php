<?php
require_once __DIR__ . '/../config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS viewing_appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        listing_id INT NOT NULL,
        tenant_id INT NOT NULL,
        owner_id INT NOT NULL,
        appointment_time DATETIME NOT NULL,
        status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
        FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Table 'viewing_appointments' created successfully!";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
