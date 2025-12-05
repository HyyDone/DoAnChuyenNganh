<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function isActive($path, $current) {
    // Active: Light Cyan text (#84ffff), thick bottom border
    // Inactive: White text, transparent border
    return $path === $current 
        ? 'color:#84ffff;border-bottom:4px solid #84ffff;' 
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
            <div class="user-dropdown-container" style="position:relative; margin-left:15px;">
                <div onclick="toggleUserDropdown()" style="cursor:pointer; color:white; font-weight:bold; display:flex; align-items:center; background:rgba(255,255,255,0.2); padding:5px 10px; border-radius:20px; border:1px solid rgba(255,255,255,0.5);">
                    <img src="/<?= htmlspecialchars($avatar) ?>" alt="Avatar" style="width:30px; height:30px; border-radius:50%; margin-right:8px; object-fit:cover; border: 1px solid white;">
                    <?= htmlspecialchars($displayName) ?>
                    <i class="fa-solid fa-caret-down" style="margin-left: 8px;"></i>
                </div>
                <div id="userDropdown" style="display:none; position:absolute; right:0; top:45px; background:white; color:black; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.2); width:200px; z-index:1000; overflow:hidden;">
                    <a href="/profile.php" style="display:block; padding:10px 15px; text-decoration:none; color:#333; transition:background 0.2s;">
                        <i class="fa-solid fa-user" style="width:20px;"></i> Thông tin cá nhân
                    </a>
                    <a href="/manage_rentals.php" style="display:block; padding:10px 15px; text-decoration:none; color:#333; transition:background 0.2s;">
                        <i class="fa-solid fa-house-chimney" style="width:20px;"></i> Cho Thuê/Thuê Nhà
                    </a>
                    <div style="border-top:1px solid #eee;"></div>
                    <a href="/logout.php" style="display:block; padding:10px 15px; text-decoration:none; color:#dc3545; transition:background 0.2s;">
                        <i class="fa-solid fa-right-from-bracket" style="width:20px;"></i> Đăng xuất
                    </a>
                </div>
            </div>

            <script>
            function toggleUserDropdown() {
                const dd = document.getElementById('userDropdown');
                dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
            }
            // Close dropdown when clicking outside
            window.addEventListener('click', function(e) {
                if (!e.target.closest('.user-dropdown-container')) {
                    const dd = document.getElementById('userDropdown');
                    if (dd && dd.style.display === 'block') {
                        dd.style.display = 'none';
                    }
                }
            });
            </script>
        <?php else: ?>
            <a href="/login.php" style="<?= isActive('/login.php', $current_path) ?>text-decoration:none;margin-left:15px;font-weight:bold;height:100%;display:flex;align-items:center;padding:0 10px;box-sizing:border-box;">Login</a>
            <a href="/register.php" style="<?= isActive('/register.php', $current_path) ?>text-decoration:none;margin-left:15px;font-weight:bold;height:100%;display:flex;align-items:center;padding:0 10px;box-sizing:border-box;">Register</a>
        <?php endif; ?>
    </nav>
</header>