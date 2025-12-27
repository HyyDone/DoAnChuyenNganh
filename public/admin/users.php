<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/auth_check.php';

$pageTitle = 'Quản Lý Tài Khoản';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? '';
    
    if ($userId && $action) {
        if ($action === 'lock') {
            $reason = $_POST['reason'] ?? 'Vi phạm điều khoản';
            $stmt = $pdo->prepare("UPDATE users SET is_active = 0, lock_reason = ? WHERE id = ?");
            $stmt->execute([$reason, $userId]);
        } elseif ($action === 'unlock') {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 1, lock_reason = NULL WHERE id = ?");
            $stmt->execute([$userId]);
        }
        header("Location: users.php"); 
        exit;
    }
}


$stmt = $pdo->query("SELECT id, username, email, full_name, role, is_active, lock_reason, created_at, 
    (SELECT COUNT(*) FROM posts WHERE user_id = users.id) as post_count,
    (SELECT COUNT(*) FROM listings WHERE owner_id = users.id) as listing_count
    FROM users ORDER BY id DESC LIMIT 50");
$users = $stmt->fetchAll();

require_once __DIR__ . '/layout.php';
?>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Role</th>
                <th>Bài đăng</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>#<?= $u['id'] ?></td>
                <td>
                    <b><?= htmlspecialchars($u['username']) ?></b><br>
                    <small><?= htmlspecialchars($u['email']) ?></small>
                </td>
                <td>
                    <span class="badge <?= $u['role'] === 'admin' ? 'badge-warning' : 'badge-success' ?>">
                        <?= htmlspecialchars($u['role']) ?>
                    </span>
                </td>
                <td>
                    Posts: <?= $u['post_count'] ?><br>
                    Rentals: <?= $u['listing_count'] ?>
                </td>
                <td>
                    <?php if ($u['is_active']): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Locked</span>
                        <div style="font-size:0.8rem; color:red;"><?= htmlspecialchars($u['lock_reason']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($u['role'] !== 'admin'): ?>
                        <?php if ($u['is_active']): ?>
                            <button onclick="openLockModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')" class="btn btn-sm btn-danger">Khóa</button>
                        <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="unlock">
                                <button type="submit" class="btn btn-sm btn-success">Mở khóa</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Lock Modal -->
<div id="lockModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; padding:20px; border-radius:8px; width:400px;">
        <h3>Khóa tài khoản: <span id="modalUsername"></span></h3>
        <form method="POST">
            <input type="hidden" name="user_id" id="modalUserId">
            <input type="hidden" name="action" value="lock">
            <label>Lý do:</label>
            <textarea name="reason" style="width:100%; height:80px; margin:10px 0;" required placeholder="Nhập lý do..."></textarea>
            <div style="text-align:right;">
                <button type="button" onclick="closeLockModal()" class="btn">Hủy</button>
                <button type="submit" class="btn btn-danger">Khóa ngay</button>
            </div>
        </form>
    </div>
</div>

<script>
function openLockModal(id, username) {
    document.getElementById('modalUserId').value = id;
    document.getElementById('modalUsername').innerText = username;
    document.getElementById('lockModal').style.display = 'flex';
}
function closeLockModal() {
    document.getElementById('lockModal').style.display = 'none';
}
</script>

</div>
</body>
</html>
