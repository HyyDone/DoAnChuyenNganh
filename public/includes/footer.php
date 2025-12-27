<?php if (basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
<footer style="
    background:#1877f2;
    color: white;
    text-align: center;
    padding: 20px 0;
    margin-top: 30px;
    font-size: 14px;
">
    <p>&copy; <?= date('Y') ?> Thuê Trọ Online. All rights reserved.</p>
    <p>
        <a href="#" style="color:white;text-decoration:none;margin:0 5px;">About</a> |
        <a href="#" style="color:white;text-decoration:none;margin:0 5px;">Contact</a> |
        <a href="#" style="color:white;text-decoration:none;margin:0 5px;">Privacy</a>
    </p>
</footer>
<?php endif; ?>

<link rel="stylesheet" href="/assets/css/chat.css?v=<?= time() ?>">
<script>
    window.currentUserId = <?= json_encode($_SESSION['user_id'] ?? 0) ?>;
    window.isUserLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>

<div class="chat-widget-container">
    <div class="chat-popup" id="chatPopup">
        <div class="chat-header" onclick="closeChat()">
            <div class="chat-user-info">
                <img src="/assets/default-avatar.png" class="chat-user-avatar" id="chatUserAvatar">
                <span id="chatUserName">User Name</span>
            </div>
            <div class="chat-controls">
                <i class="fa-solid fa-xmark" onclick="closeChat()"></i>
            </div>
        </div>
        <div class="chat-body" id="chatBody">
        </div>
        <div class="chat-footer">
            <label for="chatImageInput" class="chat-upload-label" title="Gửi ảnh">
                <i class="fa-solid fa-image"></i>
            </label>
            <input type="file" id="chatImageInput" accept="image/*" style="display:none">
            
            <input type="text" class="chat-input" id="humanChatInput" placeholder="Nhập tin nhắn..." onkeydown="handleChatKey(event)">
            <button class="chat-send-btn" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script src="/assets/js/chat.js?v=<?= time() ?>"></script>