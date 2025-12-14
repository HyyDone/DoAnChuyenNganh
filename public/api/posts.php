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

    $action = $_GET['action'] ?? '';
    
    if ($action === 'delete') {
        $data = json_decode(file_get_contents('php://input'), true);
        $postId = $data['id'] ?? 0;
        
        if (!$postId) {
            echo json_encode(['error' => 'Missing ID']);
            exit;
        }

        try {
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

            $stmtImgs = $pdo->prepare("SELECT file_path FROM post_images WHERE post_id = ?");
            $stmtImgs->execute([$postId]);
            $images = $stmtImgs->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($post['image'])) {
                $images[] = $post['image'];
            }
            
            $uniqueImages = array_unique($images);

            foreach ($uniqueImages as $imgInfo) {
                if ($imgInfo && file_exists(__DIR__ . '/../' . $imgInfo)) {
                    unlink(__DIR__ . '/../' . $imgInfo);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$postId]);

            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $maxSize = ini_get('post_max_size');
        echo json_encode([
            'success' => false, 
            'error' => "Kích thước dữ liệu gửi lên quá lớn (Vượt quá $maxSize). Vui lòng giảm số lượng hoặc dung lượng ảnh."
        ]);
        exit;
    }

    $postId = $_POST['id'] ?? null;
    $content = trim($_POST['content'] ?? '');
    
    $uploadedImages = [];
    $warnings = [];
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filesToProcess = [];
    
    if (isset($_FILES['images'])) {
        $logFile = __DIR__ . '/../debug_log.txt';
        $logMsg = date('[Y-m-d H:i:s] ') . "Received images count: " . count($_FILES['images']['name']) . PHP_EOL;
        file_put_contents($logFile, $logMsg, FILE_APPEND);
        
        foreach ($_FILES['images']['name'] as $key => $value) {
            $errorCode = $_FILES['images']['error'][$key];
            $logMsg = date('[Y-m-d H:i:s] ') . "File $key error code: $errorCode" . PHP_EOL;
            file_put_contents($logFile, $logMsg, FILE_APPEND);
            
            if ($errorCode === UPLOAD_ERR_OK) {
                $filesToProcess[] = [
                    'name' => $_FILES['images']['name'][$key],
                    'tmp_name' => $_FILES['images']['tmp_name'][$key],
                    'error' => $_FILES['images']['error'][$key]
                ];
            } else {
                $fileName = $_FILES['images']['name'][$key];
                if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
                    $warnings[] = "Ảnh '$fileName' quá lớn (Vượt quá " . ini_get('upload_max_filesize') . ").";
                } elseif ($errorCode === UPLOAD_ERR_PARTIAL) {
                    $warnings[] = "Ảnh '$fileName' chỉ được tải lên một phần.";
                } elseif ($errorCode !== UPLOAD_ERR_NO_FILE) {
                     $warnings[] = "Ảnh '$fileName' gặp lỗi không xác định (Mã: $errorCode).";
                }
            }
        }
    }
    if (empty($filesToProcess) && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $filesToProcess[] = [
            'name' => $_FILES['image']['name'],
            'tmp_name' => $_FILES['image']['tmp_name'],
            'error' => $_FILES['image']['error']
        ];
    }

    foreach ($filesToProcess as $file) {
        $fileName = $file['name'];
        $fileTmpPath = $file['tmp_name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');
        if (in_array($fileExtension, $allowedfileExtensions)) {
             $newFileName = md5(time() . $fileName . uniqid()) . '.' . $fileExtension;
             $dest_path = $uploadDir . $newFileName;
             if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $uploadedImages[] = 'uploads/' . $newFileName;
             }
        }
    }

    if (!$content && empty($uploadedImages) && !$postId) {
        echo json_encode(['error' => 'Empty']);
        exit;
    }

    try {
        if ($postId) {
            
            $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();

            if (!$post || $post['user_id'] != $_SESSION['user_id']) {
                echo json_encode(['error' => 'Forbidden']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE posts SET content = ? WHERE id = ?");
            $stmt->execute([$content, $postId]);

            if (!empty($uploadedImages)) {
                $stmtImg = $pdo->prepare("INSERT INTO post_images (post_id, file_path) VALUES (?, ?)");
                foreach ($uploadedImages as $imgPath) {
                    $stmtImg->execute([$postId, $imgPath]);
                }
                
                $stmtCheckMain = $pdo->prepare("SELECT image FROM posts WHERE id = ?");
                $stmtCheckMain->execute([$postId]);
                $curr = $stmtCheckMain->fetch();
                if (empty($curr['image'])) {
                    $stmtUpdMain = $pdo->prepare("UPDATE posts SET image = ? WHERE id = ?");
                    $stmtUpdMain->execute([$uploadedImages[0], $postId]);
                }
            }
            
            echo json_encode([
                'ok' => true, 
                'action' => 'updated',
                'warnings' => $warnings
            ]);

        } else {
            $mainImage = !empty($uploadedImages) ? $uploadedImages[0] : null;
            
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $content, $mainImage]);
            $newPostId = $pdo->lastInsertId();

            if (!empty($uploadedImages)) {
                $stmtImg = $pdo->prepare("INSERT INTO post_images (post_id, file_path) VALUES (?, ?)");
                foreach ($uploadedImages as $imgPath) {
                    $stmtImg->execute([$newPostId, $imgPath]);
                }
            }
            
            echo json_encode([
                'ok' => true, 
                'id' => $newPostId, 
                'images' => $uploadedImages,
                'warnings' => $warnings
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

if ($method === 'GET') {
    try {
        $userId = $_SESSION['user_id'] ?? 0;
        
        $pdo->exec("SET SESSION group_concat_max_len = 100000");

        $sql = "
            SELECT p.*, 
                   u.username, u.full_name, u.avatar,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked,
                   (SELECT GROUP_CONCAT(file_path SEPARATOR ',') FROM post_images WHERE post_id = p.id) as all_images
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
