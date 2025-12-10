<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? 0;
$method = $_SERVER['REQUEST_METHOD'];

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($method === 'POST') {
    // Toggle favorite
    $data = json_decode(file_get_contents('php://input'), true);
    $listingId = $data['listing_id'] ?? 0;

    if (!$listingId) {
        echo json_encode(['success' => false, 'message' => 'Invalid listing ID']);
        exit;
    }

    try {
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?");
        $stmt->execute([$userId, $listingId]);
        $exists = $stmt->fetch();

        if ($exists) {
            // Remove
            $delParams = [$userId, $listingId];
            $delStmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND listing_id = ?");
            $delStmt->execute($delParams);
            echo json_encode(['success' => true, 'action' => 'removed']);
        } else {
            // Add
            $addParams = [$userId, $listingId];
            $addStmt = $pdo->prepare("INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)");
            $addStmt->execute($addParams);
            echo json_encode(['success' => true, 'action' => 'added']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
    }
} elseif ($method === 'GET') {
    // Get favorites or check status
    $listingId = $_GET['listing_id'] ?? null;

    try {
        if ($listingId) {
            // Check status for one listing
            $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?");
            $stmt->execute([$userId, $listingId]);
            $isFavorite = $stmt->fetch() ? true : false;
            echo json_encode(['success' => true, 'is_favorite' => $isFavorite]);
        } else {
            // Get all favorites
            $sql = "SELECT l.*, 
                           (SELECT file_path FROM listing_images WHERE listing_id = l.id AND is_cover = 1 LIMIT 1) as cover_image,
                           (SELECT file_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as fallback_image
                    FROM listings l
                    JOIN favorites f ON l.id = f.listing_id
                    WHERE f.user_id = ?
                    ORDER BY f.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process images
            foreach ($favorites as &$qt) {
                $img = $qt['cover_image'] ?? $qt['fallback_image'] ?? 'assets/default-room.jpg';
                if ($img && $img[0] !== '/' && strpos($img, 'assets/') !== 0 && strpos($img, 'http') !== 0) {
                     $img = '/' . $img;
                }
                $qt['image_url'] = $img;
            }

            echo json_encode(['success' => true, 'favorites' => $favorites]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
