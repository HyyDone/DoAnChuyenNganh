let currentChatUserId = null;
let chatPollingInterval = null;
const NOTIFICATION_POLL_INTERVAL = 10000;
const CHAT_POLL_INTERVAL = 3000;

document.addEventListener('DOMContentLoaded', () => {
    if (window.isUserLoggedIn) {
        pollUnreadMessages();
        setInterval(pollUnreadMessages, NOTIFICATION_POLL_INTERVAL);
    }
});

function openChat(userId, userFullname = '', userAvatar = '') {
    const chatPopup = document.getElementById('chatPopup');

    if (chatPopup.classList.contains('active') && currentChatUserId === userId) {
        document.getElementById('chatInput').focus();
        return;
    }

    currentChatUserId = userId;

    const displayName = userFullname || 'User';
    document.getElementById('chatUserName').innerText = displayName;
    document.getElementById('chatUserAvatar').src = userAvatar ? '/' + userAvatar : '/assets/default-avatar.png';

    chatPopup.style.display = 'flex';
    setTimeout(() => chatPopup.classList.add('active'), 10);

    loadChatHistory(userId);

    if (chatPollingInterval) clearInterval(chatPollingInterval);
    chatPollingInterval = setInterval(() => loadChatHistory(userId, true), CHAT_POLL_INTERVAL);

    document.getElementById('chatInput').focus();
}

function closeChat() {
    const chatPopup = document.getElementById('chatPopup');
    chatPopup.classList.remove('active');
    setTimeout(() => chatPopup.style.display = 'none', 200);

    currentChatUserId = null;
    if (chatPollingInterval) clearInterval(chatPollingInterval);
}

async function loadChatHistory(userId, appendOnly = false) {
    try {
        const response = await fetch(`/api/messages.php?action=history&user_id=${userId}`);
        const messages = await response.json();

        const chatBody = document.getElementById('chatBody');

        if (appendOnly) {
        }

        renderMessages(messages);

        if (!appendOnly) {
            scrollToBottom();
        }

        pollUnreadMessages();

    } catch (error) {
        console.error('Error loading chat:', error);
    }
}

function renderMessages(messages) {
    const chatBody = document.getElementById('chatBody');
    const scrollTop = chatBody.scrollTop;
    const scrollHeight = chatBody.scrollHeight;
    const isAtBottom = scrollTop + chatBody.clientHeight >= scrollHeight - 20;

    chatBody.innerHTML = '';

    if (messages.length === 0) {
        chatBody.innerHTML = '<div style="text-align:center; color:#888; margin-top:20px;">Hãy bắt đầu trò chuyện!</div>';
        return;
    }

    const myId = window.currentUserId;

    messages.forEach(msg => {
        const div = document.createElement('div');
        const isMine = msg.sender_id == myId;
        div.className = `message-bubble ${isMine ? 'message-sent' : 'message-received'}`;

        let html = '';
        if (msg.image) {
            html += `<img src="/${msg.image}" class="message-image" onclick="window.open('/${msg.image}', '_blank')">`;
        }
        if (msg.content) {
            html += `<div>${msg.content}</div>`;
        }

        div.innerHTML = html;
        div.title = msg.created_at;
        chatBody.appendChild(div);
    });

    if (isAtBottom) {
        scrollToBottom();
    }
}

function scrollToBottom() {
    const chatBody = document.getElementById('chatBody');
    chatBody.scrollTop = chatBody.scrollHeight;
}

async function sendMessage() {
    const input = document.getElementById('chatInput');
    const fileInput = document.getElementById('chatImageInput');
    const content = input.value.trim();
    const file = fileInput && fileInput.files[0];

    if ((!content && !file) || !currentChatUserId) return;

    input.value = '';
    if (fileInput) fileInput.value = '';

    try {
        const formData = new FormData();
        formData.append('receiver_id', currentChatUserId);
        formData.append('content', content);
        if (file) {
            formData.append('image', file);
        }

        const response = await fetch('/api/messages.php?action=send', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.id) {
            loadChatHistory(currentChatUserId, true);
            scrollToBottom();
        }
    } catch (error) {
        console.error('Error sending message:', error);
        input.value = content;
    }
}

function handleChatKey(e) {
    if (e.key === 'Enter') {
        sendMessage();
    }
}

async function pollUnreadMessages() {
    try {
        const response = await fetch('/api/messages.php?action=unread');
        const data = await response.json();
        const count = data.count;

        const badge = document.getElementById('messageBadge');
        if (badge) {
            if (count > 0) {
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (error) {
    }
}

async function toggleMessageDropdown() {
    const dd = document.getElementById('messageDropdown');
    const list = dd.querySelector('.conversation-list');

    document.querySelectorAll('.dropdown-content').forEach(el => {
        if (el !== dd) el.style.display = 'none';
    });

    if (dd.style.display === 'block') {
        dd.style.display = 'none';
    } else {
        dd.style.display = 'block';
        list.innerHTML = '<div style="padding:10px;text-align:center;">Đang tải...</div>';
        try {
            const response = await fetch('/api/messages.php?action=conversations');
            const conversations = await response.json();
            renderConversations(conversations);
        } catch (error) {
            list.innerHTML = '<div style="padding:10px;text-align:center;color:red;">Lỗi tải tin nhắn.</div>';
        }
    }
}

function renderConversations(conversations) {
    const list = document.querySelector('#messageDropdown .conversation-list');
    list.innerHTML = '';

    if (conversations.length === 0) {
        list.innerHTML = '<div style="padding:20px;text-align:center;color:#666;">Chưa có tin nhắn nào.</div>';
        return;
    }

    conversations.forEach(c => {
        const div = document.createElement('div');
        div.className = `conversation-item ${c.is_read == 0 && c.sender_id != window.currentUserId ? 'unread' : ''}`;
        div.onclick = () => {
            openChat(c.id, c.full_name || c.username, c.avatar);
            document.getElementById('messageDropdown').style.display = 'none';
        };

        const avatar = c.avatar ? '/' + c.avatar : '/assets/default-avatar.png';
        const name = c.full_name || c.username;
        const msg = c.sender_id == window.currentUserId ? 'Bạn: ' + c.last_message : c.last_message;

        div.innerHTML = `
            <img src="${avatar}" class="conversation-avatar">
            <div class="conversation-details">
                <div class="conversation-name">${name}</div>
                <div class="conversation-last-msg">${msg}</div>
            </div>
        `;
        list.appendChild(div);
    });
}
