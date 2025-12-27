<?php
require_once __DIR__ . '/../config/db.php';
session_start();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Tham Khảo Giá - Thuê Trọ Online</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; }
        
        .banner-section {
            width: 100%;
            height: 400px;
            overflow: hidden;
            position: relative;
            margin-top: 20px; /* Moves banner down */
        }
        .banner-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px 20px; }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 0; /* Sát với banner */
            margin-bottom: 40px;
        }
        .feature-item {
            display: flex; /* Ensure centering works well if items are flex containers */
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .feature-item img {
            width: 40%; /* Make images smaller */
            border-radius: 8px;
            /* box-shadow: 0 4px 8px rgba(0,0,0,0.1); */ /* Removed shadow for cleaner look if requested, or keep it? User image shows flat icons. Let's keep it simple or remove if they look like transparency issues. The user screenshot looks clean. Let's keep shadow for now but maybe make it lighter or remove if icons are transparent. The icons are SVG. */
            /* Actually, looking at user screenshot, icons don't have box-shadow or border-radius background. They look like standalone icons. */
            /* I'll remove shadow and border-radius for the icons based on the screenshot style */
            transition: transform 0.3s;
            display: block;
            margin-bottom: 15px;
        }
        .feature-item img:hover { transform: translateY(-5px); }
        
        .feature-desc {
            font-size: 1.1rem;
            color: #444;
            max-width: 80%;
            line-height: 1.4;
        }

        .section-title {
            text-align: center;
            margin: 40px 0 20px;
            font-size: 2rem;
            color: #333;
        }
        
        .chart-container, .map-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        
        #rentalHeatmap {
            width: 100%;
            height: 500px;
            border-radius: 8px;
        }
        
        .city-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .city-btn {
            padding: 10px 25px;
            border: none;
            background: #e9ecef;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .city-btn.active {
            background: #1877f2;
            color: white;
            box-shadow: 0 4px 10px rgba(24, 119, 242, 0.3);
        }
        
        .city-btn:hover:not(.active) {
            background: #dde2e6;
        }

        .banner-overlay {
            position: absolute;
            top: 50%;
            left: 5%;
            transform: translateY(-50%);
            max-width: 40%;
            color: #333;
            text-shadow: 1px 1px 0 rgba(255,255,255,0.8);
            z-index: 10;
            pointer-events: none;
        }
        
        .banner-overlay h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #d35400;
            font-weight: 800;
            line-height: 1.2;
        }
        
        .banner-overlay p {
            font-size: 1.2rem;
            font-weight: 600;
            color: #555;
            margin: 0;
        }
        
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container">
    
    <!-- Top Banner -->
    <div class="banner-section">
        <img src="/assets/desktop-banner.jpg" alt="Toàn cảnh biến động BĐS" onerror="this.src='https://placehold.co/1200x400?text=Banner+BĐS'">
        <div class="banner-overlay">
            <h1>Toàn cảnh biến động giá BĐS</h1>
            <p>Cập nhật liên tục dữ liệu thị trường thực tế</p>
        </div>
    </div>

    <!-- 3 Images Grid -->
    <div class="feature-grid">
        <div class="feature-item">
            <img src="/assets/1.svg" alt="Biểu đồ 1" onerror="this.src='https://placehold.co/400x300?text=Image+1'">
            <div class="feature-desc">Giá thực tế đang giao dịch trên thị trường</div>
        </div>
        <div class="feature-item">
            <img src="/assets/2.svg" alt="Biểu đồ 2" onerror="this.src='https://placehold.co/400x300?text=Image+2'">
            <div class="feature-desc">Chi tiết đến từng phường / huyện 5 thành phố lớn</div>
        </div>
        <div class="feature-item">
            <img src="/assets/3.svg" alt="Biểu đồ 3" onerror="this.src='https://placehold.co/400x300?text=Image+3'">
            <div class="feature-desc">Phân loại riêng biệt nhà đất, chung cư</div>
        </div>
    </div>

    <!-- Monthly Price Comparison Chart -->
    <h2 class="section-title">So sánh giá phòng trọ</h2>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="chart-container">
            <h3 style="text-align: center; margin-bottom: 15px;">Hà Nội</h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="chartHanoi"></canvas>
            </div>
        </div>
        <div class="chart-container">
            <h3 style="text-align: center; margin-bottom: 15px;">TP. Hồ Chí Minh</h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="chartHCM"></canvas>
            </div>
        </div>
    </div>

    <!-- Heatmap -->
    <h2 class="section-title">Bản đồ giá phòng trọ</h2>
    <div class="map-container">
        <div class="city-controls">
            <button class="city-btn active" onclick="loadCity('Hà Nội')" id="btn-hanoi">Hà Nội</button>
            <button class="city-btn" onclick="loadCity('Đà Nẵng')" id="btn-danang">Đà Nẵng</button>
            <button class="city-btn" onclick="loadCity('Hồ Chí Minh')" id="btn-hcm">Hồ Chí Minh</button>
        </div>
        <div id="rentalHeatmap"></div>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="/assets/js/price_stats.js?v=<?php echo time(); ?>"></script>

</body>
</html>
