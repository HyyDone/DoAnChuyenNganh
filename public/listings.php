<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$currentUser = null;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT username, full_name, avatar FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch();
    } catch (PDOException $e) {
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

        .ai-chat-box {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
            height: 520px;
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
            left: 65px;
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
                    <label style="display:block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Thành phố</label>
                    <select id="postCity" name="city" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" onchange="updateDistricts()">
                        <option value="Ho Chi Minh">Hồ Chí Minh</option>
                        <option value="Ha Noi">Hà Nội</option>
                        <option value="Da Nang">Đà Nẵng</option>
                    </select>
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Quận/Huyện</label>
                    <select id="postDistrict" name="district" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                    </select>
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Địa chỉ cụ thể</label>
                    <input type="text" name="address" required placeholder="Số nhà, tên đường..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
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
                    <label style="display:block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Hình ảnh (Có thể chọn nhiều)</label>
                    <input type="file" id="listingImagesInput" name="images[]" multiple accept="image/*" style="width: 100%;" onchange="previewListingImages(this)">
                    <div id="listingImagePreview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 8px; margin-top: 10px;"></div>
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Mô tả</label>
                    <textarea name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;"></textarea>
                </div>
                <button type="submit" style="width: 100%; background: #1877f2; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-weight: bold;">Đăng tin</button>
            </form>
        </div>

        <div>
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
            
            <div id="listingsContainer">
                <p style="text-align:center; color: #666;">Đang tải dữ liệu...</p>
            </div>
        </div>

        <div style="height: fit-content;">
            <div class="ai-chat-box">
                <div class="ai-chat-header">
                    <i class="fa-solid fa-robot"></i>
                    Trợ lý AI
                </div>
                <div class="chat-messages" id="chatMessages">
                    <div class="chat-message message-bot">Xin chào! Tôi là trợ lý ảo. Bạn cần giúp gì về việc tìm trọ không?</div>
                </div>
                <div class="chat-image-preview-box" id="aiChatPreviewBox">
                    <div id="aiChatPreviewContainer" style="display:flex; flex-wrap:wrap;"></div>
                </div>
                <div class="chat-input-area">
                    <label for="aiChatFile" class="chat-upload-btn">
                        <i class="fa-solid fa-image"></i>
                    </label>
                    <input type="file" id="aiChatFile" hidden accept="image/*" multiple>
                    
                    <input type="text" class="chat-input" id="chatInput" placeholder="Nhập tin nhắn...">
                    <button class="chat-send-btn" onclick="sendChatMessage()">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
<script src="/assets/js/ai_chat.js?v=<?= time() ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>


<script>
const districtData = {
    'Ho Chi Minh': ['Quận 1', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 7', 'Quận 10', 'Bình Thạnh', 'Phú Nhuận', 'Tân Bình', 'Thủ Đức', 'Gò Vấp'],
    'Ha Noi': ['Hoàn Kiếm', 'Đống Đa', 'Ba Đình', 'Hai Bà Trưng', 'Hoàng Mai', 'Thanh Xuân', 'Long Biên', 'Nam Từ Liêm', 'Bắc Từ Liêm', 'Cầu Giấy', 'Hà Đông'],
    'Da Nang': ['Hải Châu', 'Thanh Khê', 'Sơn Trà', 'Ngũ Hành Sơn', 'Liên Chiểu', 'Cẩm Lệ']
};

function updateDistricts() {
    const city = document.getElementById('postCity').value;
    const districtSelect = document.getElementById('postDistrict');
    const districts = districtData[city] || [];
    
    districtSelect.innerHTML = '';
    districts.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d;
        opt.textContent = d;
        districtSelect.appendChild(opt);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    updateDistricts();
    loadListings();

    window.previewListingImages = function(input) {
        const container = document.getElementById('listingImagePreview');
        container.innerHTML = '';
        if (input.files) {
            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = 'width: 100%; height: 80px; object-fit: cover; border-radius: 4px;';
                    container.appendChild(img);
                }
                reader.readAsDataURL(file);
            });
        }
    }

    document.getElementById('postListingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;
        submitBtn.disabled = true;
        submitBtn.innerText = 'Đang đăng...';

        fetch('/api/listings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
            
            if (data.success) {
                alert('Đăng tin thành công!');
                this.reset();
                document.getElementById('listingImagePreview').innerHTML = '';
                loadListings();
            } else {
                if (data.message === 'Unauthorized') {
                    alert('Bạn cần đăng nhập để đăng tin.');
                    window.location.href = '/login.php';
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể đăng tin'));
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
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
        status: 'available'
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
                
                const imagePath = listing.image_path ? listing.image_path : 'https://placehold.co/400x300?text=Phong+Tro';
                
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
</script>

</body>
</html>
