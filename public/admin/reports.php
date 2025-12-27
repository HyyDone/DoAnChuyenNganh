<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/auth_check.php';

$pageTitle = 'Quản Lý Báo Cáo';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportId = $_POST['report_id'] ?? null;
    $action = $_POST['action'] ?? '';
    
    if ($reportId && $action) {
        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
            $stmt->execute([$reportId]);
        } else {
            $status = ($action === 'resolve') ? 'resolved' : 'reviewed';
            $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?");
            $stmt->execute([$status, $reportId]);

            if ($status === 'resolved') {
                $stmt = $pdo->prepare("SELECT reporter_id FROM reports WHERE id = ?");
                $stmt->execute([$reportId]);
                $reporterId = $stmt->fetchColumn();

                if ($reporterId) {
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id, message) VALUES (?, 'report_resolved', ?, ?)");
                    $stmt->execute([$reporterId, $reportId, 'Báo cáo của bạn đã được xử lý.']);
                }
            }
        }
        header("Location: reports.php");
        exit;
    }
}



$sql = "SELECT r.*, u.username as reporter_name,
        l.title as listing_title, 
        lu.full_name as listing_owner_name, lu.username as listing_owner_username,
        p.content as post_content,
        pu.full_name as post_owner_name, pu.username as post_owner_username
    FROM reports r 
    JOIN users u ON r.reporter_id = u.id 
    LEFT JOIN listings l ON r.target_type = 'listing' AND r.target_id = l.id
    LEFT JOIN users lu ON l.owner_id = lu.id
    LEFT JOIN posts p ON r.target_type = 'post' AND r.target_id = p.id
    LEFT JOIN users pu ON p.user_id = pu.id
    ORDER BY FIELD(r.status, 'open', 'reviewed', 'resolved'), r.created_at DESC";
$stmt = $pdo->query($sql);
$reports = $stmt->fetchAll();

require_once __DIR__ . '/layout.php';
?>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Người báo cáo</th>
                <th>Đối tượng</th>
                <th>Thông tin nội dung</th>
                <th>Lý do & Chi tiết</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reports as $r): ?>
            <tr>
                <td>#<?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['reporter_name']) ?></td>
                <td>
                    <span class="badge badge-warning"><?= $r['target_type'] ?></span> #<?= $r['target_id'] ?>
                    <?php
                        $link = '#';
                        if ($r['target_type'] === 'listing') $link = "/listing_detail.php?id=" . $r['target_id'];
                        
                    ?>
                    <a href="<?= $link ?>" target="_blank"><i class="fa-solid fa-external-link-alt"></i></a>
                </td>
                <td>
                    <?php
                        $ownerName = '---';
                        $contentSnippet = '---';
                        if ($r['target_type'] === 'listing') {
                            $ownerName = $r['listing_owner_name'] ?: $r['listing_owner_username'];
                            $contentSnippet = $r['listing_title'];
                        } elseif ($r['target_type'] === 'post') {
                            $ownerName = $r['post_owner_name'] ?: $r['post_owner_username'];
                            $contentSnippet = $r['post_content'];
                        }
                    ?>
                    <div><strong>Người đăng:</strong> <?= htmlspecialchars($ownerName ?? 'Unknown') ?></div>
                    <div style="font-size:0.9rem; color:#555; margin-top:4px; max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= htmlspecialchars($contentSnippet ?? '') ?>
                    </div>
                </td>
                <td style="max-width: 300px;">
                    <b><?= htmlspecialchars($r['reason']) ?></b><br>
                    <small><?= htmlspecialchars($r['details']) ?></small>
                </td>
                <td>
                    <?php
                        $color = 'badge-danger'; 
                        if ($r['status'] == 'resolved') $color = 'badge-success';
                        if ($r['status'] == 'reviewed') $color = 'badge-warning';
                    ?>
                    <span class="badge <?= $color ?>"><?= $r['status'] ?></span>
                </td>
                <td>
                    <?php if ($r['status'] !== 'resolved'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="resolve">
                            <button class="btn btn-sm btn-success">Đã xử lý</button>
                        </form>
                    <?php else: ?>
                        <span style="color:green; margin-right: 5px;"><i class="fa-solid fa-check"></i> Xong</span>
                    <?php endif; ?>
                    
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa báo cáo này?');">
                        <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
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
</body>
</html>
