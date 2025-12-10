<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/db.php';
// die('DEBUG: ALIVE - Listings page is executing PHP');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fetch user data if logged in (similar to index.php) to ensure session is valid
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT username, full_name, avatar FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch();
    } catch (PDOException $e) {
        // Silent fail or log
        error_log("DB Error in listings.php: " . $e->getMessage());
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Thuê Trọ - Danh sách phòng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
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
            margin: 0;
            padding: 0;
        }

        /* AI Chat Box (From Index) */
        .ai-chat-box {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
            height: 520px; /* Kept fixed height from listings page for consistency */
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
        
        .btn-favorite {
            background: none;
            border: 1px solid #ddd;
            color: #ccc;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 5px;
            transition: all 0.2s;
            font-size: 14px;
        }
        .btn-favorite.active {
            color: #e91e63;
            border-color: #e91e63;
            background: #fff0f5;
        }
        .btn-favorite:hover {
            background: #fff0f5;
        }
    </style>
</head>
<body>

<?php
include __DIR__ . '/includes/header.php';
?>

<div style="max-width: 1200px; margin: 20px auto; padding: 0 15px;">
    <div style="display: grid; grid-template-columns: 300px 1fr 360px; gap: 24px;">
        
        <!-- Left Column: Post Listing Form -->
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); height: fit-content;">
            <h3 style="margin-top: 0; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Đăng phòng</h3>
            <form id="postListingForm" enctype="multipart/form-data">
                <div style="margin-bottom: 10px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Tiêu đề</label>
                    <input type="text" name="title" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Giá (VNĐ)</label>
                    <input type="number" name="price" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Địa chỉ</label>
                    <input type="text" name="address" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Thành phố</label>
                    <select name="city" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        <option value="Ho Chi Minh">Hồ Chí Minh</option>
                        <option value="Ha Noi">Hà Nội</option>
                        <option value="Da Nang">Đà Nẵng</option>
                    </select>
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Loại phòng</label>
                    <select name="room_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        <option value="private">Riêng tư</option>
                        <option value="share">Ở ghép</option>
                        <option value="studio">Studio</option>
                    </select>
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Hình ảnh</label>
                    <input type="file" name="image" accept="image" style="width: 100%;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Mô tả</label>
                    <textarea name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;"></textarea>
                </div>
                <button type="submit" style="width: 100%; background: #1877f2; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-weight: bold;">Đăng tin</button>
            </form>
        </div>

        <!-- Center Column: Filters & Listings -->
        <div>
            <!-- Filters -->
            <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
                <div style="flex: 1;">
                    <input type="number" id="filterPriceMin" placeholder="Giá từ..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                </div>
                 <div style="flex: 1;">
                    <input type="number" id="filterPriceMax" placeholder="Đến giá..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div style="flex: 1;">
                    <select id="filterCity" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        <option value="">Tất cả thành phố</option>
                        <option value="Ho Chi Minh">Hồ Chí Minh</option>
                        <option value="Ha Noi">Hà Nội</option>
                        <option value="Da Nang">Đà Nẵng</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <select id="filterRoomType" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        <option value="">Tất cả loại phòng</option>
                        <option value="private">Riêng tư</option>
                        <option value="share">Ở ghép</option>
                        <option value="studio">Studio</option>
                    </select>
                </div>
                <button onclick="loadListings()" style="background: #1877f2; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">Lọc</button>
            </div>

            <h3 style="margin-bottom: 15px;">Danh sách phòng trọ</h3>
            
            <!-- Listings Container -->
            <div id="listingsContainer">
                <!-- Listings will be loaded here via JS -->
                <p style="text-align:center; color: #666;">Đang tải dữ liệu...</p>
            </div>
        </div>

        <!-- Right Column: AI Chat -->
        <div style="height: fit-content;"> <!-- Wrapper to keep sticky behavior if needed -->
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
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>


<script>
document.addEventListener('DOMContentLoaded', function() {
    loadListings();

    document.getElementById('postListingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('/api/listings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Đăng tin thành công!');
                this.reset();
                loadListings();
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể đăng tin'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Đã xảy ra lỗi khi đăng tin.');
        });
    });
});


let userFavorites = new Set();

async function fetchUserFavorites() {
    try {
        const res = await fetch('/api/favorites.php');
        const data = await res.json();
        if (data.success && data.favorites) {
            userFavorites = new Set(data.favorites.map(f => f.id));
        }
    } catch (e) {
        console.error('Error fetching favorites:', e);
    }
}

