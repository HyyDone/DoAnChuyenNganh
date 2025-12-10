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
        .listing-img-main { width: 100%; height: 400px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
        .img-grid { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; }
        .img-thumb { width: 80px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; opacity: 0.7; transition: opacity 0.2s; }
        .img-thumb:hover, .img-thumb.active { opacity: 1; border: 2px solid #0866ff; }
        
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
            <?php 
                $mainImage = !empty($images) ? $images[0] : 'assets/default-room.jpg';
                if ($mainImage && $mainImage[0] !== '/') $mainImage = '/' . $mainImage;
            ?>
            <img src="<?= htmlspecialchars($mainImage) ?>" id="mainImage" class="listing-img-main" alt="Main Image">
            
            <?php if (count($images) > 1): ?>
                <div class="img-grid">
                    <?php foreach ($images as $img): 
                        $thumbPath = ($img && $img[0] !== '/') ? '/' . $img : $img;
                    ?>
                        <img src="<?= htmlspecialchars($thumbPath) ?>" class="img-thumb" onclick="changeImage(this.src)" alt="Thumb">
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
            <div class="info-row">
                <div class="info-icon"><i class="fa-solid fa-user-shield"></i></div>
                <div>Chủ nhà: <strong><?= htmlspecialchars($listing['owner_name'] ?? 'Không tên') ?></strong></div>
            </div>

            <h3 style="margin-top:30px;">Mô tả chi tiết</h3>
            <div style="line-height:1.6; color:#333;">
                <?= nl2br(htmlspecialchars($listing['description'] ?? 'Không có mô tả')) ?>
            </div>
        </div>

        <!-- RIGHT: Booking Form -->
        <div class="layout-right">
            <div class="booking-form">
                <div class="form-title">Đăng ký thuê</div>
                
                <?php if ($user): ?>
                    <form id="bookingForm" onsubmit="submitBooking(event)">
                        <input type="hidden" id="listingId" value="<?= $listing['id'] ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? 'Guest') ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? 'N/A') ?>" readonly>
                        </div>
                        
                         <div class="form-group">
                            <label class="form-label">Ngày bắt đầu (Dự kiến)</label>
                            <input type="date" id="startDate" class="form-control" required>
                        </div>

                        <button type="submit" class="btn-submit">Yêu cầu thuê ngay</button>
                        <p style="font-size:0.8rem; color:#666; margin-top:10px; text-align:center;">
                            Thông tin của bạn sẽ được gửi tới chủ nhà.
                        </p>
                    </form>
                <?php else: ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                         <div class="login-prompt">
                            <p style="color:red;">Lỗi xác thực người dùng. Vui lòng đăng nhập lại.</p>
                             <a href="/logout.php" class="login-btn">Đăng xuất</a>
                        </div>
                    <?php else: ?>
                        <div class="login-prompt">
                            <p>Vui lòng đăng nhập để gửi yêu cầu thuê phòng.</p>
                            <a href="/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="login-btn">Đăng nhập</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <script>
        function changeImage(src) {
            document.getElementById('mainImage').src = src;
            document.querySelectorAll('.img-thumb').forEach(img => img.classList.remove('active'));
            event.target.classList.add('active');
        }

        // Check favorite status on load
        document.addEventListener('DOMContentLoaded', function() {
            checkFavoriteStatus();
        });

        async function checkFavoriteStatus() {
            const btn = document.getElementById('favBtn');
            const listingId = <?= $listingId ?>;
            try {
                const res = await fetch(`/api/favorites.php?listing_id=${listingId}`);
                const data = await res.json();
                if (data.success && data.is_favorite) {
                    btn.classList.add('active');
                    btn.querySelector('i').classList.replace('fa-regular', 'fa-solid');
                }
            } catch(e) { console.error(e); }
        }

        function toggleFavorite(id) {
            const btn = document.getElementById('favBtn');
            fetch('/api/favorites.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ listing_id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const icon = btn.querySelector('i');
                    if (data.action === 'added') {
                        btn.classList.add('active');
                        icon.classList.replace('fa-regular', 'fa-solid');
                    } else {
                        btn.classList.remove('active');
                        icon.classList.replace('fa-solid', 'fa-regular');
                    }
                } else {
                    if (data.message === 'Unauthorized') {
                         alert('Vui lòng đăng nhập để sử dụng tính năng này.');
                         window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.href);
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                }
            })
            .catch(err => console.error(err));
        }

        async function submitBooking(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.innerText;
            btn.innerText = 'Đang gửi...';
            btn.disabled = true;

            const data = {
                listing_id: document.getElementById('listingId').value,
                start_date: document.getElementById('startDate').value
            };

            try {
                const res = await fetch('/api/bookings.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                
                if (result.success) {
                    alert('Gửi yêu cầu thành công! Chủ nhà sẽ liên hệ với bạn sớm.');
                    e.target.reset();
                } else {
                    alert('Lỗi: ' + (result.error || 'Có lỗi xảy ra'));
                }
            } catch (err) {
                console.error(err);
                alert('Lỗi kết nối đến máy chủ.');
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
