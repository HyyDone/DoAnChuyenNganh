<?php
require_once __DIR__ . '/../../config/db.php';
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'get';

try {
    if ($action === 'get') {
        // Get unread or recent 10 notifications
        $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count unread
        $countSql = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([$userId]);
        $unreadCount = $countStmt->fetchColumn();
        
        echo json_encode(['notifications' => $notifs, 'unread_count' => $unreadCount]);
    } 
    elseif ($action === 'mark_read') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             throw new Exception('Invalid method');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $notifId = $input['id'] ?? 0;
        
        if ($notifId) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notifId, $userId]);
            echo json_encode(['success' => true]);
        } else {
             echo json_encode(['error' => 'Missing ID']);
        }
    }
    else {
        echo json_encode(['error' => 'Invalid action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
