<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/auth_check.php';

$pageTitle = 'Dashboard Thống Kê';


try {
    
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $totalListings = $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
    $totalReports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'open'")->fetchColumn();

    
    $listingStats = $pdo->query("SELECT status, COUNT(*) as count FROM listings GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    
    $cityStats = $pdo->query("SELECT city, COUNT(*) as count FROM listings GROUP BY city")->fetchAll(PDO::FETCH_KEY_PAIR);

} catch (PDOException $e) {
    echo "Error fetching stats: " . $e->getMessage();
}

require_once __DIR__ . '/layout.php';
?>

<div class="stat-grid">
    <div class="stat-card">
        <h3><?= number_format($totalUsers) ?></h3>
        <p>Người dùng</p>
    </div>
    <div class="stat-card" style="border-left-color: #2ecc71;">
        <h3><?= number_format($totalListings) ?></h3>
        <p>Bài Cho Thuê</p>
    </div>
    <div class="stat-card" style="border-left-color: #f1c40f;">
        <h3><?= number_format($totalPosts) ?></h3>
        <p>Tin Tìm Phòng</p>
    </div>
    <div class="stat-card" style="border-left-color: #e74c3c;">
        <h3><?= number_format($totalReports) ?></h3>
        <p>Báo cáo Chờ xử lý</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="card">
        <h3>Tỷ lệ trạng thái phòng</h3>
        <canvas id="statusChart"></canvas>
    </div>
    <div class="card">
        <h3>Phân bố khu vực</h3>
        <canvas id="cityChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Status Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($listingStats)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($listingStats)) ?>,
                backgroundColor: ['#2ecc71', '#e74c3c', '#95a5a6', '#f1c40f', '#34495e']
            }]
        }
    });

    // City Chart
    new Chart(document.getElementById('cityChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($cityStats)) ?>,
            datasets: [{
                label: 'Số lượng phòng',
                data: <?= json_encode(array_values($cityStats)) ?>,
                backgroundColor: '#3498db'
            }]
        },
        options: {
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

</div> <!-- End main-content (from layout) -->
</body>
</html>
