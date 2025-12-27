<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/auth_check.php';

$pageTitle = 'Quản Lý Bài Đăng';
$tab = $_GET['tab'] ?? 'rentals'; 


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $type = $_POST['type'] ?? ''; 
    $action = $_POST['action'] ?? '';

    if ($id && $type && $action) {
        $table = ($type === 'listing') ? 'listings' : 'posts';
        
        if ($action === 'hide') {
            $stmt = $pdo->prepare("UPDATE $table SET status = 'hidden' WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'spam') {
            $stmt = $pdo->prepare("UPDATE $table SET status = 'spam' WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'restore') {
            $status = ($type === 'listing') ? 'available' : 'active';
            $stmt = $pdo->prepare("UPDATE $table SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
        }
        
        header("Location: posts.php?tab=" . ($type === 'listing' ? 'rentals' : 'finding'));
        exit;
    }
}


$items = [];
if ($tab === 'rentals') {
    $stmt = $pdo->query("SELECT l.*, u.username FROM listings l JOIN users u ON l.owner_id = u.id ORDER BY l.id DESC LIMIT 50");
    $items = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.id DESC LIMIT 50");
    $items = $stmt->fetchAll();
}

require_once __DIR__ . '/layout.php';
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <a href="?tab=rentals" class="btn <?= $tab === 'rentals' ? 'btn-primary' : '' ?>" style="margin-right:10px;">Tin Cho Thuê</a>
        <a href="?tab=finding" class="btn <?= $tab === 'finding' ? 'btn-primary' : '' ?>">Tin Tìm Phòng</a>
    </div>
    <div style="position: relative;">
        <input type="text" id="searchInput" placeholder="Tìm kiếm (Nội dung, Người đăng...)" 
               style="padding: 8px 35px 8px 15px; border: 1px solid #ddd; border-radius: 20px; width: 300px; outline: none;"
               onkeydown="if(event.key === 'Enter') handleSearch()">
        <i class="fa-solid fa-search" onclick="handleSearch()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #888; cursor: pointer; padding: 5px;"></i>
    </div>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Người đăng</th>
                <th>Nội dung / Tiêu đề</th>
                <th>Trạng thái</th>
                <th>Ngày đăng</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody id="postsTableBody">
            <?php foreach ($items as $item): ?>
            <tr style="<?= ($item['status'] ?? '') === 'spam' ? 'background:#fff5f5;' : '' ?>">
                <td>#<?= $item['id'] ?></td>
                <td><?= htmlspecialchars($item['username']) ?></td>
                <td>
                    <?php if ($tab === 'rentals'): ?>
                        <a href="/listing_detail.php?id=<?= $item['id'] ?>" target="_blank" style="font-weight:bold;"><?= htmlspecialchars($item['title']) ?></a>
                        <div style="font-size:0.9rem; color:#666;"><?= number_format($item['price']) ?> VNĐ</div>
                    <?php else: ?>
                        <div style="max-width:400px; max-height:60px; overflow:hidden;"><?= htmlspecialchars(substr($item['content'], 0, 100)) ?>...</div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                        $st = $item['status'] ?? 'active';
                        $color = 'badge-success';
                        if ($st == 'hidden') $color = 'badge-warning';
                        if ($st == 'spam') $color = 'badge-danger';
                    ?>
                    <span class="badge <?= $color ?>"><?= $st ?></span>
                </td>
                <td><?= date('d/m/Y', strtotime($item['created_at'])) ?></td>
                <td>
                    <?php if (($item['status'] ?? '') !== 'hidden' && ($item['status'] ?? '') !== 'spam'): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Ẩn bài này?');">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="type" value="<?= $tab === 'rentals' ? 'listing' : 'post' ?>">
                            <input type="hidden" name="action" value="hide">
                            <button class="btn btn-sm btn-warning">Ẩn</button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Đánh dấu SPAM?');">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="type" value="<?= $tab === 'rentals' ? 'listing' : 'post' ?>">
                            <input type="hidden" name="action" value="spam">
                            <button class="btn btn-sm btn-danger">Spam</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="type" value="<?= $tab === 'rentals' ? 'listing' : 'post' ?>">
                            <input type="hidden" name="action" value="restore">
                            <button class="btn btn-sm btn-success">Khôi phục</button>
                        </form>
                    <?php endif; ?>
                    
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa vĩnh viễn?');">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <input type="hidden" name="type" value="<?= $tab === 'rentals' ? 'listing' : 'post' ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn btn-sm" style="background:#e74c3c; color:white;">Xóa</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('postsTableBody');
    const currentTab = '<?= $tab ?>'; // rentals or finding
    const type = currentTab === 'rentals' ? 'listing' : 'post';
    let timeout = null;

    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const keyword = this.value.trim();
        
        timeout = setTimeout(() => {
           handleSearch();
        }, 300);
    });

    function handleSearch() {
        const keyword = searchInput.value.trim();
        if (keyword.length > 0) {
            fetchSearch(keyword);
        } else {
             location.reload(); 
        }
    }

    async function fetchSearch(keyword) {
        try {
            const res = await fetch(`/api/admin/search_content.php?type=${type}&keyword=${encodeURIComponent(keyword)}`);
            const data = await res.json();
            renderTable(data);
        } catch (e) {
            console.error(e);
            // alert('Search Error: ' + e.message); // Helpful for debugging
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px; color:red;">Lỗi tìm kiếm: ${e.message}. Vui lòng thử lại.</td></tr>`;
        }
    }

    function renderTable(items) {
        if (!items || items.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px;">Không tìm thấy kết quả.</td></tr>';
            return;
        }

        let html = '';
        items.forEach(item => {
            const isSpam = item.status === 'spam';
            const bgStyle = isSpam ? 'background:#fff5f5;' : '';
            
            // Reconstruct Status Badge
            let badgeColor = 'badge-success';
            if (item.status === 'hidden') badgeColor = 'badge-warning';
            if (item.status === 'spam') badgeColor = 'badge-danger';
            
            // Reconstruct Content Column
            let contentHtml = '';
            if (currentTab === 'rentals') {
                contentHtml = `<a href="/listing_detail.php?id=${item.id}" target="_blank" style="font-weight:bold;">${escapeHtml(item.display_title ?? '')}</a>
                               <div style="font-size:0.9rem; color:#666;">${escapeHtml(item.display_info ?? '')}</div>`;
            } else {
                contentHtml = `<div style="max-width:400px; max-height:60px; overflow:hidden;">${escapeHtml(item.display_title ?? '')}</div>
                               <div style="font-size:0.8rem; color:#888;">${escapeHtml(item.display_info ?? '')}</div>`;
            }

            // Actions Buttons
            let actionsHtml = '';
            const confirmHide = "return confirm('Ẩn bài này?');";
            const confirmSpam = "return confirm('Đánh dấu SPAM?');";
            const confirmRestore = "return confirm('Khôi phục?');";
            const confirmDelete = "return confirm('Xóa vĩnh viễn?');";

            if (item.status !== 'hidden' && item.status !== 'spam') {
                actionsHtml += `
                    <form method="POST" style="display:inline;" onsubmit="${confirmHide}">
                        <input type="hidden" name="id" value="${item.id}">
                        <input type="hidden" name="type" value="${type}">
                        <input type="hidden" name="action" value="hide">
                        <button class="btn btn-sm btn-warning">Ẩn</button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="${confirmSpam}">
                        <input type="hidden" name="id" value="${item.id}">
                        <input type="hidden" name="type" value="${type}">
                        <input type="hidden" name="action" value="spam">
                        <button class="btn btn-sm btn-danger">Spam</button>
                    </form>`;
            } else {
                actionsHtml += `
                    <form method="POST" style="display:inline;" onsubmit="${confirmRestore}">
                        <input type="hidden" name="id" value="${item.id}">
                        <input type="hidden" name="type" value="${type}">
                        <input type="hidden" name="action" value="restore">
                        <button class="btn btn-sm btn-success">Khôi phục</button>
                    </form>`;
            }
            actionsHtml += `
                 <form method="POST" style="display:inline;" onsubmit="${confirmDelete}">
                    <input type="hidden" name="id" value="${item.id}">
                    <input type="hidden" name="type" value="${type}">
                    <input type="hidden" name="action" value="delete">
                    <button class="btn btn-sm" style="background:#e74c3c; color:white;">Xóa</button>
                </form>`;

            html += `
                <tr style="${bgStyle}">
                    <td>#${item.id}</td>
                    <td>${escapeHtml(item.owner_name)}</td>
                    <td>${contentHtml}</td>
                    <td><span class="badge ${badgeColor}">${item.status}</span></td>
                    <td>${new Date(item.created_at).toLocaleDateString('en-GB')}</td>
                    <td>${actionsHtml}</td>
                </tr>
            `;
        });
        tableBody.innerHTML = html;
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>
</div>
