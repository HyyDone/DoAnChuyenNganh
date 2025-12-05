<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Thuê Trọ - Danh sách phòng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>

<?php
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 1200px; margin: 20px auto; padding: 0 15px;">
    <div style="display: grid; grid-template-columns: 20% 60% 20%; gap: 20px;">
        
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
                    <input type="file" name="image" accept="image/*" style="width: 100%;">
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

        <!-- Right Column: Placeholder -->
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); height: fit-content;">
            <h3 style="margin-top: 0; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Thông báo</h3>
            <p style="color: #666; font-style: italic;">Chức năng chưa được phát triển</p>
        </div>

    </div>
</div>

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

function loadListings() {
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
                
                const imagePath = listing.image_path ? listing.image_path : '/assets/default-room.jpg'; // Fallback image
                
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
                        <div style="text-align: right;">
                            <a href="/listing_detail.php?id=${listing.id}" style="display: inline-block; background: #e4e6eb; color: #050505; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 14px;">Chi tiết</a>
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
</script>

</body>
</html>
