<?php
ini_set("display_errors", 1);
require_once __DIR__ . '/../config/db.php';
session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$newsItem = null;

if ($id) {
    $pdo->exec("UPDATE news SET views = views + 1 WHERE id = $id");

    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $newsItem = $stmt->fetch();
}

if (!$newsItem) {
    header("Location: /news.php");
    exit;
}

try {
    $stmtTop = $pdo->query("SELECT * FROM news ORDER BY views DESC LIMIT 5");
    $topNews = $stmtTop->fetchAll();
} catch (PDOException $e) {
    $topNews = [];
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($newsItem['title']) ?> - Tin tức</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #f0f2f5;
            --card-bg: #ffffff;
            --primary-color: #0866ff;
            --text-color: #050505;
            --secondary-text: #65676b;
        }
        body {
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            color: var(--text-color);
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 24px;
        }
        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; }
            .sidebar { order: 2; position: static; }
            .main-content { order: 1; }
        }

        .sidebar {
            position: sticky;
            top: 20px;
            height: fit-content;
        }

        .sidebar-card {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .sidebar-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 8px;
        }
        .top-news-item {
            display: block;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            text-decoration: none;
            color: inherit;
        }
        .top-news-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
            color: #333;
        }
        .top-news-views { font-size: 12px; color: var(--secondary-text); }

        .detail-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .detail-title {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 15px 0;
            line-height: 1.3;
        }
        .detail-meta {
            color: var(--secondary-text);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            gap: 20px;
            font-size: 14px;
        }
        .detail-summary {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
            font-style: italic;
            border-left: 4px solid var(--primary-color);
            padding-left: 15px;
        }
        .detail-body {
            font-size: 17px;
            line-height: 1.8;
            color: #1c1e21;
        }
        .detail-body p { margin-bottom: 15px; }

        .btn-back {
            display: inline-block;
            margin-bottom: 15px;
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 600;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <aside class="sidebar">
        <a href="/news.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Trở về trang tin tức</a>
        
        <div class="sidebar-card">
            <div class="sidebar-title">Có thể bạn quan tâm</div>
            <div class="top-news-list">
                <?php foreach ($topNews as $item): ?>
                    <a href="/news-detail.php?id=<?= $item['id'] ?>" class="top-news-item">
                        <div class="top-news-info">
                            <h4><?= htmlspecialchars($item['title']) ?></h4>
                            <div class="top-news-views"><i class="fa-regular fa-eye"></i> <?= number_format($item['views']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <article class="detail-card">
            <h1 class="detail-title"><?= htmlspecialchars($newsItem['title']) ?></h1>
            <div class="detail-meta">
                <span><i class="fa-regular fa-clock"></i> <?= date('d/m/Y H:i', strtotime($newsItem['created_at'])) ?></span>
                <span><i class="fa-regular fa-eye"></i> <?= number_format($newsItem['views']) ?> lượt xem</span>
                <span style="color: var(--primary-color); font-weight: bold;"><?= htmlspecialchars($newsItem['category']) ?></span>
            </div>
            
            <div class="detail-summary">
                <?= nl2br(htmlspecialchars($newsItem['summary'])) ?>
            </div>

            <div class="detail-body">
                <?= nl2br(htmlspecialchars($newsItem['content'])) ?>
            </div>
        </article>
    </main>
</div>

<?php if (file_exists(__DIR__ . '/includes/footer.php')) include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
