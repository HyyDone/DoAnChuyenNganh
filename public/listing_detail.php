<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

$listingId = $_GET['id'] ?? 0;

if (!$listingId) {
    header('Location: /listings.php');
    exit;
}

// Fetch Listing Details
$stmt = $pdo->prepare("
    SELECT l.*, u.full_name as owner_name, u.phone as owner_phone, u.avatar as owner_avatar
    FROM listings l
    JOIN users u ON l.owner_id = u.id
    WHERE l.id = ?
");
$stmt->execute([$listingId]);
$listing = $stmt->fetch();

if (!$listing) {
    die("Phòng trọ không tồn tại.");
}

// Fetch Images
$imgStmt = $pdo->prepare("SELECT file_path FROM listing_images WHERE listing_id = ?");
$imgStmt->execute([$listingId]);
$images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch Recommendations (Same City, Different ID)
$recStmt = $pdo->prepare("
    SELECT id, title, price, address, 
           (SELECT file_path FROM listing_images WHERE listing_id = listings.id AND is_cover = 1 LIMIT 1) as cover_image
    FROM listings 
    WHERE city = ? AND id != ? AND status = 'available'
    LIMIT 5
");
$recStmt->execute([$listing['city'] ?? '', $listingId]);
$recommendations = $recStmt->fetchAll();

// Current User Info for Form
$user = null;
if (isset($_SESSION['user_id'])) {
    $uStmt = $pdo->prepare("SELECT full_name, phone, email FROM users WHERE id = ?");
    $uStmt->execute([$_SESSION['user_id']]);
    $user = $uStmt->fetch();
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($listing['title'] ?? 'Chi tiết phòng') ?> - Thuê Trọ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .detail-container {
            display: flex;
            max-width: 1200px;
            margin: 20px auto;
            gap: 20px;
            padding: 0 16px;
        }
        
        /* 3 Columns Layout */
        .layout-left, .layout-right {
            flex: 1; /* Equal small width */
            width: 250px;
            min-width: 250px;
        }
        
        .layout-center {
            flex: 2; /* Bigger width */
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        /* Recommendation Card */
        .rec-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
            display: block;
            transition: transform 0.2s;
        }
        .rec-card:hover { transform: translateY(-3px); }
        .rec-img { width: 100%; height: 150px; object-fit: cover; }
        .rec-info { padding: 10px; }
        .rec-title { font-weight: bold; font-size: 0.95rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;}
        .rec-price { color: #dc3545; font-weight: bold; margin-top: 5px; }

        /* Detail Styles */
        .listing-title { font-size: 1.8rem; margin: 0 0 10px 0; color: #333; }
        .listing-price { color: #dc3545; font-size: 1.5rem; font-weight: bold; margin-bottom: 15px; }
        /* Carousel Styles */
        .carousel-container {
            position: relative;
            width: 100%;
            height: 400px;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .carousel-slide {
            width: 100%;
            height: 100%;
            display: none;
        }
        .carousel-slide.active {
            display: block;
        }
        .carousel-slide img {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Contain to see full image without cropping in black box */
        }
        
        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
            z-index: 2;
        }
        .carousel-btn:hover { background: rgba(0,0,0,0.8); }
        .carousel-btn.prev { left: 10px; }
        .carousel-btn.next { right: 10px; }

        .img-grid { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; }
        .img-thumb { width: 80px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; opacity: 0.6; transition: opacity 0.2s; border: 2px solid transparent;}
        .img-thumb:hover, .img-thumb.active { opacity: 1; border-color: #0866ff; }
        
        /* ... existing styles ... */
        
        .info-row { display: flex; margin-bottom: 10px; color: #555; }
        .info-icon { width: 25px; text-align: center; margin-right: 10px; color: #0866ff; }
        
        /* Form Styles */
        .booking-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            position: sticky;
            top: 80px;
        }
        .form-title { font-size: 1.2rem; font-weight: bold; margin-bottom: 15px; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .form-control[readonly] { background: #f9f9f9; }
        .btn-submit { width: 100%; background: #0866ff; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: #0056b3; }
        
        .login-prompt { text-align: center; padding: 20px; background: #f0f2f5; border-radius: 6px; }
        .login-btn { display: inline-block; background: #0866ff; color: white; padding: 8px 20px; border-radius: 20px; text-decoration: none; font-weight: bold; margin-top: 10px; }
        
        .btn-favorite {
            background: none;
            border: 1px solid #ddd;
            color: #ccc;
            padding: 8px 12px;
            border-radius: 50%;
            cursor: pointer;
            margin-left: 10px;
            transition: all 0.2s;
            font-size: 1.2rem;
            width: 40px; 
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-favorite.active {
            color: #e91e63;
            border-color: #e91e63;
            background: #fff0f5;
        }
        .btn-favorite:hover {
            background: #fff0f5;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="detail-container">
        <!-- LEFT: Recommendations -->
        <div class="layout-left">
            <h3 style="margin-top:0; font-size:1.1rem; color:#555;">Có thể bạn quan tâm</h3>
            <?php if (empty($recommendations)): ?>
                <p style="color:#888;">Chưa có đề xuất nào tại <?= htmlspecialchars($listing['city'] ?? '') ?>.</p>
            <?php else: ?>
                <?php foreach ($recommendations as $rec): ?>
                    <a href="/listing_detail.php?id=<?= $rec['id'] ?>" class="rec-card">
                        <?php 
                            $recImg = $rec['cover_image'] ?? 'assets/default-room.jpg';
                            if ($recImg && $recImg[0] !== '/') $recImg = '/' . $recImg;
                        ?>
                        <img src="<?= htmlspecialchars($recImg) ?>" class="rec-img" alt="Room">
                        <div class="rec-info">
                            <div class="rec-title"><?= htmlspecialchars($rec['title'] ?? 'Không tiêu đề') ?></div>
                            <div class="rec-price"><?= number_format($rec['price'] ?? 0) ?> đ</div>
                            <div style="font-size:0.8rem; color:#666; margin-top:4px;">
                                <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($rec['address'] ?? '') ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- CENTER: Detail -->
        <div class="layout-center">
            
            <!-- Carousel -->
            <div class="carousel-container" id="mainCarousel">
                <?php 
                    $safeImages = [];
                    if (!empty($images)) {
                        foreach ($images as $img) {
                             $safeImages[] = ($img && $img[0] !== '/') ? '/' . $img : $img;
                        }
                    } else {
                        $safeImages[] = 'assets/default-room.jpg';
                    }
                ?>
                
                <?php foreach ($safeImages as $index => $imgSrc): ?>
                    <div class="carousel-slide <?= $index === 0 ? 'active' : '' ?>">
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Slide <?= $index + 1 ?>">
                    </div>
                <?php endforeach; ?>

                <?php if (count($safeImages) > 1): ?>
                    <button class="carousel-btn prev" onclick="moveSlide(-1)">&#10094;</button>
                    <button class="carousel-btn next" onclick="moveSlide(1)">&#10095;</button>
                <?php endif; ?>
            </div>
            
            <!-- Thumbnails -->
            <?php if (count($safeImages) > 1): ?>
                <div class="img-grid">
                    <?php foreach ($safeImages as $index => $imgSrc): ?>
                        <img src="<?= htmlspecialchars($imgSrc) ?>" class="img-thumb <?= $index === 0 ? 'active' : '' ?>" onclick="currentSlide(<?= $index ?>)" alt="Thumb">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <h1 class="listing-title"><?= htmlspecialchars($listing['title'] ?? 'Không tiêu đề') ?></h1>
                <button class="btn-favorite" id="favBtn" onclick="toggleFavorite(<?= $listingId ?>)">
                    <i class="fa-regular fa-heart"></i>
                </button>
            </div>
            <div class="listing-price"><?= number_format($listing['price'] ?? 0) ?> đ/tháng</div>
            
            <hr style="border:0; border-top:1px solid #eee; margin: 20px 0;">

            <div class="info-row">
                <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                <div><?= htmlspecialchars($listing['address'] ?? '') ?>, <?= htmlspecialchars($listing['district'] ?? '') ?>, <?= htmlspecialchars($listing['city'] ?? '') ?></div>
            </div>
            <div class="info-row">
                <div class="info-icon"><i class="fa-solid fa-house"></i></div>
                <div>Loại phòng: <strong><?= ucfirst($listing['room_type'] ?? '') ?></strong></div>
            </div>
            <?php if (!empty($listing['area'])): ?>
            <div class="info-row">
                <div class="info-icon"><i class="fa-solid fa-ruler-combined"></i></div>
                <div>Diện tích: <strong><?= $listing['area'] ?> m²</strong></div>
            </div>
            <?php endif; ?>



            <div style="margin-top: 30px;">
                <h3 style="font-size: 1.2rem; border-bottom: 2px solid #0866ff; display: inline-block; padding-bottom: 5px;">Mô tả chi tiết</h3>
                <div style="line-height: 1.6; color: #333;">
                    <?= nl2br(htmlspecialchars($listing['description'] ?? '')) ?>
                </div>
            </div>

            <!-- Host Info -->
            <div style="margin-top: 30px; background: #e7f3ff; padding: 15px; border-radius: 8px; display: flex; align-items: center; gap: 15px;">
                <img src="<?= htmlspecialchars($listing['owner_avatar'] ?? '/assets/default-avatar.png') ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                <div>
                    <div style="font-weight: bold; font-size: 1.1rem;"><?= htmlspecialchars($listing['owner_name'] ?? 'Chủ nhà') ?></div>
                    <div style="color: #666;">
                        <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($listing['owner_phone'] ?? 'Liên hệ chủ nhà') ?>
                    </div>
                </div>
                <a href="tel:<?= htmlspecialchars($listing['owner_phone'] ?? '#') ?>" style="margin-left: auto; background: white; color: #0866ff; padding: 8px 15px; border-radius: 20px; text-decoration: none; font-weight: bold; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">Gọi Ngay</a>
            </div>

            <!-- Google Map -->
            <div style="margin-top: 30px;">
                <h3 style="font-size: 1.2rem; border-bottom: 2px solid #0866ff; display: inline-block; padding-bottom: 5px;">Vị trí</h3>
                <?php
                    $fullAddress = ($listing['address'] ?? '') . ', ' . ($listing['district'] ?? '') . ', ' . ($listing['city'] ?? '');
                    // Use standard Google Maps Output Embed
                    $mapUrl = "https://maps.google.com/maps?q=" . urlencode($fullAddress) . "&output=embed";
                ?>
                <div style="width: 100%; height: 300px; border-radius: 8px; overflow: hidden; margin-top: 15px; border: 1px solid #ddd; position: relative;">
                    <iframe 
                        width="100%" 
                        height="100%" 
                        frameborder="0" 
                        scrolling="no" 
                        marginheight="0" 
                        marginwidth="0" 
                        src="<?= $mapUrl ?>">
                    </iframe>
                </div>
                <div style="margin-top: 10px; text-align: right;">
                    <a href="https://www.google.com/maps?q=<?= urlencode($fullAddress) ?>" target="_blank" style="color: #0866ff; font-weight: bold; text-decoration: none; font-size: 0.9rem;">
                        <i class="fa-solid fa-map-location-dot"></i> Xem trên Google Maps
                    </a>
                </div>
            </div>

        </div>

        <!-- RIGHT: Booking Form / Map -->
        <div class="layout-right">
             <div class="booking-form">
                <div class="form-title">Gửi yêu cầu thuê</div>
                <?php if (isset($_SESSION['user_id']) && $user): ?>
                    <div class="form-group">
                        <label class="form-label">Họ tên</label>
                        <input type="text" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" readonly class="form-control">
                    </div>
                     <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" readonly class="form-control">
                    </div>
                    <form id="rentalRequestForm">
                        <input type="hidden" name="listing_id" value="<?= $listingId ?>">
                        <div class="form-group">
                            <label class="form-label">Ngày bắt đầu</label>
                            <input type="date" name="start_date" required class="form-control" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Thời hạn (tháng)</label>
                            <input type="number" name="months" required min="1" value="12" class="form-control">
                        </div>

                        <button type="button" onclick="submitRentalRequest()" class="btn-submit">Gửi yêu cầu</button>
                    </form>
                <?php else: ?>
                    <div class="login-prompt">
                        <p>Bạn cần đăng nhập để gửi yêu cầu thuê.</p>
                        <a href="/login.php" class="login-btn">Đăng nhập</a>
                    </div>
                <?php endif; ?>

                <hr style="border:0; border-top:1px solid #eee; margin: 20px 0;">
                
                <h3 style="margin-top:0; font-size: 1.1rem;">Đặt lịch xem phòng</h3>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div style="margin-top: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Chọn thời gian:</label>
                        <input type="datetime-local" id="viewingTime" class="form-control" min="<?= date('Y-m-d\TH:i') ?>" style="margin-bottom: 10px;">
                        <button onclick="bookViewing(<?= $listingId ?>)" class="btn-submit">Xét lịch</button>
                    </div>
                <?php else: ?>
                    <p>Vui lòng <a href="/login.php">đăng nhập</a> để đặt lịch xem phòng.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript -->

    <script>
        async function bookViewing(listingId) {
            const time = document.getElementById('viewingTime').value;
            if (!time) {
                alert('Vui lòng chọn thời gian!');
                return;
            }

            try {
                const res = await fetch('/api/rentals.php?action=schedule_viewing', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ listing_id: listingId, appointment_time: time })
                });
                const result = await res.json();
                if (result.success) {
                    alert('Đã gửi yêu cầu xem phòng! Chủ nhà sẽ xác nhận sớm.');
                } else {
                    alert('Lỗi: ' + (result.error || result.message || 'Không thể gửi yêu cầu'));
                }
            } catch (e) {
                console.error(e);
                alert('Có lỗi xảy ra.');
            }
        }
    </script>
    <script src="/assets/js/main.js"></script>
    <script>

        let slideIndex = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const thumbs = document.querySelectorAll('.img-thumb');
        
        function moveSlide(n) {
            showSlide(slideIndex += n);
        }
        
        function currentSlide(n) {
            showSlide(slideIndex = n);
        }
        
        function showSlide(n) {
            if (n >= slides.length) slideIndex = 0;
            if (n < 0) slideIndex = slides.length - 1;
            
            slides.forEach(slide => slide.style.display = 'none');
            thumbs.forEach(thumb => thumb.classList.remove('active'));
            
            slides[slideIndex].style.display = 'block';
            if(thumbs[slideIndex]) thumbs[slideIndex].classList.add('active');
        }

        function toggleFavorite(id) {
            alert('Tính năng đang phát triển!');
        }
        
        async function submitRentalRequest() {
            const form = document.getElementById('rentalRequestForm');
            const data = {
                listing_id: form.listing_id.value,
                start_date: form.start_date.value,
                duration: form.months.value
            };
            
            alert('Đã gửi yêu cầu thuê (Demo)!');
        }
    </script>
</body>
</html>