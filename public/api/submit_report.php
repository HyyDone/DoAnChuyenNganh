<?php
require_once __DIR__ . '/../../config/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$targetType = $input['target_type'] ?? '';
$targetId = $input['target_id'] ?? 0;
$reason = $input['reason'] ?? '';
$details = $input['details'] ?? '';

if (!$targetType || !$targetId || !$reason) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin báo cáo.']);
    exit;
}

try {
    
    $stmt = $pdo->prepare("INSERT INTO reports (reporter_id, target_type, target_id, reason, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $targetType, $targetId, $reason, $details]);
    
    
    
    $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
    
    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id, message) VALUES (?, 'new_report', ?, ?)");
    
    $message = "Có báo cáo mới về " . ($targetType === 'listing' ? 'Tin đăng' : 'Bài viết') . " #" . $targetId;
    
    foreach ($admins as $adminId) {
        $notifStmt->execute([$adminId, $targetId, $message]); 
        
    }

    echo json_encode(['success' => true, 'message' => 'Báo cáo đã được gửi.']);

} catch (PDOException $e) {
    error_log("Report Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi server.']);
}
