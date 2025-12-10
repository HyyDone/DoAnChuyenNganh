<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/mail.php';
session_start();

ob_start(); // Buffer output to prevent stray HTML/Whitespace
header('Content-Type: application/json');
ini_set('display_errors', 0); // Hide errors from output
error_reporting(E_ALL); // Still log them

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_my_listings':
            // Fetch listings owned by user
            // We need to fetch PENDING requests specifically to show them distinctively
            // And CONFIRMED bookings to show tenant info
            $stmt = $pdo->prepare("
                SELECT l.*, 
                       (SELECT file_path FROM listing_images WHERE listing_id = l.id AND is_cover = 1 LIMIT 1) as cover_image,
                       b.id as booking_id, b.tenant_id, b.start_date, b.end_date, b.status as booking_status,
                       u.full_name as tenant_name, u.phone as tenant_phone, u.email as tenant_email
                FROM listings l
                LEFT JOIN bookings b ON l.id = b.listing_id AND b.status IN ('confirmed', 'pending')
                LEFT JOIN users u ON b.tenant_id = u.id
                WHERE l.owner_id = ?
                ORDER BY CASE WHEN b.status = 'pending' THEN 0 ELSE 1 END, l.created_at DESC
            ");
            $stmt->execute([$userId]);
            $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($listings);
            break;

        case 'get_my_rentals':
            // Fetch listings where user is the tenant
            $stmt = $pdo->prepare("
                SELECT l.*, 
                       (SELECT file_path FROM listing_images WHERE listing_id = l.id AND is_cover = 1 LIMIT 1) as cover_image,
                       b.status as booking_status, b.start_date, b.end_date,
                       u.full_name as owner_name, u.phone as owner_phone
                FROM bookings b
                JOIN listings l ON b.listing_id = l.id
                JOIN users u ON l.owner_id = u.id
                WHERE b.tenant_id = ? AND b.status IN ('confirmed', 'pending')
                ORDER BY b.created_at DESC
            ");
            $stmt->execute([$userId]);
            $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rentals);
            break;

        case 'confirm_booking':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $bookingId = $data['booking_id'] ?? 0;
            
            // Verify ownership of the listing associated with this booking
            // Verify ownership and status
            $check = $pdo->prepare("
                SELECT b.id, b.listing_id, b.status, l.owner_id
                FROM bookings b 
                JOIN listings l ON b.listing_id = l.id 
                WHERE b.id = ?
            ");
            $check->execute([$bookingId]);
            $booking = $check->fetch();
            
            if (!$booking) {
                throw new Exception('Booking not found');
            }
            if ($booking['owner_id'] != $userId) {
                throw new Exception('Permission denied');
            }
            if ($booking['status'] !== 'pending') {
                throw new Exception('Request has already been processed (Current status: ' . $booking['status'] . ')');
            }
            
            // Transaction
            $pdo->beginTransaction();
            
            // Update Booking Status
            $updB = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
            $updB->execute([$bookingId]);
            
            // Update Listing Status to 'booked' (hides it from public)
            $updL = $pdo->prepare("UPDATE listings SET status = 'booked' WHERE id = ?");
            $updL->execute([$booking['listing_id']]);
            
            // Reject other pending bookings for same listing? Optional but good practice.
            // For now let's keep it simple.
            
            $pdo->commit();

            // Notify Tenant
            $notifMsg = "Yêu cầu thuê phòng '" . $booking['title'] . "' của bạn đã được CHẤP NHẬN!";
            // Need tenant_id and title. Join already has title. fetch adds it.
            // Check select: SELECT b.id, b.listing_id ... (missing title, tenant_id)
            // Let's refetch with more info inside the case or modify the check query.
            // Modifying check query above is risky if we don't change fetch.
            // Safer to just query here.
            $infoStmt = $pdo->prepare("SELECT b.tenant_id, l.title FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE b.id = ?");
            $infoStmt->execute([$bookingId]);
            $info = $infoStmt->fetch();
            
            if ($info) {
                $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id, message) VALUES (?, 'booking_confirmed', ?, ?)");
                $nStmt->execute([$info['tenant_id'], $bookingId, "Yêu cầu thuê phòng '" . $info['title'] . "' của bạn đã được CHẤP NHẬN!"]);
            }

            echo json_encode(['success' => true]);
            break;

        case 'reject_booking':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $bookingId = $data['booking_id'] ?? 0;

            // Verify ownership
            $check = $pdo->prepare("
                SELECT b.id, b.listing_id 
                FROM bookings b 
                JOIN listings l ON b.listing_id = l.id 
                WHERE b.id = ? AND l.owner_id = ?
            ");
            $check->execute([$bookingId, $userId]);
            $checkStmt = $check->fetch();
            if (!$checkStmt) {
                throw new Exception('Permission denied');
            }

            $bookingListingId = $checkStmt['listing_id'];

            // Update booking status to cancelled
            $upd = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $upd->execute([$bookingId]);
            
            // Restore listing status to available (un-hide it)
            $updL = $pdo->prepare("UPDATE listings SET status = 'available' WHERE id = ?");
            $updL->execute([$bookingListingId]);
            
            // Notify Tenant
            $infoStmt = $pdo->prepare("SELECT b.tenant_id, l.title FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE b.id = ?");
            $infoStmt->execute([$bookingId]);
            $info = $infoStmt->fetch();
            
            if ($info) {
                $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id, message) VALUES (?, 'booking_rejected', ?, ?)");
                $nStmt->execute([$info['tenant_id'], $bookingId, "Yêu cầu thuê phòng '" . $info['title'] . "' của bạn đã bị TỪ CHỐI."]);
            }

            echo json_encode(['success' => true]);
            break;

        case 'delete_listing':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $listingId = $data['id'] ?? 0;

            // Verify ownership
            $check = $pdo->prepare("SELECT id FROM listings WHERE id = ? AND owner_id = ?");
            $check->execute([$listingId, $userId]);
            if (!$check->fetch()) {
                throw new Exception('Permission denied');
            }

            // Get images to delete files
            $imgStmt = $pdo->prepare("SELECT file_path FROM listing_images WHERE listing_id = ?");
            $imgStmt->execute([$listingId]);
            $images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

            // Delete from DB (Cascade should handle related tables, but files need manual deletion)
            $del = $pdo->prepare("DELETE FROM listings WHERE id = ?");
            $del->execute([$listingId]);

            // Delete files
            foreach ($images as $path) {
                $fullPath = __DIR__ . '/../../public/' . $path;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            echo json_encode(['success' => true]);
            break;

        case 'update_listing':
             if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $listingId = $data['id'] ?? 0;
            
             // Verify ownership
            $check = $pdo->prepare("SELECT id FROM listings WHERE id = ? AND owner_id = ?");
            $check->execute([$listingId, $userId]);
            if (!$check->fetch()) {
                throw new Exception('Permission denied');
            }
            
            $sql = "UPDATE listings SET title = ?, description = ?, price = ?, address = ?, city = ?, district = ?, room_type = ?, status = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['price'],
                $data['address'],
                $data['city'],
                $data['district'],
                $data['room_type'],
                $data['status'],
                $listingId
            ]);
            
            echo json_encode(['success' => true]);
            break;

        case 'stop_renting':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $listingId = $data['id'] ?? 0;

            // Verify ownership
            $check = $pdo->prepare("SELECT id, title FROM listings WHERE id = ? AND owner_id = ?");
            $check->execute([$listingId, $userId]);
            $listing = $check->fetch();
            
            if (!$listing) {
                throw new Exception('Permission denied or listing not found');
            }

            $pdo->beginTransaction();

            // 1. Fetch tenant info for notification (before deletion)
            $tenantStmt = $pdo->prepare("
                SELECT b.tenant_id
                FROM bookings b 
                WHERE b.listing_id = ? AND b.status = 'confirmed'
            ");
            $tenantStmt->execute([$listingId]);
            $tenant = $tenantStmt->fetch();

            // 1. Delete active bookings (confirmed or pending)
            $delB = $pdo->prepare("DELETE FROM bookings WHERE listing_id = ? AND status IN ('confirmed', 'pending')");
            $delB->execute([$listingId]);

            // Send system notification if tenant existed (No Email)
            if ($tenant) {
                $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id, message) VALUES (?, 'rental_stopped', ?, ?)");
                $nStmt->execute([
                    $tenant['tenant_id'], 
                    $listingId, 
                    "Chủ nhà đã dừng hợp đồng thuê phòng: '" . $listing['title'] . "'. Vui lòng liên hệ để biết thêm chi tiết."
                ]);
            }

            // 2. Set listing status to inactive (Stop Renting)
            // Or 'available' if just eviction? User said "nút dừng cho thuê", "không cho thuê nữa".
            // Suggests 'inactive'.
            $updL = $pdo->prepare("UPDATE listings SET status = 'inactive' WHERE id = ?");
            $updL->execute([$listingId]);
            
            $pdo->commit();
            
            echo json_encode(['success' => true]);
            break;

        case 'report_payment':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            
            $listingId = $_POST['listing_id'] ?? 0;
            $message = $_POST['message'] ?? '';
            
            if (!$listingId) throw new Exception('Missing ID');

            // Get Owner Email & Info
            $stmt = $pdo->prepare("
                SELECT l.title, u.email as owner_email, u.full_name as owner_name 
                FROM listings l 
                JOIN users u ON l.owner_id = u.id 
                WHERE l.id = ?
            ");
            $stmt->execute([$listingId]);
            $info = $stmt->fetch();
            
            if (!$info) throw new Exception('Listing not found');

            // Handle Image Upload
            $imagePath = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/payments/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fileName = 'pay_' . time() . '_' . uniqid() . '.' . $ext;
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $imagePath = $targetFile;
                }
            }

            // Get Tenant Info
            $uStmt = $pdo->prepare("SELECT full_name, phone FROM users WHERE id = ?");
            $uStmt->execute([$userId]);
            $tenant = $uStmt->fetch();

            // Send Email
            $subject = "[Thuê Trọ] Thông báo đóng tiền trọ - " . $info['title'];
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            
            $body = "
                <h3>Thông báo đóng tiền trọ</h3>
                <p><strong>Người gửi:</strong> {$tenant['full_name']} ({$tenant['phone']})</p>
                <p><strong>Phòng:</strong> {$info['title']}</p>
                <p><strong>Lời nhắn:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
            ";

            if ($imagePath) {
                $relPath = str_replace(__DIR__ . '/../../public', '', $imagePath);
                $publicImgUrl = "$protocol://$host$relPath";
                $body .= "<p><strong>Chuyển khoản/Chứng từ:</strong> <a href='$publicImgUrl'>Xem hình ảnh</a></p>";
                $body .= "<img src='$publicImgUrl' style='max-width:500px; border:1px solid #ddd; margin-top:10px;'>";
            } else {
                $body .= "<p><em>Không có hình ảnh đính kèm.</em></p>";
            }

            $sent = sendMail($info['owner_email'], $subject, $body);
            
            if ($sent) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send email']);
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    ob_end_clean(); // Clean any previous output (warnings/etc)
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => $e->getMessage()]);
}
