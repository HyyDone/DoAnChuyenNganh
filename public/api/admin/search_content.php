<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../admin/auth_check.php'; 

header('Content-Type: application/json');

$keyword = $_GET['keyword'] ?? '';
$type = $_GET['type'] ?? 'listing'; 

if (!$keyword) {
    echo json_encode([]);
    exit;
}

try {
    $results = [];
    if ($type === 'listing') {
        $stmt = $pdo->prepare("
            SELECT l.id, l.title, l.address, l.price, l.status, l.created_at, u.full_name, u.username
            FROM listings l
            JOIN users u ON l.owner_id = u.id
            WHERE l.title LIKE ? OR l.description LIKE ? OR l.address LIKE ? OR u.full_name LIKE ?
            ORDER BY l.created_at DESC
            LIMIT 50
        ");
        $term = "%$keyword%";
        $stmt->execute([$term, $term, $term, $term]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        
        foreach ($results as &$row) {
            $row['display_title'] = $row['title'];
            $row['display_info'] = number_format($row['price']) . ' đ - ' . $row['address'];
            $row['owner_name'] = $row['full_name'] ?: $row['username'];
            $row['type'] = 'listing';
        }
    } else {
        $stmt = $pdo->prepare("
            SELECT p.id, p.content, p.status, p.created_at, u.full_name, u.username, 
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.content LIKE ? OR u.full_name LIKE ?
            ORDER BY p.created_at DESC
            LIMIT 50
        ");
        $term = "%$keyword%";
        $stmt->execute([$term, $term]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$row) {
            $row['display_title'] = substr($row['content'], 0, 50) . '...';
            $row['display_info'] = 'Likes: ' . $row['like_count'] . ' - Comments: ' . $row['comment_count'];
            $row['owner_name'] = $row['full_name'] ?: $row['username'];
            $row['type'] = 'post';
        }
    }

    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
