<?php
require_once __DIR__ . '/../../config/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$listingId = $data['listing_id'] ?? null;
$tenantId = $_SESSION['user_id'];
$startDate = $data['start_date'] ?? null;
$endDate = $data['end_date'] ?? null;

if (!$listingId) {
    echo json_encode(['error' => 'Missing listing ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT l.id, l.title, u.email as owner_email, u.full_name as owner_name 
        FROM listings l 
        JOIN users u ON l.owner_id = u.id 
        WHERE l.id = ?
    ");
    $stmt->execute([$listingId]);
    $listing = $stmt->fetch();

    if (!$listing) {
        throw new Exception('Listing not found');
    }

    $insert = $pdo->prepare("
        INSERT INTO bookings (listing_id, tenant_id, start_date, end_date, status) 
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $insert->execute([$listingId, $tenantId, $startDate, $endDate]);
    $bookingId = $pdo->lastInsertId();

    $notifMsg = "Bạn có yêu cầu thuê mới cho: " . $listing['title'];
    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id, message) VALUES (?, 'request_booking', ?, ?)");
    
    $stmtOwner = $pdo->prepare("SELECT owner_id FROM listings WHERE id = ?");
    $stmtOwner->execute([$listingId]);
    $ownerId = $stmtOwner->fetchColumn();
    
    $notifStmt->execute([$ownerId, $bookingId, $notifMsg]);

    require_once __DIR__ . '/../../helpers/mail.php';

    $currentUserStmt = $pdo->prepare("SELECT full_name, phone, email FROM users WHERE id = ?");
    $currentUserStmt->execute([$tenantId]);
    $tenant = $currentUserStmt->fetch();

    $to = $listing['owner_email'];
    $subject = "Yêu cầu thuê phòng mới: " . $listing['title'];
    
    $body = "
    <h3>Xin chào {$listing['owner_name']},</h3>
    <p>Bạn vừa nhận được một yêu cầu thuê phòng mới cho bài đăng: <strong>{$listing['title']}</strong></p>
    
    <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #0866ff; margin: 10px 0;'>
        <h4>Thông tin người thuê:</h4>
        <p><strong>Họ tên:</strong> " . ($tenant['full_name'] ?: 'Người dùng ẩn danh') . "</p>
        <p><strong>Số điện thoại:</strong> " . ($tenant['phone'] ?: 'Chưa cập nhật') . "</p>
        <p><strong>Email:</strong> {$tenant['email']}</p>
        <p><strong>Ngày bắt đầu thuê (dự kiến):</strong> " . ($startDate ?: 'Chưa xác định') . "</p>
    </div>

    <p>Vui lòng truy cập trang <a href='http://{$_SERVER['HTTP_HOST']}/manage_rentals.php'>Quản lý cho thuê</a> để Xác nhận hoặc Từ chối yêu cầu này.</p>
    <p>Trân trọng,<br>Đội ngũ Thuê Trọ</p>
    ";

    $mailSent = sendMail($to, $subject, $body);

    if ($mailSent) {
        echo json_encode(['success' => true, 'message' => 'Yêu cầu thuê đã được gửi! Chủ nhà sẽ nhận được email thông báo.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Đã gửi yêu cầu đặt phòng (Lỗi gửi email thông báo).']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
