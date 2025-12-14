<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Create post_images table
    $sql = "CREATE TABLE IF NOT EXISTS post_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Table 'post_images' created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
