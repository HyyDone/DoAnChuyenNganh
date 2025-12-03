<?php
require_once __DIR__ . '/../../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Check for DELETE action (simulated via POST) or actual DELETE method if configured
    $action = $_GET['action'] ?? '';
    
    if ($action === 'delete') {
        $data = json_decode(file_get_contents('php://input'), true);
        $postId = $data['id'] ?? 0;
        
        if (!$postId) {
            echo json_encode(['error' => 'Missing ID']);
            exit;
        }

        try {
            // Verify ownership and get image path
            $stmt = $pdo->prepare("SELECT user_id, image FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();

            if (!$post) {
                echo json_encode(['error' => 'Post not found']);
                exit;
            }

            if ($post['user_id'] != $_SESSION['user_id']) {
                echo json_encode(['error' => 'Forbidden']);
                exit;
            }

            // Delete image if exists
            if ($post['image'] && file_exists(__DIR__ . '/../' . $post['image'])) {
                unlink(__DIR__ . '/../' . $post['image']);
            }

            // Delete post
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$postId]);

            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // Handle Create or Update
    $postId = $_POST['id'] ?? null;
    $content = trim($_POST['content'] ?? '');
    $removeOldImage = isset($_POST['remove_image']) && $_POST['remove_image'] === 'true';
    
    // Handle Image Upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadDir . $newFileName;
            
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $imagePath = 'uploads/' . $newFileName;
            }
        }
    }

    if (!$content && !$imagePath && !$postId) {
         // For new posts, must have content or image
        echo json_encode(['error' => 'Empty']);
        exit;
    }

    try {
        if ($postId) {
            // UPDATE
            // Verify ownership
            $stmt = $pdo->prepare("SELECT user_id, image FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();

            if (!$post || $post['user_id'] != $_SESSION['user_id']) {
                echo json_encode(['error' => 'Forbidden']);
                exit;
            }

            // Handle Image Logic for Update
            $finalImagePath = $post['image']; // Default to keeping old image
            
            if ($imagePath) {
                // New image uploaded -> replace
                if ($post['image'] && file_exists(__DIR__ . '/../' . $post['image'])) {
                    unlink(__DIR__ . '/../' . $post['image']);
                }
                $finalImagePath = $imagePath;
            } elseif ($removeOldImage) {
                // Explicitly asked to remove image
                if ($post['image'] && file_exists(__DIR__ . '/../' . $post['image'])) {
                    unlink(__DIR__ . '/../' . $post['image']);
                }
                $finalImagePath = null;
            }

            $stmt = $pdo->prepare("UPDATE posts SET content = ?, image = ? WHERE id = ?");
            $stmt->execute([$content, $finalImagePath, $postId]);
            echo json_encode(['ok' => true, 'action' => 'updated']);

        } else {
            // CREATE
            if (!$content && !$imagePath) {
                echo json_encode(['error' => 'Empty']);
                exit;
            }
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $content, $imagePath]);
            echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId(), 'image' => $imagePath]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

if ($method === 'GET') {
    try {
        $userId = $_SESSION['user_id'] ?? 0;
        $sql = "
            SELECT p.*, 
                   u.username, u.full_name, u.avatar,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            ORDER BY p.created_at DESC 
            LIMIT 50
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
