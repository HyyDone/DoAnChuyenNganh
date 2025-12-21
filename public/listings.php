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
        $stmt = $pdo->prepare("SELECT username, full_name, avatar, is_landlord FROM users WHERE id = ?");
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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js"></script>
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
    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
        
        <!-- List Column -->
        <div>
            <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; flex-direction:column; gap: 15px;">
                <div style="display:flex; gap:10px; align-items:center;">
                    <div style="flex: 1;">
                         <label style="font-weight:bold; font-size:14px; margin-bottom:5px; display:block;">Khoảng giá</label>
                         <div id="priceSlider" style="margin: 0 10px 10px 10px;"></div>
                         <div style="display:flex; justify-content:space-between; font-size:13px; color:#666;">
                             <span id="priceMinDisplay"></span>
                             <span id="priceMaxDisplay"></span>
                         </div>
                         <input type="hidden" id="filterPriceMin">
                         <input type="hidden" id="filterPriceMax">
                    </div>
                </div>
                
                <div style="display:flex; gap:10px;">
                    <div style="flex: 1;">
                        <select id="filterCity" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" onchange="updateFilterDistricts()">
                            <option value="">Tất cả thành phố</option>
                            <option value="Ho Chi Minh">Hồ Chí Minh</option>
                            <option value="Ha Noi">Hà Nội</option>
                            <option value="Da Nang">Đà Nẵng</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <select id="filterDistrict" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                            <option value="">Tất cả Quận/Huyện</option>
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
                </div>

                <div style="display:flex; gap:10px; align-items:flex-end;">
                     <div style="flex: 1;">
                         <label style="font-weight:bold; font-size:14px; margin-bottom:5px; display:block;">Diện tích (m²)</label>
                         <div style="display:flex; gap:5px; align-items:center;">
                             <input type="number" id="filterAreaMin" placeholder="Từ" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width:100%;">
                             <span>-</span>
                             <input type="number" id="filterAreaMax" placeholder="Đến" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width:100%;">
                         </div>
                    </div>
                    <div>
                        <button onclick="loadListings()" style="background: #1877f2; color: white; border: none; padding: 9px 25px; border-radius: 4px; cursor: pointer; font-weight: bold; height:37px;">Lọc</button>
                    </div>
                </div>
            </div>

            <h3 style="margin-bottom: 15px;">Danh sách phòng trọ</h3>
            
            <div id="listingsContainer">
                <p style="text-align:center; color: #666;">Đang tải dữ liệu...</p>
            </div>
        </div>

        <!-- Form Column -->
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); width: 100%; height: fit-content; position: sticky; top: 20px;">
            <h3 style="margin-top: 0; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Đăng phòng</h3>
            <?php if ($currentUser && !empty($currentUser['is_landlord'])): ?>
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
                    <label style="display:block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Diện tích (m²)</label>
                    <input type="number" name="area" step="0.1" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
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
            <?php else: ?>
            <div style="text-align:center; padding:30px 20px; color:#65676b; background:#f7f7f7; border-radius:8px; border:1px dashed #ced0d4;">
                <i class="fa-solid fa-user-shield" style="font-size:32px; margin-bottom:15px; color:#ccc;"></i>
                <h4 style="margin:0 0 10px 0;">Đăng tin cho thuê</h4>
                <p style="font-size:14px; margin-bottom:20px;">Bạn cần kích hoạt vai trò <b>Chủ trọ</b> để đăng tin cho thuê phòng.</p>
                <a href="/profile.php" style="display:inline-block; padding:10px 20px; background:#e7f3ff; color:#1877f2; text-decoration:none; font-weight:bold; border-radius:6px; transition:0.2s;">
                    Kích hoạt ngay
                </a>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Floating Chat Widget -->
<style>
    .floating-chat-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        background: #0866ff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 30px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        cursor: pointer;
        z-index: 1000;
        transition: transform 0.2s;
    }
    .floating-chat-btn:hover {
        transform: scale(1.1);
    }
    .floating-chat-box {
        position: fixed;
        bottom: 90px;
        right: 20px;
        width: 350px;
        height: 500px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        z-index: 1000;
        display: none;
        flex-direction: column;
        overflow: hidden;
        animation: slideUp 0.3s ease;
        border: 1px solid #ddd;
    }
    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>

<div class="floating-chat-btn" onclick="toggleChatBox()">
    <i class="fa-solid fa-robot"></i>
</div>

<div class="floating-chat-box" id="floatingChatBox">
    <div class="ai-chat-header">
        <i class="fa-solid fa-robot"></i>
        Trợ lý AI
        <span style="margin-left:auto; cursor:pointer;" onclick="toggleChatBox()"><i class="fa-solid fa-xmark"></i></span>
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

