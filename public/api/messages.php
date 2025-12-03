<?php
require_once __DIR__ . '/../../config/db.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$me = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = intval($_POST['to'] ?? 0);
    $text = trim($_POST['text'] ?? '');
    if (!$to || !$text) {
        echo json_encode(['error' => 'Missing']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id,receiver_id,content) VALUES (?,?,?)");
    $stmt->execute([$me, $to, $text]);
    echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $peer = intval($_GET['peer'] ?? 0);
    if (!$peer) {
        echo json_encode(['error' => 'Missing peer']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT m.*, s.username as sender_name FROM messages m JOIN users s ON m.sender_id = s.id WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY m.created_at ASC");
    $stmt->execute([$me, $peer, $peer, $me]);
    echo json_encode($stmt->fetchAll());
}
