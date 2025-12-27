<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/upload.php';
require_login();

$currentUid = (int) current_user_id();
$profileUid = isset($_GET['id']) ? intval($_GET['id']) : $currentUid;


$stmt = $pdo->prepare("SELECT id,username,email,full_name,phone,avatar,bio,is_landlord, created_at FROM users WHERE id = ?");
$stmt->execute([$profileUid]);
$user = $stmt->fetch();



$stmtListings = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE owner_id = ? AND status = 'available'");
$stmtListings->execute([$profileUid]);
$countListings = $stmtListings->fetchColumn();


$stmtReviews = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM user_reviews WHERE reviewee_id = ?");
$stmtReviews->execute([$profileUid]);
$reviewStats = $stmtReviews->fetch(PDO::FETCH_ASSOC);
$avgRating = $reviewStats['avg_rating'] ? round($reviewStats['avg_rating'], 1) : 0;
$countReviews = $reviewStats['count'];


$joinDate = new DateTime($user['created_at'] ?? 'now');
$now = new DateTime();
$interval = $now->diff($joinDate);
$yearsActive = $interval->y;
$monthsActive = $interval->m;
$timeActiveStr = "";
if ($yearsActive > 0) $timeActiveStr .= "$yearsActive năm ";
if ($monthsActive > 0) $timeActiveStr .= "$monthsActive tháng";
if (empty($timeActiveStr)) $timeActiveStr = "Mới tham gia";


if (!$user) {
    die("Người dùng không tồn tại.");
}

$isOwner = ($currentUid === $profileUid);

$errors = [];
$success = null;


