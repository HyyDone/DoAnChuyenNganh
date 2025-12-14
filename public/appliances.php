<?php
ini_set("display_errors", 1);
require_once __DIR__ . '/../config/db.php';
session_start();

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_appliance') {
    if (!isset($_SESSION['user_id'])) {
        $errorMsg = 'Vui lòng đăng nhập để đăng tin.';
    } else {
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? 'Khác';
        $price = $_POST['price'] ?? 0;
        $quantity = $_POST['quantity'] ?? 1;
        $description = $_POST['description'] ?? '';
        $city = $_POST['city'] ?? 'Ho Chi Minh';

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/appliances/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $newFileName = uniqid('app_') . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
                $imagePath = '/uploads/appliances/' . $newFileName;
            }
        }

        if (empty($name) || $price < 0 || $quantity < 0) {
            $errorMsg = 'Vui lòng nhập đầy đủ thông tin hợp lệ.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO appliances (user_id, name, type, price, quantity, description, city, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $name, $type, $price, $quantity, $description, $city, $imagePath]);
                $successMsg = 'Đăng tin thành công!';
            } catch (PDOException $e) {
                $errorMsg = 'Lỗi DB: ' . $e->getMessage();
            }
        }
    }
}

$filterCity = $_GET['city'] ?? 'all';
$sql = "SELECT appliances.*, users.username, users.avatar FROM appliances JOIN users ON appliances.user_id = users.id";
$params = [];

