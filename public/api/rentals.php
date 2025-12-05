<?php
require_once __DIR__ . '/../../config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
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
            $check = $pdo->prepare("
                SELECT b.id, b.listing_id 
                FROM bookings b 
                JOIN listings l ON b.listing_id = l.id 
                WHERE b.id = ? AND l.owner_id = ? AND b.status = 'pending'
            ");
            $check->execute([$bookingId, $userId]);
            $booking = $check->fetch();
            
            if (!$booking) {
                throw new Exception('Booking not found or permission denied');
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

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
