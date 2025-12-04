<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function isActive($path, $current) {
    // Active: Light blue text, thick bottom border
    // Inactive: White text, transparent border
    return $path === $current 
        ? 'color:#e7f3ff;border-bottom:4px solid #e7f3ff;' 
        : 'color:white;border-bottom:4px solid transparent;';
}
?>
<header style="background:#1877f2;color:white;padding:0 20px;height:60px;display:flex;justify-content:space-between;align-items:center;">
    <div style="display:flex;align-items:center;height:100%;">
        <a href="/index.php" style="text-decoration:none;color:inherit;display:flex;align-items:center;margin-right:20px;">
            <img src="/assets/thumbnailTro.jpg" alt="Thuê Trọ Online" style="height:40px;object-fit:contain;margin-right:10px;">
            <h1 style="margin:0;font-size:20px;">Thuê Trọ Online</h1>
        </a>
        <a href="/listings.php" style="<?= isActive('/listings.php', $current_path) ?>text-decoration:none;font-weight:bold;font-size:16px;height:100%;display:flex;align-items:center;padding:0 10px;box-sizing:border-box;">Thuê Trọ</a>
    </div>
    <nav style="display:flex;align-items:center;height:100%;">
        <?php if (!empty($_SESSION['user_id'])): 
            require_once __DIR__ . '/../../config/db.php';
            $stmt = $pdo->prepare("SELECT username, full_name, avatar FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $headerUser = $stmt->fetch();
            
            $displayName = $headerUser['username'] ?? 'User';
            if (!empty($headerUser['full_name'])) {
                $parts = explode(' ', trim($headerUser['full_name']));
                $displayName = end($parts);
            }
            $avatar = !empty($headerUser['avatar']) ? $headerUser['avatar'] : 'assets/default-avatar.png';
        ?>
            <a href="/profile.php" style="color:white;text-decoration:none;margin-left:15px;font-weight:bold;display:flex;align-items:center;background:rgba(255,255,255,0.2);padding:5px 10px;border-radius:20px;border:1px solid rgba(255,255,255,0.5);">
                <img src="/<?= htmlspecialchars($avatar) ?>" alt="Avatar" style="width:30px;height:30px;border-radius:50%;margin-right:8px;object-fit:cover;border: 1px solid white;">
                <?= htmlspecialchars($displayName) ?>
            </a>
            <a href="/logout.php" style="color:white;text-decoration:none;margin-left:15px;font-weight:bold;">Logout</a>
        <?php else: ?>
            <a href="/login.php" style="<?= isActive('/login.php', $current_path) ?>text-decoration:none;margin-left:15px;font-weight:bold;height:100%;display:flex;align-items:center;padding:0 10px;box-sizing:border-box;">Login</a>
            <a href="/register.php" style="<?= isActive('/register.php', $current_path) ?>text-decoration:none;margin-left:15px;font-weight:bold;height:100%;display:flex;align-items:center;padding:0 10px;box-sizing:border-box;">Register</a>
        <?php endif; ?>
    </nav>
</header>