<?php
ini_set("display_errors", 1);
require_once __DIR__ . '/../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';
$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_appliance') {
        $id = $_POST['appliance_id'];
        $name = $_POST['name'];
        $price = $_POST['price'];
        $quantity = $_POST['quantity'];
        $desc = $_POST['description'];
        $city = $_POST['city'];
        $type = $_POST['type'];
        
        // Image Upload (Simplified update)
        $imagePath = $_POST['current_image'] ?? null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
             $uploadDir = __DIR__ . '/uploads/appliances/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $newFileName = uniqid('app_') . '.' . $fileExt;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFileName)) {
                $imagePath = '/uploads/appliances/' . $newFileName;
            }
        }

        try {
            $stmt = $pdo->prepare("UPDATE appliances SET name=?, price=?, quantity=?, description=?, city=?, type=?, image_path=? WHERE id=? AND user_id=?");
            $stmt->execute([$name, $price, $quantity, $desc, $city, $type, $imagePath, $id, $userId]);
            $successMsg = 'Cập nhật thành công!';
            $selectedId = $id; // Keep selected
        } catch (Exception $e) { $errorMsg = 'Lỗi cập nhật: ' . $e->getMessage(); }
        
    } elseif ($action === 'delete_appliance') {
        $id = $_POST['appliance_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM appliances WHERE id=? AND user_id=?");
            $stmt->execute([$id, $userId]);
            $successMsg = 'Đã xóa vật dụng.';
            $selectedId = 0; // Reset selection
        } catch (Exception $e) { $errorMsg = 'Lỗi xóa: ' . $e->getMessage(); }
        
    } elseif ($action === 'approve_booking' || $action === 'reject_booking') {
        $bookingId = $_POST['booking_id'];
        $newStatus = ($action === 'approve_booking') ? 'approved' : 'rejected';
        
        try {
            // Verify ownership via booking -> appliance -> user
            $stmtCheck = $pdo->prepare("SELECT b.id, b.renter_id, b.appliance_id, a.name 
                                        FROM appliance_bookings b 
                                        JOIN appliances a ON b.appliance_id = a.id 
                                        WHERE b.id = ? AND a.user_id = ?");
            $stmtCheck->execute([$bookingId, $userId]);
            $booking = $stmtCheck->fetch();
            
            if ($booking) {
                // Update Status
                $stmtUpd = $pdo->prepare("UPDATE appliance_bookings SET status = ? WHERE id = ?");
                $stmtUpd->execute([$newStatus, $bookingId]);
                
                // If Approved, decrease quantity? (Optional logic, let's keep simple for now or decrement)
                if ($newStatus === 'approved') {
                    $pdo->prepare("UPDATE appliances SET quantity = quantity - 1 WHERE id = ?")->execute([$booking['appliance_id']]);
                }

                // Send Notification to Renter
                $notifType = ($newStatus === 'approved') ? 'appliance_approved' : 'appliance_rejected';
                $msg = "Yêu cầu thuê '" . $booking['name'] . "' của bạn đã bị " . ($newStatus == 'approved' ? 'chấp nhận' : 'từ chối');
                
                $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, message, type, reference_id) VALUES (?, ?, ?, ?)");
                $stmtNotif->execute([$booking['renter_id'], $msg, $notifType, $booking['appliance_id']]);
                
                $successMsg = 'Đã xử lý yêu cầu.';
                 $selectedId = $booking['appliance_id']; // Keep context
            }
        } catch (Exception $e) { $errorMsg = 'Lỗi xử lý: ' . $e->getMessage(); }
    }
}

// Fetch User's Appliances
$stmtList = $pdo->prepare("SELECT * FROM appliances WHERE user_id = ? ORDER BY created_at DESC");
$stmtList->execute([$userId]);
$myAppliances = $stmtList->fetchAll();

// If no ID selected but items exist, select first
if ($selectedId == 0 && count($myAppliances) > 0) {
    $selectedId = $myAppliances[0]['id'];
}

