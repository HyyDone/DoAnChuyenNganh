<?php
require_once __DIR__ . '/../config/db.php';
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT l.*, u.username, u.avatar FROM listings l JOIN users u ON l.owner_id = u.id WHERE l.id = ? LIMIT 1");
$stmt->execute([$id]);
$room = $stmt->fetch();
if (!$room) {
    echo 'Not found';
    exit;
}
$stmtImgs = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id = ?");
$stmtImgs->execute([$id]);
$images = $stmtImgs->fetchAll();
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($room['title']) ?></title>
</head>

<body>
    <h1><?= htmlspecialchars($room['title']) ?></h1>
    <p>Owner: <?= htmlspecialchars($room['username']) ?></p>
    <p>Price: <?= htmlspecialchars($room['price']) ?></p>
    <div>
        <?php foreach ($images as $img): ?>
            <img src="/<?= htmlspecialchars($img['file_path']) ?>" style="width:200px;height:150px;object-fit:cover;margin:4px">
        <?php endforeach; ?>
    </div>
    <p><?= nl2br(htmlspecialchars($room['description'])) ?></p>
</body>

</html>