<script>
function toggleChatBox() {
    const box = document.getElementById('floatingChatBox');
    if (box.style.display === 'none' || !box.style.display) {
        box.style.display = 'flex';
    } else {
        box.style.display = 'none';
    }
}
</script>

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
    const citySelect = document.getElementById('postCity');
    if (!citySelect) return;
    
    const city = citySelect.value;
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

function updateFilterDistricts() {
    const city = document.getElementById('filterCity').value;
    const districtSelect = document.getElementById('filterDistrict');
    
    districtSelect.innerHTML = '<option value="">Tất cả Quận/Huyện</option>';
    
    if (city && districtData[city]) {
        districtData[city].forEach(d => {
            const opt = document.createElement('option');
            opt.value = d;
            opt.textContent = d;
            districtSelect.appendChild(opt);
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateDistricts();
    
    // Init Slider
    var slider = document.getElementById('priceSlider');
    noUiSlider.create(slider, {
        start: [0, 20000000],
        connect: true,
        range: {
            'min': 0,
            'max': 20000000
        },
        step: 100000,
        format: {
            to: (v) => Math.round(v),
            from: (v) => Number(v)
        }
    });

    const minDisplay = document.getElementById('priceMinDisplay');
    const maxDisplay = document.getElementById('priceMaxDisplay');
    const minInput = document.getElementById('filterPriceMin');
    const maxInput = document.getElementById('filterPriceMax');

    slider.noUiSlider.on('update', function (values, handle) {
        minInput.value = values[0];
        maxInput.value = values[1];
        
        const fmt = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' });
        minDisplay.innerText = fmt.format(values[0]);
        maxDisplay.innerText = fmt.format(values[1]);
    });

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

let currentPage = 1;

async function loadListings(page = 1) {
    currentPage = page;
    await fetchUserFavorites();

    const priceMin = document.getElementById('filterPriceMin').value;
    const priceMax = document.getElementById('filterPriceMax').value;
    const city = document.getElementById('filterCity').value;
    const district = document.getElementById('filterDistrict').value;
    const roomType = document.getElementById('filterRoomType').value;
    const areaMin = document.getElementById('filterAreaMin').value;
    const areaMax = document.getElementById('filterAreaMax').value;

    const params = new URLSearchParams({
        price_min: priceMin,
        price_max: priceMax,
        city: city,
        district: district,
        room_type: roomType,
        area_min: areaMin,
        area_max: areaMax,
        status: 'available',
        page: currentPage,
        limit: 10
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
                            <p style="margin: 0; color: #65676b; font-size: 14px;">
                                🏠 ${listing.room_type === 'private' ? 'Riêng tư' : (listing.room_type === 'share' ? 'Ở ghép' : 'Studio')}
                                ${listing.area ? ` • <i class="fa-solid fa-ruler-combined"></i> ${listing.area}m²` : ''}
                            </p>
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
            
            // Render Pagination
            renderPagination(result.pagination);

        } else {
            container.innerHTML = '<p style="text-align:center; color: #666; padding: 20px;">Không tìm thấy phòng trọ nào phù hợp.</p>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('listingsContainer').innerHTML = '<p style="text-align:center; color: red;">Lỗi khi tải dữ liệu.</p>';
    });
}

function renderPagination(pagination) {
    if (!pagination || pagination.total_pages <= 1) return;
    const container = document.getElementById('listingsContainer');
    
    let html = '<div style="margin-top: 20px; display: flex; justify-content: center; gap: 5px; width: 100%;">';
    
    // Prev
    if (pagination.current_page > 1) {
        html += `<button onclick="loadListings(${pagination.current_page - 1})" style="padding: 8px 12px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 4px;">&laquo;</button>`;
    }

    // Pages
    for (let i = 1; i <= pagination.total_pages; i++) {
            const active = i === pagination.current_page ? 'background: #0866ff; color: white; border-color: #0866ff;' : 'background: white; color: #333;';
            html += `<button onclick="loadListings(${i})" style="padding: 8px 12px; border: 1px solid #ddd; cursor: pointer; border-radius: 4px; ${active}">${i}</button>`;
    }

    // Next
    if (pagination.current_page < pagination.total_pages) {
        html += `<button onclick="loadListings(${pagination.current_page + 1})" style="padding: 8px 12px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 4px;">&raquo;</button>`;
    }

    html += '</div>';
    
    // Append pagination div
    const paginationDiv = document.createElement('div');
    paginationDiv.innerHTML = html;
    container.appendChild(paginationDiv);
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
