<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<header style="background:#1877f2;color:white;padding:10px 20px;display:flex;justify-content:space-between;align-items:center;">
    <div style="display:flex;align-items:center;">
        <a href="/index.php" style="text-decoration:none;color:inherit;display:flex;align-items:center;">
            <img src="/assets/thumbnailTro.jpg" alt="Thuê Trọ Online" style="height:40px;object-fit:contain;margin-right:10px;">
            <h1 style="margin:0;font-size:20px;">Thuê Trọ Online</h1>
        </a>
    </div>
    <nav>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="/profile.php" style="color:white;text-decoration:none;margin-left:15px;font-weight:bold;">Profile</a>
            <a href="/logout.php" style="color:white;text-decoration:none;margin-left:15px;font-weight:bold;">Logout</a>
        <?php else: ?>
            <a href="/login.php" style="color:white;text-decoration:none;margin-left:15px;font-weight:bold;">Login</a>
            <a href="/register.php" style="color:white;text-decoration:none;margin-left:15px;font-weight:bold;">Register</a>
        <?php endif; ?>
    </nav>
</header>