<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT username, full_name, avatar FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Cộng đồng Thuê Trọ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- FontAwesome cho icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #f0f2f5;
            --card-bg: #ffffff;
            --primary-color: #0866ff;
            --text-color: #050505;
            --secondary-text: #65676b;
            --divider: #ced0d4;
            --hover-bg: #f2f2f2;
        }

        body {
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            overflow-y: scroll;
        }

        /* Layout chính */
        .main-container {
            display: flex;
            justify-content: center;
            padding-top: 20px;
            min-height: 100vh;
        }

        .feed-column {
            width: 100%;
            max-width: 680px;
            padding: 0 16px;
        }

        /* Card chung */
        .card {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            margin-bottom: 16px;
            padding: 12px 16px;
        }

        /* Thanh đăng bài (Status Bar) */
        .create-post-bar {
            display: flex;
            align-items: center;
            padding: 12px 16px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
            cursor: pointer;
        }

        .status-input-trigger {
            background: #f0f2f5;
            border-radius: 20px;
            padding: 8px 12px;
            flex-grow: 1;
            cursor: pointer;
            color: var(--secondary-text);
            font-size: 1.05rem;
            transition: background 0.2s;
        }

        .status-input-trigger:hover {
            background: #e4e6eb;
        }

        .action-buttons {
            display: flex;
            border-top: 1px solid var(--divider);
            margin-top: 12px;
            padding-top: 8px;
        }

        .action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            color: var(--secondary-text);
            font-weight: 600;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .action-btn:hover {
            background: var(--hover-bg);
        }

        .action-btn i {
            margin-right: 8px;
            font-size: 1.2rem;
        }

        /* Modal Popup */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(244, 244, 244, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: white;
            width: 100%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid var(--divider);
            position: relative;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .close-btn {
            position: absolute;
            right: 16px;
            top: 12px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e4e6eb;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            color: var(--secondary-text);
        }

        .close-btn:hover {
            background: #d8dadf;
        }

        .modal-body {
            padding: 16px;
        }

        .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .post-input {
            width: 100%;
            border: none;
            resize: none;
            font-size: 1.1rem;
            min-height: 150px;
            font-family: inherit;
            outline: none;
        }

        .modal-footer {
            padding: 16px;
            border-top: 1px solid var(--divider); /* Optional, FB doesn't always have this */
        }

        .post-submit-btn {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
        }

        .post-submit-btn:disabled {
            background: #e4e6eb;
            color: #bcc0c4;
            cursor: not-allowed;
        }

        /* Bài viết (Post) */
        .post {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            margin-bottom: 16px;
            padding: 12px 0;
        }

        .post-header {
            display: flex;
            align-items: center;
            padding: 0 16px 12px;
            margin-bottom: 0;
        }

        .post-info h4 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .post-info h4 a {
            color: var(--text-color);
            text-decoration: none;
        }

        .post-info h4 a:hover {
            text-decoration: underline;
        }

        .post-time {
            font-size: 0.8rem;
            color: var(--secondary-text);
            display: block;
            margin-top: 2px;
        }

        .post-content {
            padding: 4px 16px 16px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .post-actions {
            border-top: 1px solid var(--divider);
            margin: 0 16px;
            padding-top: 4px;
            display: flex;
        }
        
        /* Loading skeleton */
        .skeleton {
            background: #e4e6eb;
            border-radius: 4px;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        .skeleton-text { height: 12px; margin-bottom: 8px; width: 100%; }
        .skeleton-avatar { width: 40px; height: 40px; border-radius: 50%; }

        /* Image Upload & Preview */
        .modal-actions {
            padding: 8px 16px;
            border-top: 1px solid var(--divider);
        }

        .add-image-btn {
            display: inline-flex;
            align-items: center;
            color: #45bd62;
            font-weight: 600;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .add-image-btn:hover {
            background: var(--hover-bg);
        }

        .add-image-btn i {
            margin-right: 8px;
            font-size: 1.2rem;
        }

        .image-preview-container {
            padding: 0 16px 16px;
            position: relative;
            display: none;
        }

        .image-preview-container.active {
            display: block;
        }

        .image-preview {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--divider);
        }

        .remove-image-btn {
            position: absolute;
            top: 8px;
            right: 24px;
            background: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .post-image {
            width: 100%;
            height: auto;
            display: block;
            margin-top: 12px;
            border-top: 1px solid var(--divider);
            border-bottom: 1px solid var(--divider);
        }

        .comment-input-box {
            width: 100%;
            background: #f0f2f5;
            border: none;
            border-radius: 18px;
            padding: 8px 12px;
            outline: none;
            font-size: 0.95rem;
        }
        /* Sidebar Right */
        .right-sidebar {
            width: 360px;
            padding: 0 16px;
            display: none; /* Hidden on small screens */
        }

        @media (min-width: 900px) {
            .right-sidebar {
                display: block;
            }
        }

        /* AI Chat Box */
        .ai-chat-box {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 80px; /* Adjust based on header height */
            height: calc(100vh - 100px);
            display: flex;
            flex-direction: column;
        }

        .ai-chat-header {
            padding: 12px 16px;
            border-bottom: 1px solid var(--divider);
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .ai-chat-header i {
            margin-right: 8px;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .chat-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .chat-message {
            max-width: 80%;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .message-bot {
            align-self: flex-start;
            background: #e4e6eb;
            color: var(--text-color);
            border-bottom-left-radius: 4px;
        }

        .message-user {
            align-self: flex-end;
            background: var(--primary-color);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .chat-input-area {
            padding: 12px;
            border-top: 1px solid var(--divider);
            display: flex;
            gap: 8px;
        }

        .chat-input {
            flex: 1;
            border: 1px solid var(--divider);
            border-radius: 20px;
            padding: 8px 12px;
            outline: none;
            font-family: inherit;
        }

        .chat-send-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0 8px;
        }
        
        .chat-send-btn:hover {
            opacity: 0.8;
        }

        /* Chat Image Preview */
        .chat-image-preview-box {
            position: relative;
            padding: 8px 12px;
            border-top: 1px solid var(--divider);
            display: none;
        }
        .chat-image-preview-box.active {
            display: block;
        }
        .chat-preview-img {
            height: 60px;
            border-radius: 4px;
        }
        .chat-preview-remove {
            position: absolute;
            top: 4px;
            left: 65px; /* Position next to image */
            background: rgba(0,0,0,0.5);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 20px;
            font-size: 12px;
            cursor: pointer;
        }
        .chat-upload-btn {
            color: var(--primary-color);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0 8px;
            display: flex;
            align-items: center;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="main-container">
        <div class="feed-column">
            <!-- Thanh đăng bài -->
            <div class="card create-post-bar">
                <img src="/<?= htmlspecialchars($currentUser['avatar'] ?? 'assets/default-avatar.png') ?>" class="user-avatar" alt="Avatar">
                <div class="status-input-trigger" onclick="openModal()">
                    Bạn đang nghĩ gì thế?
                </div>
            </div>

            <!-- Danh sách bài viết -->
            <div id="feed-container">
                <!-- Loading state -->
                <div class="card" style="padding: 16px;">
                    <div style="display:flex; gap:12px; margin-bottom: 12px;">
                        <div class="skeleton skeleton-avatar"></div>
                        <div style="flex:1">
                            <div class="skeleton skeleton-text" style="width: 30%"></div>
                            <div class="skeleton skeleton-text" style="width: 20%"></div>
                        </div>
                    </div>
                    <div class="skeleton skeleton-text"></div>
                    <div class="skeleton skeleton-text"></div>
                    <div class="skeleton skeleton-text" style="width: 80%"></div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar (AI Chat) -->
        <div class="right-sidebar">
            <div class="ai-chat-box">
                <div class="ai-chat-header">
                    <i class="fa-solid fa-robot"></i>
                    Trợ lý AI
                </div>
                <div class="chat-messages" id="chatMessages">
                    <div class="chat-message message-bot">Xin chào! Tôi là trợ lý ảo. Bạn cần giúp gì về việc tìm trọ không?</div>
                </div>
                <div class="chat-image-preview-box" id="aiChatPreviewBox">
                    <img src="" class="chat-preview-img" id="aiChatPreviewImg">
                    <div class="chat-preview-remove" onclick="removeChatImage()">&times;</div>
                </div>
                <div class="chat-input-area">
                    <label for="aiChatFile" class="chat-upload-btn">
                        <i class="fa-solid fa-image"></i>
                    </label>
                    <input type="file" id="aiChatFile" hidden accept="image/*" onchange="previewChatImage(this)">
                    
                    <input type="text" class="chat-input" id="chatInput" placeholder="Nhập tin nhắn..." onkeypress="handleChatKey(event)">
                    <button class="chat-send-btn" onclick="sendChatMessage()">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Đăng bài -->
    <div class="modal-overlay" id="postModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Tạo bài viết</h3>
                <div class="close-btn" onclick="closeModal()">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </div>
            <div class="modal-body">
                <div class="user-info">
                    <img src="/<?= htmlspecialchars($currentUser['avatar'] ?? 'assets/default-avatar.png') ?>" class="user-avatar" alt="Avatar">
                    <div class="user-name"><?= htmlspecialchars($currentUser['full_name'] ?: ($currentUser['username'] ?? 'Người dùng')) ?></div>
                </div>
                <textarea class="post-input" id="postContent" placeholder="Bạn đang nghĩ gì thế?"></textarea>
            </div>
            
            <div class="image-preview-container" id="imagePreviewContainer">
                <div class="remove-image-btn" onclick="removeImage()">
                    <i class="fa-solid fa-xmark"></i>
                </div>
                <img src="" alt="Preview" class="image-preview" id="imagePreview">
            </div>

            <div class="modal-actions">
                <label for="imageInput" class="add-image-btn">
                    <i class="fa-solid fa-image"></i> Ảnh/Video
                </label>
                <input type="file" id="imageInput" hidden accept="image/*" onchange="previewImage(this)">
            </div>

            <div class="modal-footer">
                <button class="post-submit-btn" id="submitBtn" onclick="submitPost()" disabled>Đăng</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        // DOM Elements
        const modal = document.getElementById('postModal');
        const feedContainer = document.getElementById('feed-container');
        const postContent = document.getElementById('postContent');
        const submitBtn = document.getElementById('submitBtn');
        const imageInput = document.getElementById('imageInput');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        const imagePreview = document.getElementById('imagePreview');

        // Modal functions
        function openModal() {
            modal.classList.add('active');
            postContent.focus();
        }

        function closeModal() {
            modal.classList.remove('active');
            resetModalState();
        }

        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Enable/Disable submit button
        postContent.addEventListener('input', validateInput);

        function validateInput() {
            // In edit mode, if we have an existing image (preview active) or content, it's valid
            const hasContent = postContent.value.trim().length > 0;
            const hasNewImage = imageInput.files.length > 0;
            const hasExistingImage = isEditing && imagePreviewContainer.classList.contains('active');
            
            if (hasContent || hasNewImage || hasExistingImage) {
                submitBtn.removeAttribute('disabled');
            } else {
                submitBtn.setAttribute('disabled', 'true');
            }
        }

        // Image Preview
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.classList.add('active');
                    removeOldImageFlag = false; // New image replaces old, so we don't need to explicitly remove old one via flag (backend handles replacement)
                    validateInput();
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeImage() {
            imageInput.value = '';
            imagePreview.src = '';
            imagePreviewContainer.classList.remove('active');
            if (isEditing) {
                removeOldImageFlag = true;
            }
            validateInput();
        }

        // Global state for editing
        let isEditing = false;
        let editingPostId = null;
        let removeOldImageFlag = false;

        // Fetch posts
        async function loadPosts() {
            console.log('Starting loadPosts...');
            try {
                const response = await fetch('/api/posts.php');
                console.log('Response status:', response.status);
                const posts = await response.json();
                console.log('Posts fetched:', posts);
                renderPosts(posts);
            } catch (error) {
                console.error('Error loading posts:', error);
                feedContainer.innerHTML = `<p style="text-align:center; padding: 20px;">Không thể tải bài viết. Lỗi: ${error.message}</p>`;
            }
        }

        function togglePostMenu(postId) {
            const menu = document.getElementById(`post-menu-${postId}`);
            // Close all other menus
            document.querySelectorAll('.post-menu-dropdown').forEach(el => {
                if (el.id !== `post-menu-${postId}`) el.style.display = 'none';
            });
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }

        // Close menus when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.post-menu-container')) {
                document.querySelectorAll('.post-menu-dropdown').forEach(el => el.style.display = 'none');
            }
        });

        function confirmDeletePost(postId) {
            if (confirm('Bạn có chắc chắn muốn xóa bài viết này không? Hành động này không thể hoàn tác.')) {
                deletePost(postId);
            }
        }

        async function deletePost(postId) {
            try {
                const response = await fetch(`/api/posts.php?action=delete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: postId })
                });
                const result = await response.json();
                if (result.ok) {
                    // Remove post from UI
                    document.getElementById(`post-${postId}`).remove();
                } else {
                    alert('Không thể xóa bài viết: ' + (result.error || 'Lỗi không xác định'));
                }
            } catch (error) {
                console.error('Error deleting post:', error);
                alert('Có lỗi xảy ra khi xóa bài viết.');
            }
        }

        function editPost(post) {
            isEditing = true;
            editingPostId = post.id;
            removeOldImageFlag = false;
            
            // Fill modal
            postContent.value = post.content;
            document.querySelector('.modal-header h3').innerText = 'Chỉnh sửa bài viết';
            submitBtn.innerText = 'Lưu';
            
            if (post.image) {
                imagePreview.src = '/' + post.image;
                imagePreviewContainer.classList.add('active');
            } else {
                removeImage();
            }
            
            openModal();
            validateInput();
        }

        function resetModalState() {
            isEditing = false;
            editingPostId = null;
            removeOldImageFlag = false;
            document.querySelector('.modal-header h3').innerText = 'Tạo bài viết';
            submitBtn.innerText = 'Đăng';
            postContent.value = '';
            removeImage();
        }


        function renderPosts(posts) {
            feedContainer.innerHTML = '';
            
            if (posts.length === 0) {
                feedContainer.innerHTML = '<p style="text-align:center; padding: 20px;">Chưa có bài viết nào.</p>';
                return;
            }

            posts.forEach(post => {
                const isLiked = post.is_liked > 0;
                const likeClass = isLiked ? 'active' : '';
                const likeIcon = isLiked ? 'fa-solid fa-thumbs-up' : 'fa-regular fa-thumbs-up';
                const likeColor = isLiked ? 'color: var(--primary-color);' : '';
                const displayName = post.full_name ? post.full_name : post.username;
                // Check if current user owns the post
                const isOwner = post.user_id == <?= json_encode($_SESSION['user_id'] ?? 0) ?>;
                const menuHtml = isOwner ? `
                    <div class="post-menu-container" style="position: relative; margin-left: auto;">
                        <div class="post-menu-trigger" onclick="togglePostMenu(${post.id})" style="cursor: pointer; padding: 8px;">
                            <i class="fa-solid fa-ellipsis"></i>
                        </div>
                        <div class="post-menu-dropdown" id="post-menu-${post.id}" style="display: none; position: absolute; right: 0; top: 100%; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.15); border-radius: 8px; z-index: 10; min-width: 150px; overflow: hidden;">
                            <div class="menu-item" onclick="editPost(${JSON.stringify(post).replace(/"/g, '&quot;')})" style="padding: 8px 16px; cursor: pointer; transition: background 0.2s;">
                                <i class="fa-solid fa-pen" style="margin-right: 8px;"></i> Chỉnh sửa
                            </div>
                            <div class="menu-item" onclick="confirmDeletePost(${post.id})" style="padding: 8px 16px; cursor: pointer; transition: background 0.2s; color: #dc3545;">
                                <i class="fa-solid fa-trash" style="margin-right: 8px;"></i> Xóa
                            </div>
                        </div>
                    </div>
                ` : '';

                const html = `
                    <div class="post" id="post-${post.id}">
                        <div class="post-header">
                            <img src="/${post.avatar || 'assets/default-avatar.png'}" class="user-avatar" alt="${post.username}">
                            <div class="post-info">
                                <h4><a href="/profile.php?id=${post.user_id}">${escapeHtml(displayName)}</a></h4>
                                <span class="post-time">${formatDate(post.created_at)}</span>
                            </div>
                            ${menuHtml}
                        </div>
                        <div class="post-content">
                            ${nl2br(escapeHtml(post.content))}
                        </div>
                        ${post.image ? `<img src="/${post.image}" class="post-image" alt="Post Image">` : ''}
                        
                        <div class="post-stats" style="padding: 0 16px 8px; font-size: 0.9rem; color: var(--secondary-text); display: flex; justify-content: space-between;">
                            <span id="like-count-${post.id}">${post.like_count > 0 ? post.like_count + ' lượt thích' : ''}</span>
                            <span onclick="toggleComments(${post.id})" style="cursor: pointer;">${post.comment_count > 0 ? post.comment_count + ' bình luận' : ''}</span>
                        </div>

                        <div class="post-actions">
                            <div class="action-btn ${likeClass}" style="${likeColor}" onclick="toggleLike(${post.id}, this)">
                                <i class="${likeIcon}"></i> Thích
                            </div>
                            <div class="action-btn" onclick="toggleComments(${post.id})">
                                <i class="fa-regular fa-comment"></i> Bình luận
                            </div>
                            <div class="action-btn">
                                <i class="fa-solid fa-share"></i> Chia sẻ
                            </div>
                        </div>

                        <div class="comments-section" id="comments-${post.id}" style="display: none; padding: 0 16px 16px;">
                            <div class="comment-input-area" style="display: flex; gap: 8px; margin-top: 12px;">
                                <img src="/<?= htmlspecialchars(($currentUser && isset($currentUser['avatar'])) ? $currentUser['avatar'] : 'assets/default-avatar.png') ?>" class="user-avatar" style="width: 32px; height: 32px;">
                                <div style="flex: 1; position: relative;">
                                    <input type="text" id="comment-input-${post.id}" class="comment-input-box" placeholder="Viết bình luận..." 
                                        onkeydown="handleCommentKey(event, ${post.id})">
                                </div>
                            </div>
                            <div class="comments-list" id="comments-list-${post.id}" style="margin-top: 12px;"></div>
                        </div>
                    </div>
                `;
                feedContainer.insertAdjacentHTML('beforeend', html);
            });
        }

        // Social Actions
        async function toggleLike(postId, btn) {
            try {
                const response = await fetch('/api/interact.php?action=like', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ post_id: postId })
                });
                const result = await response.json();
                
                if (result.status) {
                    const isLiked = result.status === 'liked';
                    const icon = btn.querySelector('i');
                    const countSpan = document.getElementById(`like-count-${postId}`);
                    let count = parseInt(countSpan.innerText) || 0;

                    if (isLiked) {
                        btn.style.color = 'var(--primary-color)';
                        icon.classList.remove('fa-regular');
                        icon.classList.add('fa-solid');
                        count++;
                    } else {
                        btn.style.color = '';
                        icon.classList.remove('fa-solid');
                        icon.classList.add('fa-regular');
                        count--;
                    }
                    countSpan.innerText = count > 0 ? count + ' lượt thích' : '';
                }
            } catch (error) {
                console.error('Error liking post:', error);
            }
        }

        async function toggleComments(postId) {
            const section = document.getElementById(`comments-${postId}`);
            const list = document.getElementById(`comments-list-${postId}`);
            
            if (section.style.display === 'none') {
                section.style.display = 'block';
                if (list.innerHTML === '') {
                    loadComments(postId);
                }
            } else {
                section.style.display = 'none';
            }
        }

        async function loadComments(postId) {
            const list = document.getElementById(`comments-list-${postId}`);
            list.innerHTML = '<div class="skeleton skeleton-text" style="width: 50%"></div>';
            
            try {
                const response = await fetch(`/api/interact.php?action=comments&post_id=${postId}`);
                const comments = await response.json();
                renderComments(postId, comments);
            } catch (error) {
                console.error('Error loading comments:', error);
                list.innerHTML = 'Không thể tải bình luận.';
            }
        }

        function renderComments(postId, comments) {
            const list = document.getElementById(`comments-list-${postId}`);
            list.innerHTML = '';
            
            if (comments.length === 0) return;

            comments.forEach(comment => {
                list.appendChild(createCommentElement(comment, 0, postId));
            });
        }

        function createCommentElement(comment, depth, postId) {
            const wrapper = document.createElement('div');
            wrapper.className = 'comment-wrapper';
            wrapper.id = `comment-${comment.id}`;
            wrapper.style.marginBottom = '8px';
            
            let marginLeft = 0;
            if (depth > 0 && depth <= 2) {
                marginLeft = 32;
            }
            if (marginLeft > 0) wrapper.style.marginLeft = marginLeft + 'px';

            const displayName = comment.full_name ? comment.full_name : comment.username;
            
            wrapper.innerHTML = `
                <div style="display: flex; gap: 8px;">
                    <img src="/${comment.avatar || 'assets/default-avatar.png'}" class="user-avatar" style="width: 32px; height: 32px;">
                    <div style="flex: 1;">
                        <div style="background: #f0f2f5; padding: 8px 12px; border-radius: 18px; display: inline-block;">
                            <div style="font-weight: 600; font-size: 0.9rem;"><a href="/profile.php?id=${comment.user_id}" style="color:inherit;text-decoration:none;">${escapeHtml(displayName)}</a></div>
                            <div style="font-size: 0.95rem;">${escapeHtml(comment.content)}</div>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--secondary-text); margin-top: 2px; margin-left: 4px;">
                            <span style="cursor: pointer; font-weight: 600;" onclick="showReplyBox(${postId}, ${comment.id}, '${escapeHtml(displayName)}')">Phản hồi</span>
                            · ${formatDate(comment.created_at)}
                        </div>
                    </div>
                </div>
                <div class="reply-box-container" id="reply-box-container-${comment.id}" style="margin-left: 40px;"></div>
                <div class="replies-list" id="replies-${comment.id}"></div>
            `;

            if (comment.replies && comment.replies.length > 0) {
                const repliesContainer = wrapper.querySelector(`#replies-${comment.id}`);
                comment.replies.forEach(reply => {
                    repliesContainer.appendChild(createCommentElement(reply, depth + 1, postId));
                });
            }

            return wrapper;
        }

        function showReplyBox(postId, commentId, username) {
            // Remove existing reply box if any
            const existingBox = document.getElementById(`reply-box-${postId}`);
            if (existingBox) existingBox.remove();

            const container = document.getElementById(`reply-box-container-${commentId}`);
            
            const replyBox = document.createElement('div');
            replyBox.id = `reply-box-${postId}`;
            replyBox.style.marginTop = '8px';
            replyBox.style.display = 'flex';
            replyBox.style.gap = '8px';
            
            replyBox.innerHTML = `
                <img src="/<?= htmlspecialchars(($currentUser && isset($currentUser['avatar'])) ? $currentUser['avatar'] : 'assets/default-avatar.png') ?>" class="user-avatar" style="width: 24px; height: 24px;">
                <div style="flex: 1;">
                    <input type="text" id="inline-reply-input-${commentId}" class="comment-input-box" 
                        placeholder="Trả lời ${escapeHtml(username)}..." 
                        style="font-size: 0.9rem; padding: 6px 10px;"
                        onkeydown="handleInlineReply(event, ${postId}, ${commentId})">
                    <div style="font-size: 0.8rem; margin-top: 4px; color: var(--secondary-text);">
                        Nhấn Enter để đăng. <span style="cursor: pointer; color: var(--primary-color);" onclick="this.closest('#reply-box-${postId}').remove()">Hủy</span>
                    </div>
                </div>
            `;
            
            container.appendChild(replyBox);
            
            const input = document.getElementById(`inline-reply-input-${commentId}`);
            input.focus();
            input.value = `@${username} `;
        }

        async function handleInlineReply(event, postId, parentId) {
            if (event.key === 'Enter') {
                const input = event.target;
                const content = input.value.trim();
                if (!content) return;
                
                try {
                    const response = await fetch('/api/interact.php?action=comment', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            post_id: postId, 
                            content: content,
                            parent_id: parentId
                        })
                    });
                    const result = await response.json();
                    
                    if (result.ok) {
                        input.closest(`#reply-box-${postId}`).remove();
                        loadComments(postId); // Reload to show new comment
                    }
                } catch (error) {
                    console.error('Error posting reply:', error);
                }
            }
        }

        async function handleCommentKey(event, postId) {
            if (event.key === 'Enter') {
                const input = event.target;
                const content = input.value.trim();
                if (!content) return;
                
                try {
                    const response = await fetch('/api/interact.php?action=comment', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            post_id: postId, 
                            content: content
                        })
                    });
                    const result = await response.json();
                    
                    if (result.ok) {
                        input.value = '';
                        loadComments(postId);
                    }
                } catch (error) {
                    console.error('Error posting comment:', error);
                }
            }
        }

        // Submit post
        async function submitPost() {
            const content = postContent.value.trim();
            const hasImage = imageInput.files.length > 0;
            // For editing, we might have an existing image shown in preview but no new file
            const hasExistingImage = isEditing && imagePreviewContainer.classList.contains('active') && !hasImage;

            if (!content && !hasImage && !hasExistingImage) return;

            // Disable button while submitting
            submitBtn.setAttribute('disabled', 'true');
            submitBtn.innerText = isEditing ? 'Đang lưu...' : 'Đang đăng...';

            try {
                const formData = new FormData();
                formData.append('content', content);
                if (hasImage) {
                    formData.append('image', imageInput.files[0]);
                }
                
                if (isEditing) {
                    formData.append('id', editingPostId);
                    if (removeOldImageFlag) {
                        formData.append('remove_image', 'true');
                    }
                }

                const response = await fetch('/api/posts.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.ok) {
                    closeModal();
                    loadPosts(); // Reload feed
                } else {
                    if (result.error === 'Unauthorized') {
                        alert('Bạn cần đăng nhập để thực hiện thao tác này!');
                        window.location.href = '/login.php';
                    } else {
                        alert('Có lỗi xảy ra: ' + result.error);
                    }
                }
            } catch (error) {
                console.error('Error submitting post:', error);
                alert('Không thể kết nối đến server.');
            } finally {
                submitBtn.innerText = isEditing ? 'Lưu' : 'Đăng';
                validateInput();
            }
        }

        // --- AI Chat Logic ---
        const chatInput = document.getElementById('chatInput');
        const chatMessages = document.getElementById('chatMessages');
        const aiChatFile = document.getElementById('aiChatFile');
        const aiChatPreviewBox = document.getElementById('aiChatPreviewBox');
        const aiChatPreviewImg = document.getElementById('aiChatPreviewImg');
        
        // Client-side Context Memory (Reset on reload)
        let chatContext = [];

        function handleChatKey(e) {
            if (e.key === 'Enter') {
                sendChatMessage();
            }
        }
        
        function previewChatImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    aiChatPreviewImg.src = e.target.result;
                    aiChatPreviewBox.classList.add('active');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function removeChatImage() {
            aiChatFile.value = '';
            aiChatPreviewImg.src = '';
            aiChatPreviewBox.classList.remove('active');
        }

        async function sendChatMessage() {
            const message = chatInput.value.trim();
            const hasImage = aiChatFile.files.length > 0;
            
            if (!message && !hasImage) return;

            // Add user message to UI
            let userDisplay = message;
            if (hasImage) {
                userDisplay += '<br><small><i>[Đã gửi 1 ảnh]</i></small>';
            }
            addMessageToChat(userDisplay, 'user');

            // Add to Context (Text only for now as pure text history)
            chatContext.push({ sender: 'user', text: message + (hasImage ? " [User sent an image]" : "") });

            // Clear input
            chatInput.value = '';
            
            // Prepare FormData
            const formData = new FormData();
            formData.append('message', message);
            if (hasImage) {
                formData.append('image', aiChatFile.files[0]);
            }
            formData.append('history', JSON.stringify(chatContext));

            // Clean up preview
            removeChatImage();

            // Show loading placeholder
            const loadingId = addMessageToChat('<i class="fa-solid fa-ellipsis fa-fade"></i>', 'bot');

            try {
                const response = await fetch('/api/ai_chat.php', {
                    method: 'POST',
                    body: formData // Fetch automatically sets Content-Type to multipart/form-data
                });
                const result = await response.json();

                // Remove loading
                document.getElementById(loadingId).remove();

                if (result.success) {
                    addMessageToChat(result.response, 'bot');
                    
                    // Add AI response to context
                    chatContext.push({ sender: 'bot', text: result.response });

                    // Handle Actions
                    if (result.action_performed === 'post_created') {
                        console.log('Post created by AI, reloading feed...');
                        loadPosts(); // Refresh feed
                    }
                } else {
                    addMessageToChat("Lỗi: " + result.message, 'bot');
                }
            } catch (error) {
                console.error('Chat error:', error);
                document.getElementById(loadingId).remove();
                addMessageToChat("Xin lỗi, tôi đang gặp sự cố kết nối.", 'bot');
            }
        }

        function addMessageToChat(html, sender) {
            const div = document.createElement('div');
            div.className = `chat-message message-${sender}`;
            div.id = 'msg-' + Date.now();
            div.innerHTML = html;
            chatMessages.appendChild(div);
            // Auto scroll
            requestAnimationFrame(() => {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });
            return div.id;
        }        // ---------------------

        // Utilities
        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function nl2br(str) {
            if (!str) return '';
            return str.replace(/\n/g, '<br>');
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffSeconds = Math.floor((now - date) / 1000);

            if (diffSeconds < 60) return 'Vừa xong';
            if (diffSeconds < 3600) return Math.floor(diffSeconds / 60) + ' phút trước';
            if (diffSeconds < 86400) return Math.floor(diffSeconds / 3600) + ' giờ trước';
            if (diffSeconds < 604800) return Math.floor(diffSeconds / 86400) + ' ngày trước';
            
            return date.toLocaleDateString('vi-VN');
        }

        // Init
        document.addEventListener('DOMContentLoaded', loadPosts);
    </script>
</body>

</html>
```