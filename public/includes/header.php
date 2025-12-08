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
            <div style="display:flex; align-items:center; margin-right: 15px;">
                <!-- Message Icon -->
                <div style="position:relative; margin-right:15px; cursor:pointer;" onclick="toggleMessageDropdown()">
                     <div style="width:40px; height:40px; background:rgba(255,255,255,0.2); border-radius:50%; display:flex; align-items:center; justify-content:center; transition:background 0.2s;">
                        <i class="fa-brands fa-facebook-messenger" style="font-size:20px;"></i>
                     </div>
                     <div id="messageBadge" class="unread-dot-indicator" style="display:none; width:20px; height:20px; background:red; border-radius:50%; position:absolute; top:-5px; right:-5px; font-size:12px; display:flex; align-items:center; justify-content:center; border:2px solid #1877f2;"></div>
                     
                     <!-- Dropdown for Messages -->
                     <div id="messageDropdown" class="dropdown-content" style="display:none; position:absolute; right:0; top:50px; background:white; width:360px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:1001; cursor:default;">
                        <div style="padding:16px; font-weight:bold; font-size:24px; color:black;">Chat</div>
                        <div class="conversation-list" style="max-height:400px; overflow-y:auto;">
                            <!-- Populated by JS -->
                        </div>
                        <div style="padding:10px; text-align:center; border-top:1px solid #ddd;">
                            <a href="#" style="text-decoration:none; color:var(--primary-color); font-weight:600;">Xem tất cả trong Messenger</a>
                        </div>
                     </div>
                </div>

                <!-- Notification Icon -->
                <div style="position:relative; cursor:pointer;" onclick="toggleNotifDropdown()">
                     <div style="width:40px; height:40px; background:rgba(255,255,255,0.2); border-radius:50%; display:flex; align-items:center; justify-content:center; transition:background 0.2s;">
                        <i class="fa-solid fa-bell" style="font-size:20px;"></i>
                     </div>
                     <div id="notifBadge" class="unread-dot-indicator" style="display:none; width:20px; height:20px; background:red; border-radius:50%; position:absolute; top:-5px; right:-5px; font-size:12px; display:flex; align-items:center; justify-content:center; border:2px solid #1877f2;"></div>

                     <!-- Notification Dropdown -->
                     <div id="notifDropdown" class="dropdown-content" style="display:none; position:absolute; right:0; top:50px; background:white; width:360px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:1001; cursor:default; max-height: 400px; overflow-y: auto;">
                        <div style="padding:16px; font-weight:bold; font-size:20px; color:black; border-bottom:1px solid #eee;">Thông báo</div>
                        <div id="notifList">
                            <!-- Populated by JS -->
                            <div style="padding:15px; text-align:center; color:#666;">Không có thông báo mới</div>
                        </div>
                     </div>
                </div>
            </div>

            <div class="user-dropdown-container" style="position:relative; margin-left:0;">
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
    // User Dropdown Logic
    function toggleUserDropdown() {
        const dd = document.getElementById('userDropdown');
        const notifDD = document.getElementById('notifDropdown');
        const msgDD = document.getElementById('messageDropdown');
        
        if (dd.style.display === 'none') {
            dd.style.display = 'block';
            if (notifDD) notifDD.style.display = 'none';
            if (msgDD) msgDD.style.display = 'none';
        } else {
            dd.style.display = 'none';
        }
    }

    // Notification Logic
    function toggleNotifDropdown() {
        const dd = document.getElementById('notifDropdown');
        const userDD = document.getElementById('userDropdown');
        const msgDD = document.getElementById('messageDropdown');
        
        if (dd.style.display === 'none') {
            dd.style.display = 'block';
            if (userDD) userDD.style.display = 'none';
            if (msgDD) msgDD.style.display = 'none';
            
            // Mark all badge as read visually (optional, or wait for click)
            // For now, valid requirement: click notification -> action.
            loadNotifications();
        } else {
            dd.style.display = 'none';
        }
    }

    async function loadNotifications() {
        try {
            const res = await fetch('/api/notifications.php?action=get');
            const data = await res.json();
            
            const badge = document.getElementById('notifBadge');
            if (data.unread_count > 0) {
                badge.style.display = 'flex';
                badge.innerText = data.unread_count > 9 ? '9+' : data.unread_count;
            } else {
                badge.style.display = 'none';
            }
            
            renderNotifications(data.notifications);
        } catch (e) {
            console.error(e);
        }
    }

    function renderNotifications(list) {
        const container = document.getElementById('notifList');
        if (!list || list.length === 0) {
            container.innerHTML = '<div style="padding:15px; text-align:center; color:#666;">Không có thông báo mới</div>';
            return;
        }
        
        container.innerHTML = '';
        list.forEach(notif => {
            const isUnread = notif.is_read == 0;
            const bg = isUnread ? '#e7f3ff' : 'white';
            
            // Determine link based on type
            let link = '#';
            if (notif.type === 'request_booking') {
                link = '/manage_rentals.php'; // Owner
            } else if (notif.type === 'booking_confirmed') {
                link = '/manage_rentals.php?view=my_rentals'; // Tenant
            }
            
            const html = `
                <div onclick="handleNotifClick(${notif.id}, '${link}')" style="padding:10px 15px; border-bottom:1px solid #eee; background:${bg}; cursor:pointer; display:flex; align-items:center; transition:background 0.2s;">
                    <div style="width:40px; height:40px; background:#1877f2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-right:12px; color:white; flex-shrink:0;">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <div>
                        <div style="font-size:14px; color:#050505;">${notif.message}</div>
                        <div style="font-size:12px; color:#65676b; margin-top:4px;">${formatTime(notif.created_at)}</div>
                    </div>
                    ${isUnread ? '<div style="width:10px; height:10px; background:#1877f2; border-radius:50%; margin-left:auto;"></div>' : ''}
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });
    }
    
    async function handleNotifClick(id, link) {
        // Mark read
        try {
            await fetch('/api/notifications.php?action=mark_read', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            });
        } catch(e) { console.error(e); }
        
        // Redirect
        if (link && link !== '#') {
            window.location.href = link;
        } else {
            // Just refresh list if no link
             loadNotifications();
        }
    }

    function formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    // Poll every 10 seconds
    setInterval(loadNotifications, 10000);
    // Initial load
    document.addEventListener('DOMContentLoaded', loadNotifications);
    
    // Close dropdowns on outside click
    window.addEventListener('click', function(e) {
        const userContainer = document.querySelector('.user-dropdown-container');
        const notifContainer = document.querySelector('.fa-bell').closest('div[onclick]');
        const msgContainer = document.querySelector('.fa-facebook-messenger').closest('div[onclick]');
        
        if (!e.target.closest('.user-dropdown-container') && 
            !e.target.closest('#notifDropdown') && 
            !e.target.closest('.fa-bell') && // Click on bell icon itself
             // Hacky check if click was on the parent div of bell
            (!notifContainer || !notifContainer.contains(e.target))) {
             
             const dd = document.getElementById('notifDropdown');
             if (dd && dd.style.display === 'block') dd.style.display = 'none';
        }
        
    });
    </script>
        <?php else: ?>
            <a href="/login.php" style="<?= isActive('/login.php', $current_path) ?>text-decoration:none;margin-left:15px;font-weight:bold;height:100%;display:flex;align-items:center;padding:0 10px;box-sizing:border-box;">Login</a>
            <a href="/register.php" style="<?= isActive('/register.php', $current_path) ?>text-decoration:none;margin-left:15px;font-weight:bold;height:100%;display:flex;align-items:center;padding:0 10px;box-sizing:border-box;">Register</a>
        <?php endif; ?>
    </nav>
</header>