<?php
ini_set("display_errors", 1);
require_once __DIR__ . '/../config/db.php';
session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$appliance = null;

try {
    $stmt = $pdo->prepare("SELECT appliances.*, users.username, users.avatar, users.email, users.phone 
                           FROM appliances 
                           JOIN users ON appliances.user_id = users.id 
                           WHERE appliances.id = ?");
    $stmt->execute([$id]);
    $appliance = $stmt->fetch();
} catch (PDOException $e) {
}

if (!$appliance) {
    echo "Sản phẩm không tồn tại.";
    exit;
}

$suggestions = [];
try {
    $stmtSug = $pdo->prepare("SELECT * FROM appliances WHERE type = ? AND id != ? LIMIT 5");
    $stmtSug->execute([$appliance['type'], $id]);
    $suggestions = $stmtSug->fetchAll();
} catch (Exception $e) {}

$bookingSuccess = '';
$bookingError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_appliance') {
    if (!isset($_SESSION['user_id'])) {
        $bookingError = 'Vui lòng đăng nhập để thuê.';
    } else {
        $renterId = $_SESSION['user_id'];
        $address = $_POST['address'] ?? '';
        
        if (empty($address)) {
            $bookingError = 'Vui lòng nhập địa chỉ nhận hàng.';
        } else {
            try {
                $pdo->beginTransaction();
                
                $stmtBook = $pdo->prepare("INSERT INTO appliance_bookings (appliance_id, renter_id, address, status) VALUES (?, ?, ?, 'pending')");
                $stmtBook->execute([$id, $renterId, $address]);
                
                if ($appliance['user_id'] != $renterId) {
                    $msg = "Có người muốn thuê " . $appliance['name'];
                    $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, message, type, reference_id) VALUES (?, ?, 'appliance_request', ?)");
                    $stmtNotif->execute([$appliance['user_id'], $msg, $id]);
                }

                $pdo->commit();
                $bookingSuccess = 'Đã gửi yêu cầu thuê thành công! Chủ nhân sẽ liên hệ với bạn.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $bookingError = 'Lỗi xử lý: ' . $e->getMessage();
            }
        }
    }
}
$isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $appliance['user_id'];

