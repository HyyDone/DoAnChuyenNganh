<?php
require_once __DIR__ . '/../config/db.php';
session_start();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Trang Chủ - Thuê Trọ Online</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-button {
            display: inline-block;
            background-color: #1877f2;
            color: white;
            padding: 15px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(24, 119, 242, 0.4);
        }

        .cta-button:hover {
            background-color: #166fe5;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(24, 119, 242, 0.6);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .section-title {
            text-align: center;
            font-size: 2rem;
            color: #333;
            margin-bottom: 40px;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: #1877f2;
            margin: 10px auto 0;
            border-radius: 2px;
        }

        .listing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .listing-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }

        .listing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .card-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .listing-card:hover .card-image img {
            transform: scale(1.1);
        }

        .card-location-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            backdrop-filter: blur(4px);
        }

        .card-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin: 0 0 10px 0;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .card-price {
            color: #e91e63;
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .card-info {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 0.9rem;
            margin-top: auto;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .card-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<header class="hero-section">
    <div class="hero-content">
        <h1>Tìm Không Gian Sống Lý Tưởng</h1>
        <p>Khám phá hàng ngàn phòng trọ, căn hộ và nhà nguyên căn với giá cả hợp lý và tiện nghi đầy đủ.</p>
        <a href="/listings.php" class="cta-button">Tìm Phòng Ngay</a>
    </div>
</header>

<div class="container">
    <h2 class="section-title">Phòng Trọ Đề Xuất</h2>

    <div class="listing-grid">
        <?php
        try {
            $stmt = $pdo->query("
                SELECT l.*, 
                       (SELECT file_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as main_image 
                FROM listings l 
                WHERE l.status = 'available' 
                ORDER BY l.created_at DESC 
                LIMIT 6
            ");
            $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($listings) {
                foreach ($listings as $listing) {
                    $imagePath = $listing['main_image'] ? $listing['main_image'] : 'https://placehold.co/400x300?text=Phong+Tro';
                    $formattedPrice = number_format($listing['price'], 0, ',', '.') . ' ₫';
                    
                    echo '
                    <a href="/listing_detail.php?id=' . $listing['id'] . '" class="listing-card">
                        <div class="card-image">
                            <img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($listing['title']) . '">
                            <div class="card-location-badge"><i class="fa-solid fa-location-dot"></i> ' . htmlspecialchars($listing['city']) . '</div>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title">' . htmlspecialchars($listing['title']) . '</h3>
                            <div class="card-price">' . $formattedPrice . '</div>
                            <div class="card-info">
                                <span><i class="fa-solid fa-ruler-combined"></i> ' . ($listing['area'] ? $listing['area'] . 'm²' : 'N/A') . '</span>
                                <span><i class="fa-solid fa-user-group"></i> ' . ($listing['room_type'] == 'share' ? 'Ở ghép' : 'Riêng tư') . '</span>
                            </div>
                        </div>
                    </a>
                    ';
                }
            } else {
                echo '<p style="text-align:center; grid-column: 1/-1; color: #666;">Chưa có phòng trọ nào được đăng.</p>';
            }

        } catch (PDOException $e) {
            echo '<p style="text-align:center; color:red;">Lỗi tải dữ liệu: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
