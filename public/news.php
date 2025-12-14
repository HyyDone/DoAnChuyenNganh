<?php
ini_set("display_errors", 1);
require_once __DIR__ . '/../config/db.php';
session_start();

try {
    $stmtTop = $pdo->query("SELECT * FROM news ORDER BY views DESC LIMIT 5");
    $topNews = $stmtTop->fetchAll();
} catch (PDOException $e) {
    $topNews = [];
}

$category = isset($_GET['category']) ? $_GET['category'] : null;
$newsList = [];

try {
    if ($category) {
        $stmtList = $pdo->prepare("SELECT * FROM news WHERE category = ? ORDER BY created_at DESC");
        $stmtList->execute([$category]);
    } else {
        $stmtList = $pdo->query("SELECT * FROM news ORDER BY created_at DESC");
    }
    $newsList = $stmtList->fetchAll();
} catch (PDOException $e) {
    $newsList = [];
}

$categories = [
    'Tin thuê trọ',
    'Kinh nghiệm thuê nhà',
    'Pháp lý nhà trọ',
    'Cảnh báo lừa đảo',
    'Tin thị trường'
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Tin tức - Thuê Trọ</title>
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
            --divider: #ced0d4;
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
            .container {
                grid-template-columns: 1fr;
            }
        }

        aside {
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
            display: inline-block;
        }

        .top-news-item {
            display: block;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            text-decoration: none;
            color: inherit;
        }
        .top-news-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .top-news-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
            color: #333;
        }
        .top-news-views {
            font-size: 12px;
            color: var(--secondary-text);
        }

        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .category-item {
            margin-bottom: 8px;
        }
        .category-link {
            display: block;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .category-link:hover, .category-link.active {
            background: var(--primary-color);
            color: white;
        }
        .category-link i {
            font-size: 12px;
        }

        .news-header {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .news-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .news-item {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .news-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .news-content {
            display: flex;
            flex-direction: column;
        }

        .news-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 10px 0;
            line-height: 1.4;
        }
        .news-title a {
            text-decoration: none;
            color: #1c1e21;
        }
        .news-title a:hover {
            color: var(--primary-color);
        }

        .news-meta {
            font-size: 13px;
            color: var(--secondary-text);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .news-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .news-summary {
            font-size: 15px;
            color: #4b4c4f;
            line-height: 1.5;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <aside>
        <div class="sidebar-card">
            <div class="sidebar-title">Top 5 Xem Nhiều</div>
            <div class="top-news-list">
                <?php if (count($topNews) > 0): ?>
                    <?php foreach ($topNews as $item): ?>
                        <a href="/news-detail.php?id=<?= $item['id'] ?>" class="top-news-item">
                            <div class="top-news-info">
                                <h4><?= htmlspecialchars($item['title']) ?></h4>
                                <div class="top-news-views"><i class="fa-regular fa-eye"></i> <?= number_format($item['views']) ?> lượt xem</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Chưa có tin tức nào.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="sidebar-card">
            <div class="sidebar-title">Chuyên Mục</div>
            <select onchange="if(this.value) window.location.href=this.value" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; margin-bottom: 15px; display: none;">
                <option value="/news.php">Tất cả chuyên mục</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="/news.php?category=<?= urlencode($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <ul class="category-list">
                <li class="category-item">
                    <a href="/news.php" class="category-link <?= !$category ? 'active' : '' ?>">
                        Tất cả <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </li>
                <?php foreach ($categories as $cat): ?>
                    <li class="category-item">
                        <a href="/news.php?category=<?= urlencode($cat) ?>" class="category-link <?= $category === $cat ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat) ?> <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>

    <main>
        <div class="news-header">
            <?php if ($category): ?>
                <h1>Chuyên mục: <?= htmlspecialchars($category) ?></h1>
                <a href="/news.php" style="font-size: 14px; text-decoration: none; color: var(--primary-color); margin-top: 5px; display:block;">&larr; Quay lại tất cả tin tức</a>
            <?php else: ?>
                <h1>Tin Tức Mới Nhất</h1>
            <?php endif; ?>
        </div>

        <div class="news-list">
            <?php if (count($newsList) > 0): ?>
                <?php foreach ($newsList as $news): ?>
                    <article class="news-item">
                        <div class="news-content">
                            <h2 class="news-title">
                                <a href="/news-detail.php?id=<?= $news['id'] ?>"><?= htmlspecialchars($news['title']) ?></a>
                            </h2>
                            <div class="news-meta">
                                <span><i class="fa-regular fa-clock"></i> <?= date('d/m/Y', strtotime($news['created_at'])) ?></span>
                                <span><i class="fa-regular fa-eye"></i> <?= number_format($news['views']) ?></span>
                                <span style="color: var(--primary-color); font-weight: 500;"><?= htmlspecialchars($news['category']) ?></span>
                            </div>
                            <div class="news-summary">
                                <?= htmlspecialchars($news['summary']) ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: white; border-radius: 8px;">
                    <i class="fa-regular fa-newspaper" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                    <p>Chưa có bài viết nào trong chuyên mục này.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php if (file_exists(__DIR__ . '/includes/footer.php')) include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