?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($appliance['name']) ?> - Chi tiết thuê</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #f0f2f5;
            --card-bg: #ffffff;
            --primary-color: #0866ff;
            --text-color: #050505;
        }
        body { background-color: var(--bg-color); color: var(--text-color); font-family: 'Segoe UI', sans-serif; }
        
        .container {
            max-width: 1300px;
            margin: 20px auto;
            padding: 0 15px;
            display: grid;
            grid-template-columns: 250px 1fr 300px;
            gap: 24px;
        }

        @media (max-width: 900px) {
            .container { grid-template-columns: 1fr; }
        }

        .card {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .sugg-item {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            text-decoration: none;
            color: inherit;
        }
        .sugg-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            background: #eee;
        }
        .sugg-info h4 { margin: 0 0 5px 0; font-size: 14px; }
        .sugg-price { font-size: 13px; color: #e91e63; font-weight: bold; }

        .detail-img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 8px;
            background-color: #ddd;
            margin-bottom: 20px;
        }
        .detail-title { margin: 0 0 10px 0; font-size: 26px; }
        .detail-price { font-size: 22px; color: #e91e63; font-weight: bold; margin-bottom: 15px; }
        .detail-props {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
        }
        .prop-item label { display: block; font-size: 13px; color: #666; }
        .prop-item span { font-weight: 500; font-size: 15px; }

        .owner-box {
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .owner-avatar { width: 50px; height: 50px; border-radius: 50%; }
        
        .booking-form .form-group { margin-bottom: 15px; }
        .booking-form label { display: block; margin-bottom: 5px; font-weight: 600; }
        .booking-form input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing:border-box; }
        
        .btn-book {
             width: 100%;
             background: #e91e63;
             color: white;
             border: none;
             padding: 12px;
             border-radius: 6px;
             font-weight: bold;
             font-size: 16px;
             cursor: pointer;
        }
        .btn-book:hover { opacity: 0.9; }
        
        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }

    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <aside>
        <div class="card">
            <h3 style="font-size: 16px; margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Gợi ý tương tự</h3>
            <?php foreach ($suggestions as $sug): 
                 $simg = !empty($sug['image_path']) ? $sug['image_path'] : 'https://placehold.co/100x100?text=.';
            ?>
            <a href="/appliance_detail.php?id=<?= $sug['id'] ?>" class="sugg-item">
                <img src="<?= htmlspecialchars($simg) ?>" class="sugg-thumb" alt="">
                <div class="sugg-info">
                    <h4><?= htmlspecialchars($sug['name']) ?></h4>
                    <div class="sugg-price"><?= number_format($sug['price']) ?> đ</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <main>
        <div class="card">
            <?php 
                $img = !empty($appliance['image_path']) ? $appliance['image_path'] : 'https://placehold.co/800x400?text=' . urlencode($appliance['name']);
            ?>
            <img src="<?= htmlspecialchars($img) ?>" class="detail-img" alt="">
            
            <h1 class="detail-title"><?= htmlspecialchars($appliance['name']) ?></h1>
            <div class="detail-price"><?= number_format($appliance['price']) ?> đ/tháng</div>
            
            <div class="detail-props">
                <div class="prop-item">
                    <label>Loại</label>
                    <span><?= htmlspecialchars($appliance['type']) ?></span>
                </div>
                <div class="prop-item">
                    <label>Tình trạng</label>
                    <span><?= $appliance['quantity'] > 0 ? 'Sẵn sàng' : 'Hết hàng' ?></span>
                </div>
                <div class="prop-item">
                    <label>Khu vực</label>
                    <span><?= htmlspecialchars($appliance['city']) ?></span>
                </div>
                <div class="prop-item">
                    <label>Số lượng kho</label>
                    <span><?= $appliance['quantity'] ?></span>
                </div>
            </div>

            <div style="line-height: 1.6;">
                <h3 style="font-size: 18px;">Mô tả chi tiết</h3>
                <?= nl2br(htmlspecialchars($appliance['description'])) ?>
            </div>
        </div>
    </main>

    <aside>
        <div class="card">
            <div class="owner-box">
                <img src="<?= htmlspecialchars(!empty($appliance['avatar']) ? $appliance['avatar'] : '/assets/default-avatar.png') ?>" class="owner-avatar" alt="">
                <div>
                    <div style="font-weight: bold;"><?= htmlspecialchars($appliance['username']) ?></div>
                    <div style="font-size: 13px; color: #666;">Chủ sở hữu</div>
                </div>
            </div>
            
            <?php if ($bookingSuccess): ?>
                <div class="alert alert-success"><?= $bookingSuccess ?></div>
            <?php endif; ?>
            <?php if ($bookingError): ?>
                <div class="alert alert-error"><?= $bookingError ?></div>
            <?php endif; ?>

            <?php if (!$isOwner && $appliance['quantity'] > 0): ?>
            <form class="booking-form" method="POST">
                <input type="hidden" name="action" value="book_appliance">
                
                <h3 style="font-size: 16px; margin-top:0;">Thuê đồ ngay</h3>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="form-group">
                        <label>Thông tin người thuê</label>
                        <div style="background: #f0f2f5; padding: 10px; border-radius: 6px; font-size: 14px;">
                            <?php 
                                $renter = $pdo->prepare("SELECT full_name, phone FROM users WHERE id = ?");
                                $renter->execute([$_SESSION['user_id']]);
                                $rInfo = $renter->fetch();
                                echo htmlspecialchars($rInfo['full_name'] ?? 'Tôi');
                                echo ' - ' . htmlspecialchars($rInfo['phone'] ?? 'Chưa sđt');
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Địa chỉ nhận hàng (*)</label>
                    <input type="text" name="address" required placeholder="Nhập địa chỉ của bạn...">
                </div>

                <button type="submit" class="btn-book">Yêu cầu thuê</button>
                <p style="font-size: 12px; color: #666; margin-top: 10px; text-align: center;">Chủ sở hữu sẽ nhận được thông báo và duyệt yêu cầu của bạn.</p>
            </form>
            <?php elseif ($appliance['quantity'] <= 0): ?>
                <button disabled style="width:100%; padding: 12px; background: #ccc; border:none; border-radius:6px; font-weight:bold; color: #666;">Tạm hết hàng</button>

            <?php else: ?>
                <div style="text-align:center; padding: 10px; background: #f0f2f5; border-radius: 6px;">Đây là sản phẩm của bạn</div>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php if (file_exists(__DIR__ . '/includes/footer.php')) include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
