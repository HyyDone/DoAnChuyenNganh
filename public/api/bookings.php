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
    // Check if listing exists and get owner info for email
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

    // Check availability (simple check: is it already booked in that range? or just is status available?)
    // For now user requested a simple flow: "khi ấn thuê trọ sẽ gửi thư".
    // We will insert 'pending' booking.

    $insert = $pdo->prepare("
        INSERT INTO bookings (listing_id, tenant_id, start_date, end_date, status) 
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $insert->execute([$listingId, $tenantId, $startDate, $endDate]);

    // Send Email
    $currentUserStmt = $pdo->prepare("SELECT full_name, phone, email FROM users WHERE id = ?");
    $currentUserStmt->execute([$tenantId]);
    $tenant = $currentUserStmt->fetch();

    $to = $listing['owner_email'];
    $subject = "Yêu cầu thuê phòng mới: " . $listing['title'];
    $message = "Xin chào " . $listing['owner_name'] . ",\n\n";
    $message .= "Bạn vừa nhận được một yêu cầu thuê phòng cho bài đăng: " . $listing['title'] . ".\n\n";
    $message .= "--- Thông tin người thuê ---\n";
    $message .= "Họ tên: " . ($tenant['full_name'] ?: 'Người dùng ẩn danh') . "\n";
    $message .= "Số điện thoại: " . ($tenant['phone'] ?: 'Chưa cập nhật') . "\n";
    $message .= "Email: " . $tenant['email'] . "\n";
    $message .= "Ngày bắt đầu dự kiến: " . ($startDate ?: 'Chưa xác định') . "\n\n";
    $message .= "Vui lòng truy cập trang Quản lý cho thuê để Xác nhận hoặc Từ chối yêu cầu này.\n";
    $message .= "Link: http://" . $_SERVER['HTTP_HOST'] . "/manage_rentals.php\n";
    
    $headers = "From: no-reply@thuetro.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Attempt to send email (suppress errors if local server not configured)
    @mail($to, $subject, $message, $headers);

    echo json_encode(['success' => true, 'message' => 'Yêu cầu của bạn đã được gửi thành công!']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