// Get Selected Item Details
$selectedItem = null;
$bookings = [];
if ($selectedId > 0) {
    foreach ($myAppliances as $app) {
        if ($app['id'] == $selectedId) {
            $selectedItem = $app;
            break;
        }
    }
    
    // Get Bookings for this item
    if ($selectedItem) {
        $stmtBook = $pdo->prepare("SELECT b.*, u.full_name, u.phone, u.avatar 
                                   FROM appliance_bookings b 
                                   JOIN users u ON b.renter_id = u.id 
                                   WHERE b.appliance_id = ? ORDER BY b.created_at DESC");
        $stmtBook->execute([$selectedId]);
        $bookings = $stmtBook->fetchAll();
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Quản lý đồ gia dụng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #f0f2f5;
            --card-bg: #ffffff;
            --primary-color: #0866ff;
        }
        body { 
            background-color: var(--bg-color); 
            font-family: 'Segoe UI', sans-serif; 
            margin: 0 !important;
            padding: 0 !important;
        }
        header {
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
        }
        
        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 15px;
            display: grid;
            grid-template-columns: 350px 1fr 350px; /* Left (Edit), Center (List), Right (Requests) */
            gap: 20px;
            align-items: start;
        }
        
        @media (max-width: 1000px) {
            .container { grid-template-columns: 1fr; }
            .col-left { order: 2; }
            .col-center { order: 1; }
            .col-right { order: 3; }
        }

        .card {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        /* Center List */
        .list-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
        }
        .list-item:hover { background: #f9f9f9; }
        .list-item.active { background: #e7f3ff; border-left: 4px solid var(--primary-color); }
        .list-thumb { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; background: #eee; }
        
        /* Forms */
        .form-group { margin-bottom: 12px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        .form-input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing:border-box; }
        .btn { padding: 8px 15px; border-radius: 4px; border: none; cursor: pointer; font-weight: 600; font-size: 14px; }
        .btn-primary { background: var(--primary-color); color: white; width: 100%; }
        .btn-danger { background: #dc3545; color: white; width: 100%; margin-top: 10px; }
        
        /* Requests */
        .req-item {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
            border: 1px solid #eee;
        }
        .req-head { display: flex; align-items: center; gap: 10px; margin-bottom: 5px; }
        .req-avatar { width: 30px; height: 30px; border-radius: 50%; }
        .req-actions { display: flex; gap: 5px; margin-top: 10px; }
        .btn-sm { padding: 5px 10px; font-size: 12px; flex: 1; }
        
        .status-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
        }
        .st-pending { background: #fff3cd; color: #856404; }
        .st-approved { background: #d4edda; color: #155724; }
        .st-rejected { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <!-- Left Column: Edit Info -->
    <div class="col-left">
        <div class="card">
            <?php if ($selectedItem): ?>
                <h3 style="margin-top:0;">Chỉnh sửa thông tin</h3>
                
                <?php if ($successMsg): ?><div style="color:green; margin-bottom:10px;"><?= $successMsg ?></div><?php endif; ?>
                <?php if ($errorMsg): ?><div style="color:red; margin-bottom:10px;"><?= $errorMsg ?></div><?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_appliance">
                    <input type="hidden" name="appliance_id" value="<?= $selectedItem['id'] ?>">
                    
                    <div style="text-align: center; margin-bottom: 15px;">
                         <img src="<?= htmlspecialchars(!empty($selectedItem['image_path']) ? $selectedItem['image_path'] : 'https://placehold.co/100x100') ?>" style="width:100px; height:100px; object-fit:cover; border-radius:4px;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tên</label>
                        <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($selectedItem['name']) ?>">
                    </div>
                    
                    <div style="display:flex; gap:10px;">
                        <div class="form-group" style="flex:1;">
                            <label class="form-label">Giá</label>
                            <input type="number" name="price" class="form-input" value="<?= $selectedItem['price'] ?>">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label class="form-label">Số lượng</label>
                            <input type="number" name="quantity" class="form-input" value="<?= $selectedItem['quantity'] ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Loại</label>
                         <select name="type" class="form-input">
                            <?php foreach(['Tủ lạnh','Máy giặt','Quạt','Bếp Gas','Khác'] as $t): ?>
                                <option value="<?= $t ?>" <?= $selectedItem['type'] == $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Khu vực</label>
                         <select name="city" class="form-input">
                            <?php foreach(['Ho Chi Minh','Ha Noi','Da Nang'] as $c): ?>
                                <option value="<?= $c ?>" <?= $selectedItem['city'] == $c ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Hình ảnh mới (nếu thay đổi)</label>
                        <input type="file" name="image" class="form-input">
                        <input type="hidden" name="current_image" value="<?= htmlspecialchars($selectedItem['image_path'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-input" rows="3"><?= htmlspecialchars($selectedItem['description']) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </form>

                <form method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa không?');">
                    <input type="hidden" name="action" value="delete_appliance">
                    <input type="hidden" name="appliance_id" value="<?= $selectedItem['id'] ?>">
                    <button type="submit" class="btn btn-danger">Xóa vật dụng</button>
                </form>
            <?php else: ?>
                <p style="text-align:center; color:#666;">Chọn một vật dụng để chỉnh sửa.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Center Column: My List -->
    <div class="col-center">
        <div class="card">
            <h2 style="margin-top:0; font-size:20px;">Danh sách của tôi</h2>
            <?php if (count($myAppliances) > 0): ?>
                <?php foreach ($myAppliances as $item): ?>
                    <div onclick="window.location.href='?id=<?= $item['id'] ?>'" class="list-item <?= $item['id'] == $selectedId ? 'active' : '' ?>">
                        <img src="<?= htmlspecialchars(!empty($item['image_path']) ? $item['image_path'] : 'https://placehold.co/800x800') ?>" class="list-thumb">
                        <div>
                            <h4 style="margin:0 0 5px 0;"><?= htmlspecialchars($item['name']) ?></h4>
                            <div style="font-size:13px; color:#666;"><?= number_format($item['price']) ?> đ/tháng</div>
                            <div style="font-size:13px; color:#666;">SL: <?= $item['quantity'] ?> • <?= $item['city'] ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Bạn chưa đăng tin nào.</p>
                <a href="/appliances.php" style="display:block; text-align:center; margin-top:10px;">Đăng tin ngay</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Requests -->
    <div class="col-right">
        <div class="card">
            <h3 style="margin-top:0;">Yêu cầu thuê</h3>
            <?php if ($selectedItem): ?>
                <?php if (count($bookings) > 0): ?>
                    <?php foreach ($bookings as $req): ?>
                        <div class="req-item">
                            <div class="req-head">
                                <img src="<?= htmlspecialchars(!empty($req['avatar']) ? $req['avatar'] : '/assets/default-avatar.png') ?>" class="req-avatar">
                                <div>
                                    <div style="font-weight:600; font-size:14px;"><?= htmlspecialchars($req['full_name']) ?></div>
                                    <div style="font-size:11px; color:#666;"><?= date('d/m H:i', strtotime($req['created_at'])) ?></div>
                                </div>
                                <div style="margin-left:auto;">
                                    <span class="status-badge st-<?= $req['status'] ?>"><?= $req['status'] ?></span>
                                </div>
                            </div>
                            <div style="font-size:13px; margin-bottom:5px;">
                                <strong>SĐT:</strong> <?= htmlspecialchars($req['phone']) ?><br>
                                <strong>Đ/c:</strong> <?= htmlspecialchars($req['address']) ?>
                            </div>
                            
                            <?php if ($req['status'] === 'pending'): ?>
                            <div class="req-actions">
                                <form method="POST" style="flex:1;">
                                    <input type="hidden" name="action" value="approve_booking">
                                    <input type="hidden" name="booking_id" value="<?= $req['id'] ?>">
                                    <button class="btn btn-sm" style="background:#42b72a; color:white; border:none; border-radius:4px; width:100%; cursor:pointer;">Xác nhận</button>
                                </form>
                                <form method="POST" style="flex:1;">
                                    <input type="hidden" name="action" value="reject_booking">
                                    <input type="hidden" name="booking_id" value="<?= $req['id'] ?>">
                                    <button class="btn btn-sm" style="background:#dc3545; color:white; border:none; border-radius:4px; width:100%; cursor:pointer;">Từ chối</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; color:#666; font-size:14px;">Chưa có yêu cầu nào cho vật dụng này.</p>
                <?php endif; ?>
            <?php else: ?>
                <p style="text-align:center; color:#666;">Chọn vật dụng để xem yêu cầu.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (file_exists(__DIR__ . '/includes/footer.php')) include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
