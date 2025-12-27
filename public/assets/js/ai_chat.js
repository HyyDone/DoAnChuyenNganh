document.addEventListener('DOMContentLoaded', function () {
    initAiChat();
});

let chatContext = [];
let selectedChatImages = [];

function initAiChat() {
    const chatMessages = document.getElementById('chatMessages');
    if (!chatMessages) return;

    const storedHistory = sessionStorage.getItem('aiChatHistory');
    if (storedHistory) {
        chatContext = JSON.parse(storedHistory);
        renderChatHistory(chatContext);
    } else {
        const firstMsg = document.querySelector('.message-bot');
        if (firstMsg) {
            if (chatContext.length === 0) {
                chatContext.push({ sender: 'bot', text: firstMsg.innerText });
                saveHistory();
            }
        }
    }

    const chatInput = document.getElementById('chatInput');
    if (chatInput) {
        chatInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') sendChatMessage();
        });
    }

    const fileInput = document.getElementById('aiChatFile');
    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            handleChatFilesObj(e.target.files);
            this.value = '';
        });
    }
}

function saveHistory() {
    sessionStorage.setItem('aiChatHistory', JSON.stringify(chatContext));
}

function renderChatHistory(history) {
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.innerHTML = '';

    history.forEach(msg => {
        addMessageToChatOrRestore(msg.text, msg.sender, false);
    });

    scrollToBottom();
}

function handleChatFilesObj(files) {
    if (!files || files.length === 0) return;

    const previewBox = document.getElementById('aiChatPreviewBox');

    Array.from(files).forEach(file => {
        selectedChatImages.push(file);

        const reader = new FileReader();
        reader.onload = function (e) {
            const wrapper = document.createElement('div');
            wrapper.className = 'chat-preview-item';
            wrapper.style.position = 'relative';
            wrapper.style.display = 'inline-block';
            wrapper.style.marginRight = '8px';

            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'chat-preview-img';
            img.style.height = '60px';
            img.style.borderRadius = '4px';

            const removeBtn = document.createElement('div');
            removeBtn.innerHTML = '&times;';
            removeBtn.className = 'chat-preview-remove';
            removeBtn.style.position = 'absolute';
            removeBtn.style.top = '-5px';
            removeBtn.style.right = '-5px';
            removeBtn.style.background = 'red';
            removeBtn.style.color = 'white';
            removeBtn.style.borderRadius = '50%';
            removeBtn.style.width = '18px';
            removeBtn.style.height = '18px';
            removeBtn.style.textAlign = 'center';
            removeBtn.style.lineHeight = '16px';
            removeBtn.style.cursor = 'pointer';
            removeBtn.style.fontSize = '14px';

            removeBtn.onclick = function () {
                const idx = selectedChatImages.indexOf(file);
                if (idx > -1) selectedChatImages.splice(idx, 1);
                wrapper.remove();
                if (selectedChatImages.length === 0) {
                    previewBox.classList.remove('active');
                }
            };

            wrapper.appendChild(img);
            wrapper.appendChild(removeBtn);

            let container = document.getElementById('aiChatPreviewContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'aiChatPreviewContainer';
                container.style.display = 'flex';
                container.style.flexWrap = 'wrap';
                previewBox.appendChild(container); 
                const oldImg = document.getElementById('aiChatPreviewImg');
                if (oldImg) oldImg.style.display = 'none';
                const oldRem = document.querySelector('.chat-preview-remove:not(.chat-preview-item .chat-preview-remove)');
                if (oldRem) oldRem.style.display = 'none';
            }

            container.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
    });

    previewBox.classList.add('active');
}


async function sendChatMessage() {
    const chatInput = document.getElementById('chatInput');
    const message = chatInput.value.trim();
    const hasImages = selectedChatImages.length > 0;

    if (!message && !hasImages) return;

    let userDisplay = message;
    if (hasImages) {
        userDisplay += `<br><small><i>[Đã gửi ${selectedChatImages.length} ảnh]</i></small>`;
    }
    addMessageToChat(userDisplay, 'user');
    chatInput.value = '';

    chatContext.push({ sender: 'user', text: userDisplay });
    saveHistory();

    const formData = new FormData();
    formData.append('message', message);

    selectedChatImages.forEach(file => {
        formData.append('images[]', file);
    });

    const historyPayload = chatContext.map(c => ({
        sender: c.sender,
        text: c.text
    }));
    formData.append('history', JSON.stringify(historyPayload));

    selectedChatImages = [];
    const previewContainer = document.getElementById('aiChatPreviewContainer');
    if (previewContainer) previewContainer.innerHTML = '';
    document.getElementById('aiChatPreviewBox').classList.remove('active');

    const loadingId = addMessageToChat('<i class="fa-solid fa-ellipsis fa-fade"></i>', 'bot');

    try {
        const response = await fetch('/api/ai_chat.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        const loadingEl = document.getElementById(loadingId);
        if (loadingEl) loadingEl.remove();

        if (result.success) {
            addMessageToChat(result.response, 'bot');
            chatContext.push({ sender: 'bot', text: result.response });
            saveHistory();

            if (result.action_performed === 'listing_created' || result.action_performed === 'post_created') {
                if (typeof loadListings === 'function') loadListings();
                if (typeof location !== 'undefined' && location.pathname === '/index.php') {
                    window.location.reload();
                }
            }
        } else {
            addMessageToChat("Lỗi: " + result.message, 'bot');
            chatContext.push({ sender: 'bot', text: "Lỗi: " + result.message });
            saveHistory();
        }
    } catch (error) {
        console.error('Chat error:', error);
        const loadingEl = document.getElementById(loadingId);
        if (loadingEl) loadingEl.remove();
        addMessageToChat("Xin lỗi, tôi đang gặp sự cố kết nối.", 'bot');
    }
}

function addMessageToChat(html, sender) {
    return addMessageToChatOrRestore(html, sender, true);
}

function addMessageToChatOrRestore(html, sender, shouldScroll) {
    const chatMessages = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = `chat-message message-${sender}`;
    div.id = 'msg-' + Date.now() + Math.random();

    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');

    div.innerHTML = html;
    chatMessages.appendChild(div);

    if (shouldScroll) scrollToBottom();
    return div.id;
}

function scrollToBottom() {
    const chatMessages = document.getElementById('chatMessages');
    requestAnimationFrame(() => {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });
}