if ($filterCity !== 'all' && !empty($filterCity)) {
    $sql .= " WHERE appliances.city = ?";
    $params[] = $filterCity;
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmtList = $pdo->prepare($sql);
    $stmtList->execute($params);
    $appliances = $stmtList->fetchAll();
} catch (PDOException $e) {
    $appliances = [];
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Thuê Đồ Gia Dụng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #f0f2f5;
            --card-bg: #ffffff;
            --primary-color: #0866ff;
            --text-color: #050505;
            --secondary-text: #65676b;
        }
        body {
             font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            margin: 0 !important;
            padding: 0 !important;
            color: var(--text-color);
            width: 100%;
        }
        header {
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 24px;
        }
        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; }
            .sidebar { order: 1; position: static !important; }
            .main-content { order: 2; }
        }

        aside {
            position: sticky;
            top: 20px;
             height: fit-content;
        }
        .form-card {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .form-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced0d4;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: inherit;
        }
        .btn-submit {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-submit:hover { background: #0056b3; }

        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }

        .filter-bar {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .appliance-item {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            gap: 20px;
        }
        .appliance-thumb {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 6px;
            background: #eee;
        }
        .appliance-content { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }

        .appliance-info h3 { margin: 0 0 5px 0; font-size: 18px; }
        .appliance-meta { color: var(--secondary-text); font-size: 14px; display: flex; gap: 15px; align-items: center; margin-bottom: 8px; }
        .price-tag { color: #e91e63; font-weight: bold; font-size: 16px; }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .status-available { background: #e7f3ff; color: #1877f2; }
        .status-out { background: #f0f2f5; color: #65676b; }
        
        .btn-detail {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            align-self: flex-start;
        }

        @media (max-width: 600px) {
            .appliance-item { flex-direction: column; }
            .appliance-thumb { width: 100%; height: 200px; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <aside class="sidebar">
        <div class="form-card">
            <h3 class="form-title">Đăng tin cho thuê</h3>
            
            <?php if ($successMsg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create_appliance">
                
                <div class="form-group">
                    <label class="form-label">Tên đồ gia dụng</label>
                    <input type="text" name="name" class="form-input" placeholder="VD: Máy giặt LG..." required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Hình ảnh</label>
                    <input type="file" name="image" class="form-input" accept="image/*">
                </div>

                <div class="form-group">
                    <label class="form-label">Khu vực</label>
                    <select name="city" class="form-select">
                        <option value="Ho Chi Minh">Hồ Chí Minh</option>
                        <option value="Ha Noi">Hà Nội</option>
                        <option value="Da Nang">Đà Nẵng</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Loại đồ</label>
                    <select name="type" class="form-select">
                        <option value="Tủ lạnh">Tủ lạnh</option>
                        <option value="Máy giặt">Máy giặt</option>
                        <option value="Quạt">Quạt</option>
                        <option value="Bếp Gas">Bếp Gas</option>
                        <option value="Khác">Khác</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Giá thuê / tháng (VNĐ)</label>
                    <input type="number" name="price" class="form-input" required min="0">
                </div>

                <div class="form-group">
                    <label class="form-label">Số lượng</label>
                    <input type="number" name="quantity" class="form-input" value="1" required min="1">
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả chi tiết</label>
                    <textarea name="description" class="form-textarea" rows="3"></textarea>
                </div>

                <button type="submit" class="btn-submit">Đăng tin</button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <div class="filter-bar">
            <span><i class="fa-solid fa-filter"></i> Lọc theo:</span>
            <select onchange="window.location.href='?city='+this.value" class="form-select" style="width: 200px; margin: 0;">
                <option value="all">Tất cả thành phố</option>
                <option value="Ho Chi Minh" <?= $filterCity == 'Ho Chi Minh' ? 'selected' : '' ?>>Hồ Chí Minh</option>
                <option value="Ha Noi" <?= $filterCity == 'Ha Noi' ? 'selected' : '' ?>>Hà Nội</option>
                <option value="Da Nang" <?= $filterCity == 'Da Nang' ? 'selected' : '' ?>>Đà Nẵng</option>
            </select>
        </div>

        <?php if (count($appliances) > 0): ?>
            <?php foreach ($appliances as $item): ?>
                <?php 
                    $isAvailable = $item['quantity'] > 0 && $item['status'] === 'available';
                    $statusText = $isAvailable ? 'Sẵn sàng' : 'Hết hàng';
                    $statusClass = $isAvailable ? 'status-available' : 'status-out';
                    $img = !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : 'https://placehold.co/150x150?text=' . urlencode($item['type']);
                    $cityMap = ['Ho Chi Minh' => 'TP.HCM', 'Ha Noi' => 'Hà Nội', 'Da Nang' => 'Đà Nẵng'];
                    $cityLabel = $cityMap[$item['city']] ?? $item['city'];
                ?>
                <div class="appliance-item">
                     <img src="<?= $img ?>" class="appliance-thumb" alt="">
                    <div class="appliance-content">
                        <div class="appliance-info">
                            <div style="display:flex; justify-content:space-between;">
                                <h3><?= htmlspecialchars($item['name']) ?></h3>
                                <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                            </div>
                            <div class="appliance-meta">
                                <span class="price-tag"><?= number_format($item['price']) ?> đ/tháng</span>
                                <span><i class="fa-solid fa-layer-group"></i> <?= htmlspecialchars($item['type']) ?></span>
                                <span>• SL: <?= $item['quantity'] ?></span>
                                <span><i class="fa-solid fa-location-dot"></i> <?= $cityLabel ?></span>
                            </div>
                            <div style="margin-top: 5px; color: #555; font-size: 14px; margin-bottom: 10px;">
                                <?= nl2br(htmlspecialchars(substr($item['description'], 0, 150))) . (strlen($item['description']) > 150 ? '...' : '') ?>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                             <div style="font-size: 13px; color: #65676b; display: flex; align-items: center; gap: 5px;">
                                <img src="<?= htmlspecialchars(!empty($item['avatar']) ? $item['avatar'] : '/assets/default-avatar.png') ?>" style="width:24px;height:24px;border-radius:50%;" alt="">
                                <span><?= htmlspecialchars($item['username'] ?? 'Ẩn danh') ?></span>
                            </div>
                            <a href="/appliance_detail.php?id=<?= $item['id'] ?>" class="btn-detail">Chi tiết</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="background: white; padding: 40px; text-align: center; border-radius: 8px;">
                <p>Chưa có đồ gia dụng nào được đăng trong khu vực này.</p>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php if (file_exists(__DIR__ . '/includes/footer.php')) include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
