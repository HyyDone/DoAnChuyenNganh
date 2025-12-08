<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/auth.php';

header('Content-Type: application/json');

// Helper to send JSON response
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Check authentication
$currentUserId = current_user_id();
if (!$currentUserId) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'conversations':
            // Get list of users the current user has chatted with
            // We want the latest message for each conversation to sort by time
            $sql = "
                SELECT 
                    u.id, u.username, u.full_name, u.avatar,
                    m.content as last_message,
                    m.created_at as last_message_time,
                    m.is_read,
                    m.sender_id
                FROM users u
                JOIN messages m ON (
                    (m.sender_id = ? AND m.receiver_id = u.id) 
                    OR 
                    (m.sender_id = u.id AND m.receiver_id = ?)
                )
                WHERE m.id IN (
                    SELECT MAX(id) 
                    FROM messages 
                    WHERE (sender_id = ? OR receiver_id = ?)
                    GROUP BY IF(sender_id = ?, receiver_id, sender_id)
                )
                ORDER BY m.created_at DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse($conversations);
            break;

        case 'history':
            $otherUserId = $_GET['user_id'] ?? 0;
            if (!$otherUserId) {
                jsonResponse(['error' => 'Missing user_id'], 400);
            }
            
            // Mark messages from this user as read
            $updateStmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
            $updateStmt->execute([$otherUserId, $currentUserId]);

            // Fetch chat history
            $stmt = $pdo->prepare("
                SELECT * FROM messages 
                WHERE (sender_id = ? AND receiver_id = ?) 
                   OR (sender_id = ? AND receiver_id = ?) 
                ORDER BY created_at ASC
            ");
            $stmt->execute([$currentUserId, $otherUserId, $otherUserId, $currentUserId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse($messages);
            break;

        case 'unread':
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
            $stmt->execute([$currentUserId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            jsonResponse(['count' => $result['count']]);
            break;

        case 'send':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            
            $receiverId = $_POST['receiver_id'] ?? 0;
            $content = trim($_POST['content'] ?? '');
            
            // Handle Image Upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/chat/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = $_FILES['image']['name'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                
                $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    $newFileName = md5(time() . '_' . $fileName) . '.' . $fileExtension;
                    $dest_path = $uploadDir . $newFileName;
                    
                    if(move_uploaded_file($_FILES['image']['tmp_name'], $dest_path)) {
                        $imagePath = 'uploads/chat/' . $newFileName;
                    }
                }
            }
            
            if (!$receiverId || (empty($content) && !$imagePath)) {
                jsonResponse(['error' => 'Invalid input'], 400);
            }
            
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, image, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$currentUserId, $receiverId, $content, $imagePath]);
            
            // Return the new message
            $messageId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
            $stmt->execute([$messageId]);
            $newMessage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            jsonResponse($newMessage);
            break;
            
        case 'user_info':
             $userId = $_GET['user_id'] ?? 0;
             if (!$userId) {
                 jsonResponse(['error' => 'Missing user_id'], 400);
             }
             $stmt = $pdo->prepare("SELECT id, username, full_name, avatar FROM users WHERE id = ?");
             $stmt->execute([$userId]);
             $user = $stmt->fetch(PDO::FETCH_ASSOC);
             if ($user) {
                 jsonResponse($user);
             } else {
                 jsonResponse(['error' => 'User not found'], 404);
             }
             break;

        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
