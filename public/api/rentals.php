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
                SELECT l.*, b.id as booking_id,
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

        case 'report_damage':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $bookingId = $data['booking_id'] ?? 0;
            $title = $data['title'] ?? '';
            $desc = $data['description'] ?? '';
            $cost = $data['cost'] ?? 0;

            if (!$bookingId || !$title || !$cost) {
                throw new Exception('Missing required fields');
            }

            // Verify tenant ownership
            $stmt = $pdo->prepare("
                SELECT b.id, b.listing_id, l.owner_id, l.title as listing_title, u.full_name as tenant_name 
                FROM bookings b
                JOIN listings l ON b.listing_id = l.id
                JOIN users u ON b.tenant_id = u.id
                WHERE b.id = ? AND b.tenant_id = ?
            ");
            $stmt->execute([$bookingId, $userId]);
            $info = $stmt->fetch();

            if (!$info) {
                throw new Exception('Booking not found or permission denied');
            }

            // Insert Report
            $ins = $pdo->prepare("INSERT INTO damage_reports (booking_id, reporter_id, title, description, cost, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $ins->execute([$bookingId, $userId, $title, $desc, $cost]);
            $reportId = $pdo->lastInsertId();

            // Notify Owner
            $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id, message) VALUES (?, 'damage_report', ?, ?)");
            $msg = "Người thuê {$info['tenant_name']} báo cáo hư hại tại '{$info['listing_title']}': $title. Phí dự kiến: " . number_format($cost) . "đ";
            $nStmt->execute([$info['owner_id'], $reportId, $msg]);

            echo json_encode(['success' => true]);
            break;

        case 'get_damage_report':
            $reportId = $_GET['id'] ?? 0;
            
            // Verify access (Reporter OR Owner)
            // Join through bookings -> listings to get owner_id
            $stmt = $pdo->prepare("
                SELECT dr.*, b.tenant_id, l.owner_id
                FROM damage_reports dr
                JOIN bookings b ON dr.booking_id = b.id
                JOIN listings l ON b.listing_id = l.id
                WHERE dr.id = ?
            ");
            $stmt->execute([$reportId]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                throw new Exception('Report not found');
            }

            // Check permission
            if ($report['tenant_id'] != $userId && $report['owner_id'] != $userId) {
                throw new Exception('Permission denied');
            }
            
            echo json_encode($report);
            break;

        case 'confirm_damage_report':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $reportId = $data['report_id'] ?? 0;

            // Get Report & Verify Owner
            // Report -> Booking -> Listing -> Owner
            $stmt = $pdo->prepare("
                SELECT dr.*, b.tenant_id, l.owner_id, l.title as listing_title
                FROM damage_reports dr
                JOIN bookings b ON dr.booking_id = b.id
                JOIN listings l ON b.listing_id = l.id
                WHERE dr.id = ?
            ");
            $stmt->execute([$reportId]);
            $report = $stmt->fetch();

            if (!$report) {
                throw new Exception('Report not found');
            }

            if ($report['owner_id'] != $userId) {
                throw new Exception('Permission denied (Only owner can confirm)');
            }

            if ($report['status'] !== 'pending') {
                throw new Exception('Report already processed');
            }

            // Update Status
            $upd = $pdo->prepare("UPDATE damage_reports SET status = 'confirmed' WHERE id = ?");
            $upd->execute([$reportId]);

            // Notify Tenant
            $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id, message) VALUES (?, 'damage_confirmed', ?, ?)");
            $msg = "Chủ nhà đã XÁC NHẬN báo cáo hư hại '{$report['title']}' tại '{$report['listing_title']}'.";
            $nStmt->execute([$report['tenant_id'], $reportId, $msg]);

            echo json_encode(['success' => true]);
            break;

            echo json_encode(['success' => true]);
            break;

        // --- CONTRACT ACTIONS ---

        case 'get_contracts':
            // Fetch contracts/bookings for owner or tenant
            // For owner: Get all confirmed bookings for their listings
            // For tenant: Get all confirmed bookings where they are tenant
            
            $sql = "
                SELECT b.id as booking_id, b.start_date, b.end_date,
                       l.title, l.price, l.address, l.district, l.city,
                       u_tenant.full_name as tenant_name, u_owner.full_name as owner_name,
                       c.id as contract_id, c.status as contract_status, c.created_at as contract_created_at
                FROM bookings b
                JOIN listings l ON b.listing_id = l.id
                JOIN users u_tenant ON b.tenant_id = u_tenant.id
                JOIN users u_owner ON l.owner_id = u_owner.id
                LEFT JOIN contracts c ON b.id = c.booking_id
                WHERE b.status = 'confirmed' AND (l.owner_id = ? OR b.tenant_id = ?)
                ORDER BY b.created_at DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rows);
            break;

        case 'create_contract':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $bookingId = $data['booking_id'] ?? 0;
            $content = $data['content'] ?? '';

            if (!$bookingId || !$content) throw new Exception('Missing data');

            // Verify Owner
            $check = $pdo->prepare("
                SELECT l.owner_id, u.id as tenant_id, l.title
                FROM bookings b
                JOIN listings l ON b.listing_id = l.id
                JOIN users u ON b.tenant_id = u.id
                WHERE b.id = ?
            ");
            $check->execute([$bookingId]);
            $info = $check->fetch();

            if (!$info || $info['owner_id'] != $userId) {
                throw new Exception('Permission denied');
            }

            // Insert
            $ins = $pdo->prepare("INSERT INTO contracts (booking_id, content, status) VALUES (?, ?, 'pending')");
            $ins->execute([$bookingId, $content]);
            $contractId = $pdo->lastInsertId();

            // Notify Tenant
            $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id, message) VALUES (?, 'contract_created', ?, ?)");
            $msg = "Chủ nhà đã tạo hợp đồng thuê cho phòng '{$info['title']}'. Vui lòng xem và xác nhận.";
            $nStmt->execute([$info['tenant_id'], $contractId, $msg]);

            echo json_encode(['success' => true]);
            break;

        case 'get_contract_details':
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("
                SELECT c.*, l.owner_id, b.tenant_id
                FROM contracts c
                JOIN bookings b ON c.booking_id = b.id
                JOIN listings l ON b.listing_id = l.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $contract = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$contract) throw new Exception('Contract not found');
            if ($contract['owner_id'] != $userId && $contract['tenant_id'] != $userId) {
                throw new Exception('Permission denied');
            }
            
            echo json_encode($contract);
            break;

        case 'sign_contract':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;

            // Verify Tenant
            $stmt = $pdo->prepare("
                SELECT c.*, l.owner_id, b.tenant_id, l.title
                FROM contracts c
                JOIN bookings b ON c.booking_id = b.id
                JOIN listings l ON b.listing_id = l.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $contract = $stmt->fetch();

            if (!$contract) throw new Exception('Not found');
            if ($contract['tenant_id'] != $userId) throw new Exception('Permission denied');
            if ($contract['status'] == 'signed') throw new Exception('Already signed');

            // Update
            $upd = $pdo->prepare("UPDATE contracts SET status = 'signed', signed_at = NOW() WHERE id = ?");
            $upd->execute([$id]);

            // Notify Owner
            $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id, message) VALUES (?, 'contract_signed', ?, ?)");
            $msg = "Người thuê đã XÁC NHẬN hợp đồng thuê phòng '{$contract['title']}'.";
            $nStmt->execute([$contract['owner_id'], $id, $msg]);

            echo json_encode(['success' => true]);
            break;

        case 'delete_contract':
             if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
            
            // Verify Owner
             $stmt = $pdo->prepare("
                SELECT c.*, l.owner_id
                FROM contracts c
                JOIN bookings b ON c.booking_id = b.id
                JOIN listings l ON b.listing_id = l.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $contract = $stmt->fetch();
            
            if (!$contract || $contract['owner_id'] != $userId) throw new Exception('Permission denied');
            
            $del = $pdo->prepare("DELETE FROM contracts WHERE id = ?");
            $del->execute([$id]);
            
            echo json_encode(['success' => true]);
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
