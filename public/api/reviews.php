<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $type = $data['type'] ?? 'listing'; 
    $reviewerId = $_SESSION['user_id'];
    $rating = $data['rating'] ?? null;
    $comment = $data['comment'] ?? '';

    if (!$rating) {
        echo json_encode(['success' => false, 'message' => 'Missing rating']);
        exit;
    }

    try {
        if ($type === 'listing') {
            $listingId = $data['listing_id'] ?? null;
            if (!$listingId) throw new Exception('Listing ID required');
            
            
            $stmt = $pdo->prepare("INSERT INTO listing_reviews (listing_id, reviewer_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$listingId, $reviewerId, $rating, $comment]);

        } else {
            
            $targetUserId = $data['target_user_id'] ?? $data['reviewee_id'] ?? null;
            
            if (!$targetUserId) throw new Exception('Target User ID required');
            if ($targetUserId == $reviewerId) throw new Exception('Cannot review yourself');
            
            
            $stmt = $pdo->prepare("INSERT INTO user_reviews (reviewee_id, reviewer_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$targetUserId, $reviewerId, $rating, $comment]);
        }
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }

} elseif ($method === 'GET') {
    $type = $_GET['type'] ?? 'listing';

    try {
        if ($type === 'listing') {
            $listingId = $_GET['listing_id'] ?? null;
            if (!$listingId) throw new Exception('Listing ID required');
            
            
            $stmt = $pdo->prepare("
                SELECT r.*, u.full_name as reviewer_name, u.avatar as reviewer_avatar 
                FROM listing_reviews r 
                JOIN users u ON r.reviewer_id = u.id 
                WHERE r.listing_id = ? 
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$listingId]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtStats = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM listing_reviews WHERE listing_id = ?");
            $stmtStats->execute([$listingId]);
        } else {
            $userId = $_GET['user_id'] ?? null;
            if (!$userId) throw new Exception('User ID required');

            
            $stmt = $pdo->prepare("
                SELECT r.*, u.full_name as reviewer_name, u.avatar as reviewer_avatar 
                FROM user_reviews r 
                JOIN users u ON r.reviewer_id = u.id 
                WHERE r.reviewee_id = ? 
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$userId]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtStats = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM user_reviews WHERE reviewee_id = ?");
            $stmtStats->execute([$userId]);
        }

        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true, 
            'reviews' => $reviews,
            'stats' => [
                'average' => round($stats['avg_rating'], 1) ?? 0,
                'count' => $stats['count']
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
