<?php
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    if ($action === 'chart_data') {
        
        $sql = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                city,
                AVG(price / NULLIF(area, 0)) as avg_price_per_m2
            FROM listings
            WHERE 
                city IN ('Ha Noi', 'Ho Chi Minh', 'Hà Nội', 'Hồ Chí Minh', 'TP.HCM')
                AND area > 0 
                AND price > 0
                AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY 
                DATE_FORMAT(created_at, '%Y-%m'), 
                city
            ORDER BY month ASC
        ";
        
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $data]);

    } elseif ($action === 'heatmap_data') {
        $city = $_GET['city'] ?? 'Ho Chi Minh';
        
        
        $dbCity = $city;
        if ($city == 'Hồ Chí Minh') $dbCity = 'Ho Chi Minh';
        if ($city == 'Hà Nội') $dbCity = 'Ha Noi';
        
        
        $sql = "
            SELECT 
                district,
                AVG(price / NULLIF(area, 0)) as avg_price_per_m2,
                COUNT(*) as count
            FROM listings
            WHERE 
                (city = :city OR city = :city_utf8)
                AND area > 0
                AND price > 0
            GROUP BY district
            HAVING count >= 1
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':city' => $dbCity,
            ':city_utf8' => $city 
        ]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $data]);
        
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