async function loadListings() {
    await fetchUserFavorites();

    const priceMin = document.getElementById('filterPriceMin').value;
    const priceMax = document.getElementById('filterPriceMax').value;
    const city = document.getElementById('filterCity').value;
    const roomType = document.getElementById('filterRoomType').value;

    const params = new URLSearchParams({
        price_min: priceMin,
        price_max: priceMax,
        city: city,
        room_type: roomType,
        status: 'available' // Only show available listings
    });

    fetch(`/api/listings.php?${params.toString()}`)
    .then(response => response.json())
    .then(result => {
        const container = document.getElementById('listingsContainer');
        container.innerHTML = '';

        if (result.success && result.data.length > 0) {
            result.data.forEach(listing => {
                const item = document.createElement('div');
                item.style.cssText = 'background: white; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 15px; display: flex; gap: 15px;';
                
                const imagePath = listing.image_path ? listing.image_path : 'https://placehold.co/400x300?text=Phong+Tro'; // Fallback image
                
                const isFav = userFavorites.has(listing.id);
                const heartClass = isFav ? 'active' : '';
                const heartIcon = isFav ? 'fa-solid' : 'fa-regular';

                item.innerHTML = `
                    <div style="width: 150px; height: 120px; flex-shrink: 0;">
                        <img src="${imagePath}" alt="${listing.title}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px; background: #eee;">
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; font-size: 18px; color: #1c1e21;">${listing.title}</h3>
                            <p style="margin: 0 0 5px 0; color: #e91e63; font-weight: bold;">${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(listing.price)}</p>
                            <p style="margin: 0 0 5px 0; color: #65676b; font-size: 14px;">📍 ${listing.address}, ${listing.city}</p>
                            <p style="margin: 0; color: #65676b; font-size: 14px;">🏠 ${listing.room_type === 'private' ? 'Riêng tư' : (listing.room_type === 'share' ? 'Ở ghép' : 'Studio')}</p>
                        </div>
                        <div style="text-align: right; display:flex; justify-content:flex-end; align-items:center;">
                            <a href="/listing_detail.php?id=${listing.id}" style="display: inline-block; background: #e4e6eb; color: #050505; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 14px;">Chi tiết</a>
                            <button class="btn-favorite ${heartClass}" onclick="toggleFavorite(${listing.id}, this)">
                                <i class="${heartIcon} fa-heart"></i>
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(item);
            });
        } else {
            container.innerHTML = '<p style="text-align:center; color: #666; padding: 20px;">Không tìm thấy phòng trọ nào phù hợp.</p>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('listingsContainer').innerHTML = '<p style="text-align:center; color: red;">Lỗi khi tải dữ liệu.</p>';
    });
}

function toggleFavorite(listingId, btn) {
    fetch('/api/favorites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ listing_id: listingId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Toggle UI
            const icon = btn.querySelector('i');
            if (data.action === 'added') {
                btn.classList.add('active');
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
                userFavorites.add(listingId);
            } else {
                btn.classList.remove('active');
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
                userFavorites.delete(listingId);
            }
        } else {
            if (data.message === 'Unauthorized') {
                alert('Vui lòng đăng nhập để sử dụng tính năng này.');
                window.location.href = '/login.php';
            } else {
                alert('Lỗi: ' + data.message);
            }
        }
    })
    .catch(err => console.error(err));
}

const chatInput = document.getElementById('chatInput');
const chatMessages = document.getElementById('chatMessages');
const aiChatFile = document.getElementById('aiChatFile');
const aiChatPreviewBox = document.getElementById('aiChatPreviewBox');
const aiChatPreviewImg = document.getElementById('aiChatPreviewImg');

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

    // Add user message
    let userDisplay = message;
    if (hasImage) {
        userDisplay += '<br><small><i>[Đã gửi 1 ảnh]</i></small>';
    }
    addMessageToChat(userDisplay, 'user');

    chatContext.push({ sender: 'user', text: message + (hasImage ? " [User sent an image]" : "") });
    chatInput.value = '';

    const formData = new FormData();
    formData.append('message', message);
    if (hasImage) {
        formData.append('image', aiChatFile.files[0]);
    }
    formData.append('history', JSON.stringify(chatContext));

    removeChatImage();

    const loadingId = addMessageToChat('<i class="fa-solid fa-ellipsis fa-fade"></i>', 'bot');

    try {
        const response = await fetch('/api/ai_chat.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        document.getElementById(loadingId).remove();

        if (result.success) {
            addMessageToChat(result.response, 'bot');
            chatContext.push({ sender: 'bot', text: result.response });

             // Handle Actions (Auto Listing)
            if (result.action_performed === 'listing_created') {
                console.log('Listing created by AI, reloading list...');
                loadListings(); 
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
    requestAnimationFrame(() => {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });
    return div.id;
}
</script>

</body>
</html>
