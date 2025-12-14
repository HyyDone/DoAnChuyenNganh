<?php
require_once __DIR__ . '/../config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS appliances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL COMMENT 'Người đăng',
        name VARCHAR(255) NOT NULL,
        type ENUM('Tủ lạnh', 'Máy giặt', 'Quạt', 'Bếp Gas', 'Khác') NOT NULL,
        price DECIMAL(10, 2) NOT NULL COMMENT 'Giá thuê trên tháng',
        quantity INT DEFAULT 1,
        description TEXT,
        status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        image_path VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "Created/Verified appliances table.<br>";

    $stmtUser = $pdo->query("SELECT id FROM users LIMIT 1");
    $user = $stmtUser->fetch();
    $userId = $user ? $user['id'] : 1;

    $stmtCount = $pdo->query("SELECT COUNT(*) FROM appliances");
    if ($stmtCount->fetchColumn() == 0) {
        $stmtInsert = $pdo->prepare("INSERT INTO appliances (user_id, name, type, price, quantity, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $appliances = [
            [$userId, 'Máy giặt LG 8kg Inverter', 'Máy giặt', 300000, 2, 'Máy giặt lồng ngang tiết kiệm điện', 'available'],
            [$userId, 'Tủ lạnh Aqua 90L', 'Tủ lạnh', 150000, 5, 'Tủ lạnh mini phù hợp phòng trọ', 'available'],
            [$userId, 'Quạt đứng Senko', 'Quạt', 50000, 10, 'Quạt gió mạnh, bền', 'available'],
            [$userId, 'Bếp Gas đôi mặt kính', 'Bếp Gas', 100000, 3, 'Bếp gas an toàn, tiết kiệm gas', 'available'],
            [$userId, 'Nồi cơm điện Sharp', 'Khác', 70000, 0, 'Nồi cơm nắp gài 1.8L', 'available']
        ];

        foreach ($appliances as $app) {
            $stmtInsert->execute($app);
        }
        echo "Seeded Appliances Data.<br>";
    } else {
        echo "Appliances table already has data.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
