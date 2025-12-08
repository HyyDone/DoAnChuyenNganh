<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Quản lý Cho Thuê/Thuê Nhà</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0866ff;
            --bg-color: #f0f2f5;
            --card-bg: #ffffff;
            --text-color: #050505;
            --secondary-text: #65676b;
            --border-color: #ddd;
            --success-bg: #e6f4ea; 
            --success-text: #1e7e34;
            --warning-bg: #fff3cd;
            --warning-text: #856404;
        }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        .main-layout {
            display: flex;
            flex: 1;
            overflow: hidden; 
        }

        /* Left Column - Navigation */
        .layout-left {
            width: 250px;
            background: var(--card-bg);
            border-right: 1px solid var(--border-color);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .nav-btn {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: var(--secondary-text);
            transition: 0.2s;
            background: none;
            border: none;
            text-align: left;
            font-size: 1rem;
            width: 100%;
        }

        .nav-btn:hover {
            background-color: #f0f2f5;
        }

        .nav-btn.active {
            background-color: #e7f3ff;
            color: var(--primary-color);
        }

        .nav-btn i {
            margin-right: 12px;
            width: 24px;
            text-align: center;
        }

        /* Center Column - List */
        .layout-center {
            flex: 1.5; 
            background: var(--bg-color);
            padding: 20px;
            overflow-y: auto;
            border-right: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: var(--text-color);
        }

        .listing-card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: 0.2s;
            border: 2px solid transparent;
        }

        .listing-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .listing-card.selected {
            border-color: var(--primary-color);
        }

        .listing-card.booked {
            background-color: var(--success-bg);
            border: 1px solid #c3e6cb;
        }
        
        .listing-card.pending {
            background-color: var(--warning-bg);
            border: 1px solid #ffeeba;
        }

        .listing-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .listing-title {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 4px;
            color: var(--text-color);
        }

        .listing-price {
            color: #dc3545;
            font-weight: bold;
        }

        .tenant-info {
            margin-top: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        /* Right Column - Editor/Details */
        .layout-right {
            width: 350px; 
            background: var(--card-bg);
            padding: 20px;
            overflow-y: auto;
            border-left: 1px solid var(--border-color);
        }

        .editor-form {
            display: none; 
        }
        
        .editor-form.active {
            display: block; 
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-family: inherit;
            box-sizing: border-box;
        }
        
        .form-textarea { resize: vertical; min-height: 80px; }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #bb2d3b; }
        .btn-success { background: #198754; color: white; }
        .btn-success:hover { background: #157347; }
        
        .empty-state {
            text-align: center;
            color: var(--secondary-text);
            margin-top: 50px;
        }
        
        .request-box {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="main-layout">
        <!-- LEFT COLUMN -->
        <div class="layout-left">
            <button class="nav-btn active" onclick="switchView('my_listings', this)">
                <i class="fa-solid fa-house-user"></i> Cho Thuê
            </button>
            <button class="nav-btn" onclick="switchView('my_rentals', this)">
                <i class="fa-solid fa-key"></i> Nhà Thuê
            </button>
            <button class="nav-btn" onclick="switchView('stats', this)">
                <i class="fa-solid fa-chart-pie"></i> Thống kê
            </button>
        </div>

        <!-- CENTER COLUMN -->
        <div class="layout-center" id="center-column">
            <h2 class="section-title">Danh sách nhà cho thuê</h2>
            <div id="list-container">
                <!-- Items will be injected here -->
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="layout-right" id="right-column">
            <div class="empty-state">
                <i class="fa-regular fa-hand-pointer" style="font-size: 3rem; margin-bottom: 20px;"></i>
                <p>Chọn một mục để xem chi tiết hoặc chỉnh sửa</p>
            </div>
            
            <!-- EDIT FORM FOR LISTINGS -->
            <div id="listing-editor" class="editor-form">
                
                <!-- Pending Request Box -->
                <div id="pending-request-ui" style="display:none;">
                    <div class="request-box">
                        <h4 style="margin-top:0"><i class="fa-solid fa-bell"></i> Yêu cầu thuê mới</h4>
                        <p><strong>Người thuê:</strong> <span id="req-name"></span></p>
                        <p><strong>SĐT:</strong> <span id="req-phone"></span></p>
                        <p><strong>Ngày muốn thuê:</strong> <span id="req-date"></span></p>
                        <p style="font-size:0.9rem;">Hệ thống sẽ ẩn bài đăng này nếu bạn xác nhận.</p>
                        <div class="btn-group">
                            <button class="btn btn-success" onclick="confirmBooking()">Xác nhận</button>
                            <button class="btn btn-danger" onclick="rejectBooking()">Từ chối</button>
                        </div>
                    </div>
                    <hr>
                </div>

                <h3>Chỉnh sửa thông tin</h3>
                <input type="hidden" id="edit-id">
                <input type="hidden" id="booking-id"> <!-- For confirming -->
                
                <div class="form-group">
                    <label class="form-label">Tiêu đề</label>
                    <input type="text" id="edit-title" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Giá (VNĐ)</label>
                    <input type="number" id="edit-price" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Loại phòng</label>
                    <select id="edit-type" class="form-select">
                        <option value="private">Phòng riêng</option>
                        <option value="share">Ở ghép</option>
                        <option value="studio">Studio</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Địa chỉ</label>
                    <input type="text" id="edit-address" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Quận/Huyện</label>
                    <input type="text" id="edit-district" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Thành phố</label>
                    <input type="text" id="edit-city" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Trạng thái</label>
                    <select id="edit-status" class="form-select">
                        <option value="available">Còn trống</option>
                        <option value="booked">Đã thuê</option>
                        <option value="inactive">Tạm ẩn</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea id="edit-desc" class="form-textarea"></textarea>
                </div>

                <div class="btn-group">
                    <button class="btn btn-primary" onclick="updateListing()">Cập nhật</button>
                    <button class="btn btn-danger" onclick="deleteListing()">Xóa nhà</button>
                </div>
            </div>

            <!-- VIEW FOR RENTALS (READ ONLY) -->
            <div id="rental-details" class="editor-form">
                <h3>Thông tin nhà thuê</h3>
                <div id="rental-info-content"></div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        let currentView = 'my_listings';
        let currentItems = [];
        let selectedId = null;

        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const view = urlParams.get('view');
            if (view && ['my_listings', 'my_rentals', 'stats'].includes(view)) {
                // Find button
                const btn = document.querySelector(`.nav-btn[onclick*="'${view}'"]`);
                if (btn) {
                    switchView(view, btn);
                    return; // switchView calls loadData
                }
            }
            loadData();
        });

        function switchView(view, btn) {
            currentView = view;
            
            document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            resetRightColumn();
            
            const titles = {
                'my_listings': 'Danh sách nhà cho thuê',
                'my_rentals': 'Nhà đang thuê',
                'stats': 'Thống kê'
            };
            document.querySelector('.section-title').innerText = titles[view];

            loadData();
        }
        
        function resetRightColumn() {
            selectedId = null;
            document.querySelector('.empty-state').style.display = 'block';
            document.querySelectorAll('.editor-form').forEach(f => f.classList.remove('active'));
            document.getElementById('edit-id').value = '';
        }

        async function loadData() {
            const container = document.getElementById('list-container');
            container.innerHTML = '<p>Đang tải...</p>';

            if (currentView === 'stats') {
                container.innerHTML = '<p>Chức năng thống kê chưa được triển khai.</p>';
                return;
            }

            try {
                const action = currentView === 'my_listings' ? 'get_my_listings' : 'get_my_rentals';
                const res = await fetch(`/api/rentals.php?action=${action}`);
                const data = await res.json();
                currentItems = data;
                renderList(data);
            } catch (err) {
                console.error(err);
                container.innerHTML = '<p>Lỗi tải dữ liệu</p>';
            }
        }

        function renderList(items) {
            const container = document.getElementById('list-container');
            container.innerHTML = '';
            
            if (items.length === 0) {
                container.innerHTML = '<p>Không có dữ liệu.</p>';
                return;
            }

            items.forEach(item => {
                let statusClass = '';
                let extraInfo = '';

                if (currentView === 'my_listings') {
                    if (item.booking_status === 'pending') {
                        statusClass = 'pending';
                        extraInfo = `<div class="tenant-info" style="color:var(--warning-text)"><i class="fa-solid fa-bell"></i> Đang có yêu cầu thuê</div>`;
                    } else if (item.booking_status === 'confirmed') {
                        statusClass = 'booked';
                        extraInfo = `
                            <div class="tenant-info">
                                <i class="fa-solid fa-user-check" style="margin-right:8px"></i> 
                                Đang thuê: ${item.tenant_name || 'Người dùng'} (${item.tenant_phone || 'SĐT: N/A'})
                            </div>`;
                    } else {
                        extraInfo = `<div style="color:var(--secondary-text); font-size:0.9rem; margin-top:5px;">Trạng thái: ${getStatusText(item.status)}</div>`;
                    }
                } else if (currentView === 'my_rentals') {
                     if (item.booking_status === 'pending') {
                         extraInfo = `<div style="color:orange; margin-top:5px;">⏳ Đang chờ chủ nhà xác nhận...</div>`;
                     } else {
                        extraInfo = `
                            <div style="margin-top:5px; font-size:0.9rem;">
                                <div><i class="fa-solid fa-phone"></i> Chủ nhà: ${item.owner_phone || 'N/A'}</div>
                                <div>Thời hạn: ${item.end_date || 'Không xác định'}</div>
                            </div>`;
                     }
                }

                const html = `
                    <div class="listing-card ${statusClass}" id="item-${item.id}" onclick="selectItem(${item.id})">
                        <div class="listing-header">
                            <div class="listing-title">${item.title}</div>
                            <div class="listing-price">${parseInt(item.price).toLocaleString()} đ</div>
                        </div>
                        <div style="font-size:0.9rem; color:var(--secondary-text)">
                            <i class="fa-solid fa-location-dot"></i> ${item.address}, ${item.district}
                        </div>
                        ${extraInfo}
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', html);
            });
        }

        function selectItem(id) {
            document.querySelectorAll('.listing-card').forEach(c => c.classList.remove('selected'));
            const card = document.getElementById(`item-${id}`);
            if(card) card.classList.add('selected');
            
            selectedId = id;
            const item = currentItems.find(i => i.id == id);
            
            document.querySelector('.empty-state').style.display = 'none';
            document.querySelectorAll('.editor-form').forEach(f => f.classList.remove('active'));

            if (currentView === 'my_listings') {
                const form = document.getElementById('listing-editor');
                form.classList.add('active');
                
                // Show/Hide Pending Section
                const pendingUI = document.getElementById('pending-request-ui');
                if (item.booking_status === 'pending') {
                    pendingUI.style.display = 'block';
                    document.getElementById('booking-id').value = item.booking_id;
                    document.getElementById('req-name').innerText = item.tenant_name || 'Không có tên';
                    document.getElementById('req-phone').innerText = item.tenant_phone || 'N/A';
                    document.getElementById('req-date').innerText = item.start_date || 'N/A';
                } else {
                    pendingUI.style.display = 'none';
                }

                // Fill form
                document.getElementById('edit-id').value = item.id;
                document.getElementById('edit-title').value = item.title;
                document.getElementById('edit-price').value = item.price;
                document.getElementById('edit-type').value = item.room_type;
                document.getElementById('edit-address').value = item.address;
                document.getElementById('edit-district').value = item.district;
                document.getElementById('edit-city').value = item.city;
                document.getElementById('edit-status').value = item.status;
                document.getElementById('edit-desc').value = item.description;

            } else if (currentView === 'my_rentals') {
                const view = document.getElementById('rental-details');
                view.classList.add('active');
                
                let content = `
                    <p><strong>Tiêu đề:</strong> ${item.title}</p>
                    <p><strong>Giá thuê:</strong> ${parseInt(item.price).toLocaleString()} đ/tháng</p>
                    <p><strong>Địa chỉ:</strong> ${item.address}, ${item.district}, ${item.city}</p>
                    <hr>
                `;
                
                if (item.booking_status === 'pending') {
                    content += `<p style="color:orange; font-weight:bold;">Yêu cầu thuê của bạn đang chờ chủ nhà xác nhận.</p>`;
                } else {
                    content += `
                        <p><strong>Chủ nhà:</strong> ${item.owner_name || 'N/A'}</p>
                        <p><strong>Liên hệ:</strong> ${item.owner_phone || 'N/A'}</p>
                        <p><strong>Ngày bắt đầu:</strong> ${item.start_date || 'N/A'}</p>
                        <p><strong>Ngày kết thúc:</strong> ${item.end_date || 'N/A'}</p>
                    `;
                }
                
                document.getElementById('rental-info-content').innerHTML = content;
            }
        }

        async function updateListing() {
            if (!confirm('Lưu thay đổi?')) return;
            
            const data = {
                id: document.getElementById('edit-id').value,
                title: document.getElementById('edit-title').value,
                price: document.getElementById('edit-price').value,
                room_type: document.getElementById('edit-type').value,
                address: document.getElementById('edit-address').value,
                district: document.getElementById('edit-district').value,
                city: document.getElementById('edit-city').value,
                status: document.getElementById('edit-status').value,
                description: document.getElementById('edit-desc').value
            };

            try {
                const res = await fetch('/api/rentals.php?action=update_listing', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    alert('Cập nhật thành công!');
                    loadData(); 
                } else {
                    alert('Lỗi: ' + (result.error || 'Unknown'));
                }
            } catch (err) {
                alert('Lỗi kết nối');
            }
        }
        
        async function confirmBooking() {
            if (!confirm('Xác nhận cho thuê phòng này? Bài đăng sẽ được chuyển sang trạng thái "Đã thuê" và ẩn khỏi danh sách tìm kiếm.')) return;
            const bookingId = document.getElementById('booking-id').value;
             try {
                const res = await fetch('/api/rentals.php?action=confirm_booking', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({booking_id: bookingId})
                });
                const result = await res.json();
                if (result.success) {
                    alert('Đã xác nhận cho thuê! Thông tin người thuê đã được cập nhật.');
                    document.getElementById('pending-request-ui').style.display = 'none';
                    loadData(); 
                } else {
                    alert('Lỗi: ' + (result.error || 'Unknown'));
                }
            } catch (err) {
                alert('Lỗi kết nối');
            }
        }
        
        async function rejectBooking() {
             if (!confirm('Từ chối yêu cầu thuê này?')) return;
             const bookingId = document.getElementById('booking-id').value;
             try {
                const res = await fetch('/api/rentals.php?action=reject_booking', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({booking_id: bookingId})
                });
                const result = await res.json();
                if (result.success) {
                    alert('Đã từ chối yêu cầu.');
                    document.getElementById('pending-request-ui').style.display = 'none';
                    loadData(); 
                } else {
                    alert('Lỗi: ' + (result.error || 'Unknown'));
                }
            } catch (err) {
                alert('Lỗi kết nối');
            }
        }

        async function deleteListing() {
            if (!confirm('Bạn có chắc muốn xóa nhà này? Hành động này sẽ xóa cả hình ảnh và không thể hoàn tác.')) return;
            
            try {
                const res = await fetch('/api/rentals.php?action=delete_listing', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: selectedId})
                });
                const result = await res.json();
                if (result.success) {
                    alert('Đã xóa!');
                    resetRightColumn();
                    loadData();
                } else {
                    alert('Lỗi: ' + (result.error || 'Unknown'));
                }
            } catch (err) {
                alert('Lỗi kết nối');
            }
        }

        function getStatusText(status) {
            const map = {
                'available': 'Còn trống',
                'booked': 'Đã thuê',
                'inactive': 'Tạm ẩn'
            };
            return map[status] || status;
        }
    </script>
</body>
</html>
