<?php
require_once __DIR__ . '/../config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL COMMENT 'Tiêu đề tin',
        slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Slug SEO',
        image_url VARCHAR(255) DEFAULT NULL COMMENT 'Ảnh đại diện tin',
        category VARCHAR(100) NOT NULL COMMENT 'Thể loại tin tức',
        summary TEXT COMMENT 'Mô tả ngắn',
        content LONGTEXT NOT NULL COMMENT 'Nội dung chi tiết',
        views INT DEFAULT 0 COMMENT 'Lượt xem',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày đăng',
        INDEX idx_category (category),
        INDEX idx_views (views)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "Created/Verified news table.<br>";

    $pdo->exec("TRUNCATE TABLE news");
    echo "Cleared existing news data.<br>";

    $categories = [
        'Tin thuê trọ',
        'Kinh nghiệm thuê nhà',
        'Pháp lý nhà trọ',
        'Cảnh báo lừa đảo',
        'Tin thị trường'
    ];

    $start_date = new DateTime();
    
    $stmt = $pdo->prepare("INSERT INTO news (title, slug, category, summary, content, views, created_at, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        'Phòng trọ giá rẻ quận Cầu Giấy chỉ 3 triệu',
        'phong-tro-gia-re-cau-giay',
        'Tin thuê trọ',
        'Cần cho thuê phòng trọ khép kín, đầy đủ tiện nghi tại Cầu Giấy.',
        'Chi tiết phòng trọ... Diện tích 25m2, điều hòa, nóng lạnh...',
        150,
        $start_date->modify('-1 day')->format('Y-m-d H:i:s'),
        'https://placeholder.co/600x400?text=Phong+Tro' 
    ]);

    $stmt->execute([
        'Căn hộ mini Đống Đa full nội thất',
        'can-ho-mini-dong-da',
        'Tin thuê trọ',
        'Cho thuê căn hộ chung cư mini tại Đống Đa, vào ở ngay.',
        'Căn hộ 35m2, có thang máy, bảo vệ 24/7...',
        200,
        $start_date->modify('-2 days')->format('Y-m-d H:i:s'),
        'https://placeholder.co/600x400?text=Can+Ho'
    ]);

    $stmt->execute([
        '5 lưu ý quan trọng khi đi thuê phòng trọ sinh viên',
        '5-luu-y-thue-phong-tro',
        'Kinh nghiệm thuê nhà',
        'Sinh viên đi thuê phòng trọ cần chú ý những điều sau để tránh bị lừa.',
        'Thứ nhất, kiểm tra an ninh. Thứ hai, xem kỹ hợp đồng...',
        500,
        $start_date->modify('-5 days')->format('Y-m-d H:i:s'),
        'https://placeholder.co/600x400?text=Kinh+Nghiem'
    ]);
    
    $stmt->execute([
        'Cách deal giá thuê nhà hiệu quả',
        'cach-deal-gia-thue-nha',
        'Kinh nghiệm thuê nhà',
        'Bí quyết thương lượng giá thuê nhà với chủ nhà để có giá tốt nhất.',
        'Nghiên cứu giá thị trường, tìm ra các lỗi nhỏ của căn nhà...',
        300,
        $start_date->modify('-1 day')->format('Y-m-d H:i:s'),
        'https://placeholder.co/600x400?text=Deal+Gia'
    ]);

    $stmt->execute([
        'Quy định mới về đăng ký tạm trú 2024',
        'quy-dinh-tam-tru-2024',
        'Pháp lý nhà trọ',
        'Cập nhật những thay đổi mới nhất về luật cư trú và thủ tục đăng ký tạm trú.',
        'Theo thông tư mới, thủ tục đăng ký tạm trú đã được đơn giản hóa...',
        1200,
        $start_date->modify('-10 days')->format('Y-m-d H:i:s'),
        'https://placeholder.co/600x400?text=Phap+Ly'
    ]);

    $stmt->execute([
        'Cảnh báo chiêu trò lừa cọc giữ phòng',
        'canh-bao-lua-coc',
        'Cảnh báo lừa đảo',
        'Nhiều sinh viên bị lừa mất tiền cọc vì tin lời môi giới ảo.',
        'Các đối tượng thường đăng ảnh phòng đẹp giá rẻ bất ngờ...',
        2500,
        $start_date->modify('-3 days')->format('Y-m-d H:i:s'),
        'https://placeholder.co/600x400?text=Lua+Dao'
    ]);

    $stmt->execute([
        'Giá thuê nhà Hà Nội tăng mạnh dịp cuối năm',
        'gia-thue-nha-tang-manh',
        'Tin thị trường',
        'Thị trường cho thuê căn hộ và phòng trọ tại Hà Nội đang nóng lên.',
        'Nhu cầu tìm thuê nhà tăng cao khiến giá phòng tại các quận trung tâm...',
        800,
        $start_date->modify('-2 days')->format('Y-m-d H:i:s'),
        'https://placeholder.co/600x400?text=Thi+Truong'
    ]);
    
     $stmt->execute([
        'Xu hướng ở ghép của giới trẻ 2025',
        'xu-huong-o-ghep',
        'Tin thị trường',
        'Thay vì thuê phòng ở một mình, nhiều người trẻ chọn xu hướng co-living.',
        'Mô hình co-living giúp tiết kiệm chi phí và tăng kết nối...',
        600,
        $start_date->modify('-4 days')->format('Y-m-d H:i:s'),
        'https://placeholder.co/600x400?text=O+Ghep'
    ]);

    echo "Seeded News Data successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