if ($isOwner && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $full = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $res = upload_avatar($_FILES['avatar']);
        if (isset($res['error'])) {
            $errors[] = $res['error'];
        } else {
            $avatarPath = $res['path'];
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, bio = ?, avatar = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$full, $phone, $bio, $avatarPath, $currentUid]);
            $success = "Cập nhật thành công.";
        }
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, bio = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$full, $phone, $bio, $currentUid]);
        $success = "Cập nhật thành công.";
    }
    
    
    $stmt = $pdo->prepare("SELECT id,username,email,full_name,phone,avatar,bio FROM users WHERE id = ?");
    $stmt->execute([$profileUid]);
    $user = $stmt->fetch();
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($user['username']) ?> | Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #f0f2f5;
            --card-bg: #ffffff;
            --primary-color: #1877f2;
            --text-color: #050505;
            --secondary-text: #65676b;
            --divider: #ced0d4;
            --hover-bg: #f2f2f2;
        }

        body {
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }

        .profile-container {
            max-width: 940px;
            margin: 0 auto;
            background: var(--card-bg);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding-bottom: 16px;
        }

        .cover-photo {
            height: 500px;
            background: url('/assets/poster.jpg') center center / cover no-repeat;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
            position: relative;
        }

        .profile-header {
            padding: 0 32px;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: -30px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--divider);
        }

        .profile-info {
            display: flex;
            align-items: flex-end;
        }

        .avatar-container {
            position: relative;
            margin-right: 20px;
        }

        .avatar {
            width: 168px;
            height: 168px;
            border-radius: 50%;
            border: 4px solid var(--card-bg);
            object-fit: cover;
            background: white;
        }

        .camera-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #e4e6eb;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid white;
        }

        .user-details h1 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .user-details p {
            margin: 0;
            color: var(--secondary-text);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .edit-profile-btn {
            background: #e4e6eb;
            color: black;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 15px;
            margin-bottom: 16px;
        }

        .edit-profile-btn:hover {
            background: #d8dadf;
        }

        .edit-profile-btn i {
            margin-right: 6px;
        }

        .content-area {
            max-width: 940px;
            margin: 16px auto;
            display: flex;
            gap: 16px;
            padding: 0 16px;
        }

        .intro-card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            flex: 1;
            max-width: 360px;
        }

        .intro-card h2 {
            margin: 0 0 16px;
            font-size: 20px;
        }

        .bio-text {
            text-align: center;
            margin-bottom: 16px;
            font-size: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            color: var(--secondary-text);
            font-size: 15px;
        }

        .info-item i {
            width: 24px;
            font-size: 20px;
            margin-right: 10px;
            color: #8c939d;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(244, 244, 244, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: white;
            width: 100%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid var(--divider);
            position: relative;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 20px;
        }

        .close-btn {
            position: absolute;
            right: 16px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e4e6eb;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .modal-body {
            padding: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 15px;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced0d4;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .save-btn {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
        }

        /* Stats Box */
        .stats-container {
            max-width: 940px;
            margin: 16px auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            padding: 0 16px;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .stat-label { color: #65676b; font-size: 0.9rem; margin-bottom: 5px; }
        .stat-value { font-size: 1.5rem; font-weight: bold; color: #050505; }
        .stat-sub { font-size: 0.8rem; color: #65676b; margin-top: 5px; }
    </style>
</head>

<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="profile-container">
        <div class="cover-photo"></div>
        <div class="profile-header">
            <div class="profile-info">
                <div class="avatar-container">
                    <img src="/<?= htmlspecialchars($user['avatar'] ?: 'assets/default-avatar.png') ?>" alt="Avatar" class="avatar">
                    <?php if ($isOwner): ?>
                    <div class="camera-icon" onclick="openEditModal()">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <h1 style="display:flex; align-items:center; gap:8px;">
                        <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?>
                        <?php if (!empty($user['is_landlord'])): ?>
                            <i class="fa-solid fa-circle-check" style="color:#1877f2; font-size:24px;" title="Đã xác minh chủ trọ"></i>
                        <?php endif; ?>
                    </h1>
                    <p>@<?= htmlspecialchars($user['username']) ?></p>
                </div>
            </div>
            
            <?php if ($isOwner): ?>
                <button class="edit-profile-btn" onclick="openEditModal()">
                    <i class="fa-solid fa-pen"></i> Chỉnh sửa trang cá nhân
                </button>
                <?php if (empty($user['is_landlord'])): ?>
                    <button class="edit-profile-btn" onclick="openOtpModal()" style="margin-left:10px; background:#e7f3ff; color:#1877f2;">
                        <i class="fa-solid fa-user-shield"></i> Kích hoạt vai trò chủ trọ
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <button class="edit-profile-btn" onclick="openChat(<?= $user['id'] ?>, '<?= htmlspecialchars($user['full_name'] ?: $user['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['avatar'] ?? '', ENT_QUOTES) ?>')">
                    <i class="fa-brands fa-facebook-messenger"></i> Nhắn tin
                </button>
                <button class="edit-profile-btn" onclick="openUserReviewModal()" style="margin-left:10px; background:#fff5e4; color:#f5c518;">
                    <i class="fa-solid fa-star"></i> Đánh giá người dùng
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-container">
        <div class="stat-box">
            <div class="stat-label">Thời gian hoạt động</div>
            <div class="stat-value"><?= !empty($yearsActive) && $yearsActive > 0 ? $yearsActive . ' năm' : 'Mới' ?></div>
            <div class="stat-sub"><?= htmlspecialchars($timeActiveStr) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Đã giao dịch</div>
            <div class="stat-value">--</div>
            <div class="stat-sub">Chưa có giao dịch</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Tin hiện có</div>
            <div class="stat-value"><?= $countListings ?> tin</div>
            <div class="stat-sub"><a href="#" style="text-decoration:none; color:#1877f2;">Xem tất cả</a></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Đánh giá</div>
            <div class="stat-value">
                <?= $avgRating > 0 ? $avgRating : '--' ?> 
                <i class="fa-solid fa-star" style="color:#f5c518; font-size:1.2rem;"></i>
            </div>
            <div class="stat-sub"><?= !empty($countReviews) && $countReviews > 0 ? "$countReviews đánh giá" : "Chưa có đánh giá" ?></div>
        </div>
    </div>

    <div class="content-area">
        <div class="intro-card">
            <h2>Giới thiệu</h2>
            <?php if ($success): ?>
                <div style="color:green; margin-bottom: 10px; text-align:center;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div style="color:red; margin-bottom: 10px; text-align:center;">
                    <?php foreach ($errors as $e) echo "<div>" . htmlspecialchars($e) . "</div>"; ?>
                </div>
            <?php endif; ?>

            <?php if ($user['bio']): ?>
                <div class="bio-text"><?= nl2br(htmlspecialchars($user['bio'])) ?></div>
            <?php else: ?>
                <div class="bio-text" style="color:#65676b; font-style:italic;">Chưa có giới thiệu</div>
            <?php endif; ?>

            <div class="info-item">
                <i class="fa-solid fa-envelope"></i>
                <span><?= htmlspecialchars($user['email']) ?></span>
            </div>
            <?php if ($user['phone']): ?>
                <div class="info-item">
                    <i class="fa-solid fa-phone"></i>
                    <span><?= htmlspecialchars($user['phone']) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($isOwner): ?>
            <button class="edit-profile-btn" style="width:100%; justify-content:center;" onclick="openEditModal()">
                Chỉnh sửa chi tiết
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Placeholder for posts or other content -->
        <!-- Placeholder for posts or other content -->
        <div style="flex: 2;">
            <div style="background:white; border-radius:8px; padding:20px; box-shadow:0 1px 2px rgba(0,0,0,0.1);">
                <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Đánh giá từ người dùng</h3>
                <div id="userReviewsList">
                   <div style="text-align:center; color:#888; padding:20px;">Đang tải...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Chỉnh sửa trang cá nhân</h3>
                <div class="close-btn" onclick="closeEditModal()">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </div>
            <div class="modal-body">
                <form method="post" enctype="multipart/form-data" action="/profile.php">
                    <div class="form-group">
                        <label>Ảnh đại diện</label>
                        <div style="text-align:center; margin-bottom:10px;">
                            <img src="/<?= htmlspecialchars($user['avatar'] ?: 'assets/default-avatar.png') ?>" style="width:100px;height:100px;border-radius:50%;object-fit:cover;">
                        </div>
                        <input type="file" name="avatar" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label>Họ và tên</label>
                        <input name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" placeholder="Nhập họ tên hiển thị">
                    </div>
                    <div class="form-group">
                        <label>Tiểu sử (Bio)</label>
                        <textarea name="bio" placeholder="Mô tả ngắn về bạn"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Số điện thoại liên hệ">
                    </div>
                    <button type="submit" class="save-btn">Lưu thay đổi</button>
                </form>
            </div>
        </div>
    </div>

    <!-- OTP Modal -->
    <div class="modal-overlay" id="otpModal">
        <div class="modal-box" style="max-width:400px;">
            <div class="modal-header">
                <h3>Xác thực chủ trọ</h3>
                <div class="close-btn" onclick="document.getElementById('otpModal').classList.remove('active')">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </div>
            <div class="modal-body">
                <p style="margin-bottom:15px; text-align:center;">
                    Nhấn "Gửi mã" để nhận mã OTP qua email, sau đó nhập mã vào bên dưới.
                </p>
                
                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <input type="text" id="otpInput" class="form-control" placeholder="Nhập mã OTP (6 số)" style="flex:1; padding:10px; border:1px solid #ddd; border-radius:6px; text-align:center; letter-spacing:2px;">
                    <button onclick="sendOtp()" id="btnSendOtp" style="padding:10px; background:#e4e6eb; border:none; border-radius:6px; font-weight:600; cursor:pointer;">Gửi mã</button>
                </div>
                
                <button onclick="verifyOtp()" style="width:100%; padding:12px; background:#1877f2; color:white; border:none; border-radius:6px; font-weight:600; cursor:pointer;">Xác nhận</button>
                <div id="otpMsg" style="margin-top:10px; text-align:center; font-size:14px; min-height:20px;"></div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        async function openOtpModal() {
            document.getElementById('otpModal').classList.add('active');
        }

        async function sendOtp() {
            const btn = document.getElementById('btnSendOtp');
            const msg = document.getElementById('otpMsg');
            btn.disabled = true;
            btn.innerText = 'Đang gửi...';
            msg.innerText = '';
            
            try {
                const res = await fetch('/api/otp.php?action=send_otp', { method: 'POST' });
                const data = await res.json();
                
                if (data.success) {
                    msg.style.color = 'green';
                    msg.innerText = data.message;
                    // For demo, maybe auto-fill or alert
                    if (data.debug_otp) {
                        console.log('DEBUG OTP:', data.debug_otp);
                        // alert('DEBUG OTP: ' + data.debug_otp);
                    }
                } else {
                    msg.style.color = 'red';
                    msg.innerText = data.message;
                }
            } catch (err) {
                console.error(err);
                msg.innerText = 'Lỗi kết nối';
            } finally {
                btn.disabled = false;
                btn.innerText = 'Gửi lại';
            }
        }

        async function verifyOtp() {
            const otp = document.getElementById('otpInput').value;
            const msg = document.getElementById('otpMsg');
            
            if (!otp) {
                msg.style.color = 'red';
                msg.innerText = 'Vui lòng nhập mã OTP';
                return;
            }
            
            try {
                const res = await fetch('/api/otp.php?action=verify_otp', {
                    method: 'POST',
                    body: JSON.stringify({ otp: otp }),
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();
                
                if (data.success) {
                    msg.style.color = 'green';
                    msg.innerText = data.message;
                    setTimeout(() => location.reload(), 1500);
                } else {
                     msg.style.color = 'red';
                    msg.innerText = data.message;
                }
            } catch (err) {
                 msg.style.color = 'red';
                 msg.innerText = 'Lỗi kết nối';
            }
        }



        const modal = document.getElementById('editModal');

        function openEditModal() {
            modal.classList.add('active');
        }

        function closeEditModal() {
            modal.classList.remove('active');
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeEditModal();
            }
        });
        async function openUserReviewModal() {
            document.getElementById('userReviewModal').classList.add('active');
        }

        async function submitUserReview() {
            const rating = document.getElementById('userRating').value;
            const comment = document.getElementById('userComment').value;
            
            if (!rating || rating == 0) { alert('Vui lòng chọn số sao!'); return; }
            if (!comment) { alert('Vui lòng nhập nội dung đánh giá!'); return; }

            try {
                const res = await fetch('/api/reviews.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        type: 'user',
                        reviewee_id: <?= $profileUid ?>,
                        rating: rating,
                        comment: comment
                    })
                });
                const result = await res.json();
                if (result.success) {
                    alert('Cảm ơn phản hồi của bạn!');
                    document.getElementById('userReviewModal').classList.remove('active');
                    loadUserReviews();
                    location.reload(); // Reload to update stats
                } else {
                    alert(result.message);
                }
            } catch (e) {
                console.error(e);
                alert('Lỗi kết nối');
            }
        }

        async function loadUserReviews() {
            try {
                const res = await fetch(`/api/reviews.php?type=user&user_id=<?= $profileUid ?>`);
                const data = await res.json();
                if (data.success) {
                    const list = document.getElementById('userReviewsList');
                    if (data.reviews.length > 0) {
                        list.innerHTML = data.reviews.map(r => `
                            <div style="border-bottom:1px solid #eee; padding:15px 0;">
                                <div style="display:flex; align-items:center; gap:10px; margin-bottom:5px;">
                                    <img src="/${r.reviewer_avatar || 'assets/default-avatar.png'}" style="width:32px; height:32px; border-radius:50%; object-fit:cover;">
                                    <div style="font-weight:bold;">${r.reviewer_name}</div>
                                    <div style="color:#f5c518;">
                                        ${'<i class="fa-solid fa-star"></i>'.repeat(r.rating)}
                                    </div>
                                </div>
                                <div style="color:#333; margin-top:5px;">${r.comment}</div>
                                <div style="font-size:0.8rem; color:#888; margin-top:5px;">${new Date(r.created_at).toLocaleDateString('vi-VN')}</div>
                            </div>
                        `).join('');
                    } else {
                        list.innerHTML = '<div style="text-align:center; padding:20px; color:#888;">Chưa có đánh giá nào.</div>';
                    }
                }
            } catch (e) { console.error(e); }
        }

        loadUserReviews();

    </script>

<!-- User Review Modal -->
<div class="modal-overlay" id="userReviewModal">
    <div class="modal-box" style="max-width:500px; width: 100%;">
        <div class="modal-header">
            <h3>Đánh giá người dùng</h3>
            <div class="close-btn" onclick="document.getElementById('userReviewModal').classList.remove('active')">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </div>
        <div class="modal-body">
            <div style="text-align:center; margin-bottom:20px;">
                <div class="user-star-rating" style="font-size:2rem; cursor:pointer;">
                    <i class="fa-regular fa-star" data-value="1"></i>
                    <i class="fa-regular fa-star" data-value="2"></i>
                    <i class="fa-regular fa-star" data-value="3"></i>
                    <i class="fa-regular fa-star" data-value="4"></i>
                    <i class="fa-regular fa-star" data-value="5"></i>
                </div>
                <input type="hidden" id="userRating" value="0">
            </div>
            <textarea id="userComment" class="form-control" placeholder="Nhập nội dung đánh giá..." style="min-height:100px; margin-bottom:15px; width: 100%; box-sizing: border-box;"></textarea>
            <button onclick="submitUserReview()" class="save-btn" style="width: 100%;">Gửi đánh giá</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Star Rating Logic
        const userStars = document.querySelectorAll('.user-star-rating i');
        const userRatingInput = document.getElementById('userRating');
        
        if(userStars.length > 0) {
            userStars.forEach(star => {
                star.addEventListener('click', () => {
                    const val = star.getAttribute('data-value');
                    userRatingInput.value = val;
                    
                    userStars.forEach(s => {
                        if (s.getAttribute('data-value') <= val) {
                            s.classList.remove('fa-regular');
                            s.classList.add('fa-solid');
                            s.style.color = '#f5c518';
                        } else {
                            s.classList.remove('fa-solid');
                            s.classList.add('fa-regular');
                            s.style.color = '#ccc';
                        }
                    });
                });
            });
        }
    });
</script>
</body>


</html>