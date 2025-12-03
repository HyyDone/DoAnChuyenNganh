<?php
require_once __DIR__ . '/../../config/db.php';
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = trim($_POST['q'] ?? '');
if (!$input) {
    echo json_encode(['error' => 'Missing parameter: q']);
    exit;
}

$fake_response = "Mình gợi ý: Bạn nên tìm phòng trong khu A nếu ngân sách <=3 triệu, hoặc khu B nếu cần gần trường.";

try {
    $stmt = $pdo->prepare("INSERT INTO ai_conversations (user_id, input, response) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $input, $fake_response]);
} catch (PDOException $e) {
    echo json_encode([
        'reply' => $fake_response,
        'warning' => 'Không lưu được lịch sử cuộc trò chuyện: ' . $e->getMessage()
    ]);
    exit;
}

echo json_encode(['reply' => $fake_response]);
exit;
