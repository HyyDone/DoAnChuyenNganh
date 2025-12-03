<?php
require_once __DIR__ . '/../../config/db.php';
session_start();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'like') {
        $postId = $input['post_id'] ?? 0;
        
        // Check if already liked
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$userId, $postId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Unlike
            $stmt = $pdo->prepare("DELETE FROM likes WHERE id = ?");
            $stmt->execute([$existing['id']]);
            echo json_encode(['status' => 'unliked']);
        } else {
            // Like
            $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
            $stmt->execute([$userId, $postId]);
            echo json_encode(['status' => 'liked']);
        }
    } elseif ($action === 'comment') {
        $postId = $input['post_id'] ?? 0;
        $content = trim($input['content'] ?? '');
        $parentId = !empty($input['parent_id']) ? $input['parent_id'] : null;

        if (!$content) {
            echo json_encode(['error' => 'Empty content']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content, parent_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $postId, $content, $parentId]);
        
        $commentId = $pdo->lastInsertId();
        
        // Fetch the new comment with user info
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.full_name, u.avatar 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$commentId]);
        $newComment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['ok' => true, 'comment' => $newComment]);
    }
} elseif ($method === 'GET') {
    if ($action === 'comments') {
        $postId = $_GET['post_id'] ?? 0;
        
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.full_name, u.avatar 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = ? 
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$postId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize into tree structure
        $commentTree = [];
        $commentsById = [];
        
        foreach ($comments as $comment) {
            $comment['replies'] = [];
            $commentsById[$comment['id']] = $comment;
        }
        
        foreach ($commentsById as $id => &$comment) {
            if ($comment['parent_id']) {
                if (isset($commentsById[$comment['parent_id']])) {
                    $commentsById[$comment['parent_id']]['replies'][] = &$comment;
                }
            } else {
                $commentTree[] = &$comment;
            }
        }
        
        echo json_encode($commentTree);
    }
}
