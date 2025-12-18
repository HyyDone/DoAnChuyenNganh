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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <button class="nav-btn" onclick="switchView('contracts', this)">
                <i class="fa-solid fa-file-contract"></i> Hợp đồng
            </button>
            <button class="nav-btn" onclick="switchView('viewings', this)">
                <i class="fa-regular fa-calendar-check"></i> Lịch xem phòng
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

                <!-- Confirmed Tenant Box -->
                <div id="confirmed-tenant-ui" style="display:none;">
                    <div class="request-box" style="background:#e6f4ea; border-color:#c3e6cb; color:#0f5132;">
                        <h4 style="margin-top:0"><i class="fa-solid fa-user-check"></i> Thông tin người thuê</h4>
                        <p><strong>Họ tên:</strong> <span id="conf-name"></span></p>
                        <p><strong>SĐT:</strong> <span id="conf-phone"></span></p>
                        <p><strong>Email:</strong> <span id="conf-email"></span></p>
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
                    <label class="form-label">Diện tích (m²)</label>
                    <input type="number" id="edit-area" class="form-input" step="0.1">
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
                <div id="rental-actions" style="margin-top:20px;">
                    <!-- Buttons injected by JS -->
                </div>
            </div>

            <!-- PAYMENT FORM -->
            <div id="payment-form" class="editor-form">
                <h3><i class="fa-solid fa-money-bill-wave"></i> Đóng tiền trọ</h3>
                <form onsubmit="submitPayment(event)">
                    <input type="hidden" id="pay-listing-id">
                    
                    <div class="form-group">
                        <label class="form-label">Phòng</label>
                        <input type="text" id="pay-title" class="form-input" readonly style="background:#eee;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Lời nhắn</label>
                        <textarea id="pay-message" class="form-textarea" placeholder="VD: Em gửi tiền trọ tháng 10..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Hình ảnh chuyển khoản</label>
                        <input type="file" id="pay-image" class="form-input" accept="image/*">
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Gửi thông báo</button>
                        <button type="button" class="btn" style="background:#eee;" onclick="document.getElementById('payment-form').classList.remove('active'); document.getElementById('rental-details').classList.add('active');">Hủy</button>
                    </div>
                </form>
            </div>

            <!-- DAMAGE REPORT FORM (TENANT) -->
            <div id="damage-report-form" class="editor-form">
                <h3><i class="fa-solid fa-triangle-exclamation"></i> Báo cáo hư hại</h3>
                <form onsubmit="submitDamageReport(event)">
                    <input type="hidden" id="damage-booking-id">
                    
                    <div class="form-group">
                        <label class="form-label">Tiêu đề</label>
                        <input type="text" id="damage-title" class="form-input" placeholder="VD: Hỏng vòi nước..." required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mô tả chi tiết</label>
                        <textarea id="damage-desc" class="form-textarea" placeholder="Mô tả tình trạng hư hỏng..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Chi phí dự kiến (VNĐ)</label>
                        <input type="number" id="damage-cost" class="form-input" required>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-danger">Gửi báo cáo</button>
                        <button type="button" class="btn" style="background:#eee;" onclick="document.getElementById('damage-report-form').classList.remove('active'); document.getElementById('rental-details').classList.add('active');">Hủy</button>
                    </div>
                </form>
            </div>

            <!-- DAMAGE REPORT DETAIL (OWNER) -->
            <div id="damage-report-detail" class="editor-form">
                <div class="request-box" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba;">
                    <h4 style="margin-top:0"><i class="fa-solid fa-circle-exclamation"></i> Có báo cáo hư hại</h4>
                    <p><strong>Tiêu đề:</strong> <span id="rpt-title"></span></p>
                    <p><strong>Mô tả:</strong> <span id="rpt-desc"></span></p>
                    <p><strong>Chi phí dự kiến:</strong> <span id="rpt-cost" style="color:#dc3545; font-weight:bold;"></span></p>
                    <div style="margin-top:15px; display:flex; gap:10px;">
                        <button class="btn btn-success" onclick="confirmDamageReport()">Xác nhận</button>
                        <button class="btn" onclick="document.getElementById('damage-report-detail').classList.remove('active');">Đóng</button>
                    </div>
                </div>
                <input type="hidden" id="rpt-id">
            </div>

            <!-- CONTRACT VIEW (RIGHT COLUMN) -->
            <div id="contract-view" class="editor-form">
                <h3><i class="fa-solid fa-file-contract"></i> Quản lý hợp đồng</h3>
                <div id="contract-info-content"></div>
                <div id="contract-actions-container" style="margin-top:20px;"></div>
            </div>

            <div id="viewing-details" class="editor-form">
                <h3><i class="fa-regular fa-calendar-check"></i> Chi tiết lịch hẹn</h3>
                <div id="viewing-info-content"></div>
            </div>

            <!-- STATS PAYMENT FORM -->
            <div id="stats-payment-form" class="editor-form">
                <h3><i class="fa-solid fa-money-bill-wave"></i> Ghi nhận doanh thu</h3>
                <form onsubmit="submitStatsPayment(event)">
                    <div class="form-group">
                        <label class="form-label">Chọn phòng đã thuê</label>
                        <select id="stat-pay-booking" class="form-select" required onchange="onBookingSelect(this)">
                            <option value="">-- Chọn phòng --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Người thuê</label>
                        <input type="text" id="stat-pay-tenant" class="form-input" readonly style="background:#eee;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Giá phòng</label>
                        <input type="text" id="stat-pay-price" class="form-input" readonly style="background:#eee;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Số tiền thực nhận</label>
                        <input type="number" id="stat-pay-amount" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ngày thu tiền</label>
                        <input type="date" id="stat-pay-date" class="form-input" required value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cho tháng (Tính vào doanh thu tháng này)</label>
                        <input type="month" id="stat-pay-for-month" class="form-input" required value="<?php echo date('Y-m'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ghi chú</label>
                        <textarea id="stat-pay-note" class="form-textarea"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;">Lưu</button>
                </form>
            </div>
        </div>
    </div>

    <!-- CONTRACT POPUPS (MODALS) -->
    
    <!-- CREATE CONTRACT MODAL -->
    <div id="create-contract-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
        <div style="background:#f0f0f0; width:900px; max-width:95%; max-height:95vh; border-radius:8px; padding:20px; display:flex; flex-direction:column; box-shadow:0 4px 20px rgba(0,0,0,0.2);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3 style="margin:0">Tạo hợp đồng thuê nhà</h3>
                <button onclick="document.getElementById('create-contract-modal').style.display='none'" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            
            <form onsubmit="submitContract(event)" style="display:flex; flex-direction:column; flex:1; overflow:hidden; gap:15px;">
                <input type="hidden" id="create-contract-booking-id">
                
                <!-- A4 Paper Container -->
                <div style="flex:1; overflow-y:auto; background:#525659; padding:20px; display:flex; justify-content:center;">
                    <div style="background:white; width:210mm; min-height:297mm; padding:25mm; box-shadow:0 0 10px rgba(0,0,0,0.5); box-sizing:border-box;">
                         <textarea id="contract-content-input" class="form-textarea" style="width:100%; height:100%; border:none; resize:none; font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; outline:none; padding:0;"></textarea>
                    </div>
                </div>

                <div class="btn-group" style="padding:10px; background:white; border-top:1px solid #ddd; margin-top:0;">
                    <button type="submit" class="btn btn-primary">Xác nhận tạo hợp đồng</button>
                    <button type="button" class="btn" style="background:#eee;" onclick="document.getElementById('create-contract-modal').style.display='none'">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW CONTRACT MODAL -->
    <div id="view-contract-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
         <div style="background:#f0f0f0; width:900px; max-width:95%; max-height:95vh; border-radius:8px; padding:20px; display:flex; flex-direction:column; box-shadow:0 4px 20px rgba(0,0,0,0.2);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3 style="margin:0">Chi tiết hợp đồng</h3>
                <button onclick="document.getElementById('view-contract-modal').style.display='none'" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>

            <!-- A4 Paper Container -->
            <div style="flex:1; overflow-y:auto; background:#525659; padding:20px; display:flex; justify-content:center;">
                <div id="view-contract-content" style="background:white; width:210mm; min-height:297mm; padding:25mm; box-shadow:0 0 10px rgba(0,0,0,0.5); box-sizing:border-box; font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; white-space:pre-wrap;"></div>
            </div>

            <input type="hidden" id="view-contract-id">
            <div class="btn-group" style="padding:10px; background:white; border-top:1px solid #ddd; margin-top:0;">
                <button id="btn-sign-contract" type="button" class="btn btn-success" onclick="signContract()" style="display:none;">Xác nhận (Ký)</button>
                <button type="button" class="btn" style="background:#eee;" onclick="document.getElementById('view-contract-modal').style.display='none'">Thoát</button>
            </div>
         </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        const USER_ID = <?= $_SESSION['user_id'] ?>;
        let currentView = 'my_listings';
        let currentItems = [];
        let selectedId = null;

        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const view = urlParams.get('view');
            if (view && ['my_listings', 'my_rentals', 'stats', 'viewings'].includes(view)) {
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
                'contracts': 'Quản lý hợp đồng',
                'stats': 'Thống kê',
                'viewings': 'Lịch xem phòng'
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
                container.innerHTML = `
                    <div style="background:white; padding:20px; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,0.1);">
                        <div style="display:flex; gap:10px; margin-bottom:20px;">
                            <select id="stats-month" class="form-select" style="width:auto;" onchange="loadStats()">
                                <option value="0">Cả năm</option>
                                ${Array.from({length: 12}, (_, i) => `<option value="${i+1}">Tháng ${i+1}</option>`).join('')}
                            </select>
                            <select id="stats-year" class="form-select" style="width:auto;" onchange="loadStats()">
                                ${Array.from({length: new Date().getFullYear() - 2019}, (_, i) => {
                                    const y = 2020 + i;
                                    return `<option value="${y}" ${y === new Date().getFullYear() ? 'selected' : ''}>${y}</option>`;
                                }).join('')}
                            </select>
                        </div>
                        <div style="height:350px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                `;
                loadStats();
                
                loadStats();
                
                resetRightColumn();
                document.querySelector('.empty-state').style.display = 'none';
                document.getElementById('stats-payment-form').classList.add('active');
                
                if (document.getElementById('stat-pay-booking').options.length <= 1) {
                    loadActiveRentalsForStats();
                }
                return;
            }

            try {
                let action = '';
                if (currentView === 'my_listings') action = 'get_my_listings';
                else if (currentView === 'my_rentals') action = 'get_my_rentals';
                else if (currentView === 'contracts') action = 'get_contracts';
                else if (currentView === 'viewings') action = 'get_viewing_appointments';
                
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
            
            if (currentView === 'viewings') {
                let html = '';
                // Owner Section
                if (items.owner && items.owner.length > 0) {
                    html += '<h4 style="margin-bottom:10px; color:#0866ff;">Khách muốn xem phòng của bạn</h4>';
                    items.owner.forEach(item => {
                        const date = new Date(item.appointment_time).toLocaleString('vi-VN');
                        const statusBadge = getStatusBadge(item.status);
                        html += `
                            <div class="listing-card" onclick='selectViewing(${JSON.stringify(item)}, "owner")'>
                                <div class="listing-header">
                                    <div class="listing-title">${item.title}</div>
                                    ${statusBadge}
                                </div>
                                <div style="font-size:0.9rem; margin-top:5px;">
                                    <i class="fa-regular fa-clock"></i> ${date}
                                </div>
                                <div class="tenant-info">
                                    <i class="fa-solid fa-user"></i> ${item.tenant_name} (${item.tenant_phone})
                                </div>
                            </div>
                        `;
                    });
                     html += '<hr style="margin:20px 0;">';
                }

                // Tenant Section
                if (items.tenant && items.tenant.length > 0) {
                     html += '<h4 style="margin-bottom:10px; color:#0866ff;">Lịch hẹn của bạn</h4>';
                     items.tenant.forEach(item => {
                        const date = new Date(item.appointment_time).toLocaleString('vi-VN');
                        const statusBadge = getStatusBadge(item.status);
                        html += `
                            <div class="listing-card" onclick='selectViewing(${JSON.stringify(item)}, "tenant")'>
                                <div class="listing-header">
                                    <div class="listing-title">${item.title}</div>
                                    ${statusBadge}
                                </div>
                                <div style="font-size:0.9rem; margin-top:5px;">
                                    <i class="fa-regular fa-clock"></i> ${date}
                                </div>
                                <div class="tenant-info">
                                    <i class="fa-solid fa-house-user"></i> Chủ nhà: ${item.owner_name}
                                </div>
                            </div>
                        `;
                    });
                }
                
                if (!html) html = '<p>Chưa có lịch hẹn.</p>';
                container.innerHTML = html;
                return;
            }

            if (!Array.isArray(items) || items.length === 0) {
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
                            <div class="tenant-info" style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                                <span style="font-size:0.9rem;">
                                    <i class="fa-solid fa-user-check" style="margin-right:8px"></i> 
                                    Đang thuê: ${item.tenant_name || 'Người dùng'} (${item.tenant_phone || 'SĐT: N/A'})
                                </span>
                                <button class="btn" style="background:#ffc107; color:#000; padding:4px 12px; font-size:0.75rem; width:auto !important; min-width:60px; height:auto; display:inline-block;" onclick="event.stopPropagation(); stopRenting(${item.id})">
                                    Dừng
                                </button>
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
                document.getElementById('edit-area').value = item.area || '';
                document.getElementById('edit-type').value = item.room_type;
                document.getElementById('edit-address').value = item.address;
                document.getElementById('edit-district').value = item.district;
                document.getElementById('edit-city').value = item.city;
                document.getElementById('edit-status').value = item.status;
                document.getElementById('edit-status').value = item.status;
                document.getElementById('edit-desc').value = item.description;

                // Show/Hide Confirmed Tenant Section (Merged from duplicate block)
                const confirmedUI = document.getElementById('confirmed-tenant-ui');
                if (item.booking_status === 'confirmed') {
                    confirmedUI.style.display = 'block';
                    document.getElementById('conf-name').innerText = item.tenant_name || 'Không có tên';
                    document.getElementById('conf-phone').innerText = item.tenant_phone || 'N/A';
                    document.getElementById('conf-email').innerText = item.tenant_email || 'N/A';
                } else {
                    confirmedUI.style.display = 'none';
                }

            } else if (currentView === 'my_rentals') {
                const view = document.getElementById('rental-details');
                view.classList.add('active');
                
                let content = `
                    <p><strong>Tiêu đề:</strong> ${item.title}</p>
                    <p><strong>Giá thuê:</strong> ${parseInt(item.price).toLocaleString()} đ/tháng</p>
                    <p><strong>Diện tích:</strong> ${item.area ? item.area + ' m²' : 'N/A'}</p>
                    <p><strong>Địa chỉ:</strong> ${item.address}, ${item.district}, ${item.city}</p>
                    <hr>
                `;
                
                let actions = '';

                if (item.booking_status === 'pending') {
                    content += `<p style="color:orange; font-weight:bold;">Yêu cầu thuê của bạn đang chờ chủ nhà xác nhận.</p>`;
                } else {
                    content += `
                        <p><strong>Chủ nhà:</strong> ${item.owner_name || 'N/A'}</p>
                        <p><strong>Liên hệ:</strong> ${item.owner_phone || 'N/A'}</p>
                        <p><strong>Ngày bắt đầu:</strong> ${item.start_date || 'N/A'}</p>
                        <p><strong>Ngày kết thúc:</strong> ${item.end_date || 'N/A'}</p>
                    `;
                    actions = `
                        <button class="btn btn-primary" onclick="showPaymentForm(${item.id}, '${item.title}')">Đóng tiền trọ</button>
                        <button class="btn btn-danger" style="margin-top:10px;" onclick="showDamageForm(${item.booking_id})">Báo cáo hư hại</button>
                    `;
                }
                
                document.getElementById('rental-info-content').innerHTML = content;
                document.getElementById('rental-actions').innerHTML = actions;

            } else if (currentView === 'contracts') {
                 const view = document.getElementById('contract-view');
                 view.classList.add('active');
                 
                 let html = `
                    <p><strong>Nhà:</strong> ${item.title}</p>
                    <p><strong>Địa chỉ:</strong> ${item.address}, ${item.district}, ${item.city}</p>
                    <p><strong>Chủ nhà:</strong> ${item.owner_name}</p>
                    <p><strong>Người thuê:</strong> ${item.tenant_name}</p>
                    <p><strong>Thời hạn:</strong> ${item.start_date} - ${item.end_date}</p>
                    <hr>
                 `;
                 
                 let btns = '';
                 
                 if (!item.contract_id) {
                     // No contract -> Create
                     btns = `<button class="btn btn-primary" onclick="openCreateContract(${item.booking_id})">Làm hợp đồng</button>`;
                 } else {
                     // Has contract -> View / Delete
                     btns = `
                        <button class="btn btn-primary" onclick="viewContract(${item.contract_id})">Xem hợp đồng</button>
                        <button class="btn btn-danger" onclick="deleteContract(${item.contract_id})">Xóa hợp đồng</button>
                     `;
                 }
                 
                 document.getElementById('contract-info-content').innerHTML = html;
                 document.getElementById('contract-actions-container').innerHTML = btns;




            }
        }

        function selectViewing(item, role) {
            resetRightColumn();
            document.querySelector('.empty-state').style.display = 'none';
            document.getElementById('viewing-details').classList.add('active');
            
            const date = new Date(item.appointment_time).toLocaleString('vi-VN');
            let content = `
                <p><strong>Phòng:</strong> ${item.title}</p>
                <p><strong>Địa chỉ:</strong> ${item.address}</p>
                <p><strong>Thời gian:</strong> ${date}</p>
                <p><strong>Trạng thái:</strong> ${item.status.toUpperCase()}</p>
                <hr>
            `;

            if (role === 'owner') {
                content += `
                    <p><strong>Người xem:</strong> ${item.tenant_name}</p>
                    <p><strong>SĐT:</strong> <a href="tel:${item.tenant_phone}">${item.tenant_phone}</a></p>
                    <br>
                `;
                if (item.status === 'pending') {
                    content += `
                        <button class="btn btn-success" onclick="updateViewingStatus(${item.id}, 'confirmed')">Xác nhận</button>
                        <button class="btn btn-danger" onclick="updateViewingStatus(${item.id}, 'cancelled')">Từ chối</button>
                    `;
                } else if (item.status === 'confirmed') {
                     content += `
                        <button class="btn btn-primary" onclick="updateViewingStatus(${item.id}, 'completed')">Đã xem xong</button>
                        <button class="btn btn-danger" onclick="updateViewingStatus(${item.id}, 'cancelled')">Hủy lịch</button>
                    `;
                } else if (item.status === 'completed' || item.status === 'cancelled') {
                     content += `
                        <button class="btn btn-danger" onclick="deleteViewing(${item.id})">Xóa lịch sử này</button>
                    `;
                }
            } else {
                content += `
                    <p><strong>Chủ nhà:</strong> ${item.owner_name}</p>
                    <p><strong>Liên hệ:</strong> <a href="tel:${item.owner_phone}">${item.owner_phone}</a></p>
                    <br>
                `;
                if (item.status === 'pending' || item.status === 'confirmed') {
                     content += `
                        <button class="btn btn-danger" onclick="updateViewingStatus(${item.id}, 'cancelled')">Hủy lịch hẹn</button>
                    `;
                } else if (item.status === 'completed' || item.status === 'cancelled') {
                     content += `
                        <button class="btn btn-danger" onclick="deleteViewing(${item.id})">Xóa lịch sử này</button>
                    `;
                }
            }
            
            document.getElementById('viewing-info-content').innerHTML = content;
        }

        async function updateViewingStatus(id, status) {
            if (!confirm('Xác nhận thay đổi trạng thái?')) return;
            try {
                const res = await fetch('/api/rentals.php?action=update_viewing_status', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id, status: status})
                });
                const result = await res.json();
                if (result.success) {
                    alert('Cập nhật thành công!');
                    resetRightColumn();
                    loadData();
                } else {
                    alert('Lỗi: ' + (result.error || 'Unknown'));
                }
            } catch (e) { alert('Lỗi kết nối'); }
        }

        async function deleteViewing(id) {
            if (!confirm('Bạn có chắc chắn muốn xóa lịch sử xem phòng này?')) return;
            try {
                const res = await fetch('/api/rentals.php?action=delete_viewing', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                });
                const result = await res.json();
                if (result.success) {
                    alert('Đã xóa!');
                    resetRightColumn();
                    loadData();
                } else {
                    alert('Lỗi: ' + (result.error || 'Unknown'));
                }
            } catch (e) { alert('Lỗi kết nối'); }
        }

        async function updateListing() {
            if (!confirm('Lưu thay đổi?')) return;
            
            const data = {
                id: document.getElementById('edit-id').value,
                title: document.getElementById('edit-title').value,
                price: document.getElementById('edit-price').value,
                area: document.getElementById('edit-area').value,
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
                alert('Lỗi kết nối: ' + err.message);
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
                alert('Lỗi kết nối: ' + err.message);
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

        async function stopRenting(id) {
             const targetId = id || selectedId;
             if (!targetId) return;
             if (!confirm('Dừng cho thuê nhà này? Hành động này sẽ xóa thông tin người thuê hiện tại và chuyển trạng thái nhà sang "Tạm ẩn".')) return;
             
             try {
                // Correction: URL needs action
                const res2 = await fetch('/api/rentals.php?action=stop_renting', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: targetId})
                });
                
                const result = await res2.json();
                if (result.success) {
                    alert('Đã dừng cho thuê.');
                    resetRightColumn();
                    loadData();
                } else {
                    alert('Lỗi: ' + (result.message || result.error || 'Unknown'));
                }
             } catch(e) { console.error(e); alert('Lỗi kết nối: ' + e.message); }
        }

        function showPaymentForm(id, title) {
            document.getElementById('rental-details').classList.remove('active');
            const form = document.getElementById('payment-form');
            form.classList.add('active');
            
            document.getElementById('pay-listing-id').value = id;
            document.getElementById('pay-title').value = title;
            document.getElementById('pay-message').value = '';
            document.getElementById('pay-image').value = '';
        }

        async function submitPayment(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerText = 'Đang gửi...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('listing_id', document.getElementById('pay-listing-id').value);
            formData.append('message', document.getElementById('pay-message').value);
            
            const fileInput = document.getElementById('pay-image');
            if (fileInput.files[0]) {
                formData.append('image', fileInput.files[0]);
            }

            try {
                const res = await fetch('/api/rentals.php?action=report_payment', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                
                if (result.success) {
                    alert('Đã gửi thông báo đóng tiền thành công!');
                    document.getElementById('payment-form').classList.remove('active');
                    document.getElementById('rental-details').classList.add('active');
                } else {
                    alert('Lỗi: ' + (result.message || 'Có lỗi xảy ra'));
                }
            } catch (err) {
                console.error(err);
                alert('Lỗi kết nối');
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }

        // --- Damage Reporting Logic ---

        function showDamageForm(bookingId) {
            if (!bookingId) {
                alert('Không tìm thấy thông tin hợp đồng.');
                return;
            }
            document.getElementById('rental-details').classList.remove('active');
            document.getElementById('damage-report-form').classList.add('active');
            
            document.getElementById('damage-booking-id').value = bookingId;
            document.getElementById('damage-title').value = '';
            document.getElementById('damage-desc').value = '';
            document.getElementById('damage-cost').value = '';
        }

        async function submitDamageReport(e) {
            e.preventDefault();
            if (!confirm('Gửi báo cáo này cho chủ nhà?')) return;

            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerText = 'Đang gửi...';
            btn.disabled = true;

            const data = {
                booking_id: document.getElementById('damage-booking-id').value,
                title: document.getElementById('damage-title').value,
                description: document.getElementById('damage-desc').value,
                cost: document.getElementById('damage-cost').value
            };

            try {
                const res = await fetch('/api/rentals.php?action=report_damage', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                
                if (result.success) {
                    alert('Báo cáo đã gửi thành công!');
                    document.getElementById('damage-report-form').classList.remove('active');
                    document.getElementById('rental-details').classList.add('active');
                } else {
                    alert('Lỗi: ' + (result.message || result.error || 'Unknown'));
                }
            } catch (err) {
                console.error(err);
                alert('Lỗi kết nối');
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }

        // Check for report view param
        const rptId = new URLSearchParams(window.location.search).get('view_report');
        if (rptId) {
            loadDamageReport(rptId);
        }

        async function loadDamageReport(id) {
            try {
                const res = await fetch(`/api/rentals.php?action=get_damage_report&id=${id}`);
                const data = await res.json();
                
                if (data.error) {
                    alert(data.error);
                    return;
                }
                
                const popup = document.getElementById('damage-report-detail');
                // Ensure right column is visible/reset
                 // Force switch to my_listings if owner? user might be owner.
                 // We don't change view automatically unless needed, but popup needs to be in DOM.
                 // It is in layout-right.
                resetRightColumn(); // Clear others
                popup.classList.add('active');

                document.getElementById('rpt-id').value = data.id;
                document.getElementById('rpt-title').innerText = data.title;
                document.getElementById('rpt-desc').innerText = data.description;
                document.getElementById('rpt-cost').innerText = parseInt(data.cost).toLocaleString() + ' đ';
                
                if (data.status !== 'pending') {
                    // Hide confirm button if not pending
                     const btn = popup.querySelector('.btn-success');
                     if(btn) btn.style.display = 'none';
                }

            } catch (e) {
                console.error(e);
            }
        }

        async function confirmDamageReport() {
            if (!confirm('Xác nhận báo cáo hư hại này?')) return;
            const id = document.getElementById('rpt-id').value;
            
            try {
                const res = await fetch('/api/rentals.php?action=confirm_damage_report', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({report_id: id})
                });
                const result = await res.json();
                
                if (result.success) {
                    alert('Đã xác nhận!');
                    document.getElementById('damage-report-detail').classList.remove('active');
                    // Maybe refresh?
                } else {
                    alert('Lỗi: ' + (result.error || 'Unknown'));
                }
            } catch (e) {
                alert('Lỗi kết nối');
            }
        }

        // --- CONTRACTS LOGIC ---
        
        const CONTRACT_TEMPLATE = `HỢP ĐỒNG THUÊ NHÀ
1. Thông tin các bên
• Bên cho thuê: ..........................................................
  CCCD: ....................   SĐT: ..............................
• Bên thuê: ................................................................
  CCCD: ....................   SĐT: ..............................
2. Thông tin nhà/ phòng cho thuê
Địa chỉ: ........................................................................
Diện tích: ..................... m²
Tình trạng bàn giao: ............................................................
3. Thời hạn thuê
Từ ngày ....... đến ngày .......
Gia hạn: .......................................................................
4. Giá thuê và thanh toán
Giá thuê: ..................... VNĐ/tháng
Bao gồm phí: điện/nước/internet/... (ghi rõ)
Hình thức thanh toán: tiền mặt / chuyển khoản.
5. Tiền đặt cọc
Số tiền cọc: ............................... VNĐ
Thời điểm và điều kiện hoàn trả: ..............................................
6. Quyền và nghĩa vụ của các bên
• Bên cho thuê: bảo trì kết cấu lớn, đảm bảo quyền sử dụng hợp pháp...
• Bên thuê: giữ gìn tài sản, không cho thuê lại khi chưa được phép...
7. Tài sản đi kèm
Danh sách tài sản bàn giao:
....................................................................................
8. Chấm dứt hợp đồng
Các trường hợp chấm dứt: hết hạn, vi phạm nghĩa vụ, thỏa thuận...
9. Điều khoản chung
Hai bên cam kết tuân thủ và cùng giải quyết tranh chấp theo pháp luật.
10. Chữ ký
Bên cho thuê: .............................................
Bên thuê: ....................................................`;

        function openCreateContract(bookingId) {
            document.getElementById('create-contract-modal').style.display = 'flex';
            document.getElementById('create-contract-booking-id').value = bookingId;
            document.getElementById('contract-content-input').value = CONTRACT_TEMPLATE;
        }

        async function submitContract(e) {
            e.preventDefault();
            if(!confirm('Xác nhận tạo hợp đồng?')) return;
            
            const data = {
                booking_id: document.getElementById('create-contract-booking-id').value,
                content: document.getElementById('contract-content-input').value
            };
            
            try {
                const res = await fetch('/api/rentals.php?action=create_contract', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if(result.success) {
                    alert('Hợp đồng đã được tạo và gửi cho người thuê!');
                    document.getElementById('create-contract-modal').style.display = 'none';
                    loadData();
                } else {
                    alert('Lỗi: ' + (result.error || 'Unknown'));
                }
            } catch(e) { alert('Lỗi kết nối'); }
        }

        async function viewContract(id) {
            try {
                const res = await fetch(`/api/rentals.php?action=get_contract_details&id=${id}`);
                const data = await res.json();
                if(data.error) {
                    alert(data.error); return;
                }
                
                document.getElementById('view-contract-modal').style.display = 'flex';
                document.getElementById('view-contract-content').innerText = data.content;
                document.getElementById('view-contract-id').value = id;
                
                // If I am tenant and status is pending, show Sign button
                // How do I know if I am tenant? 
                // data object should help. But session user id is not in JS.
                // However, the action 'sign_contract' checks backend.
                // We can just show the button if status is pending? 
                // Or try to detect.
                // data.tenant_id is from backend. We don't have user_id locally easily unless we inject it.
                // Let's inject user_id in PHP at the top.
                
                const btnSign = document.getElementById('btn-sign-contract');
                if (data.status === 'pending') {
                    // Check if current user is tenant
                   if (window.USER_ID && window.USER_ID == data.tenant_id) {
                        btnSign.style.display = 'inline-block';
                   } else {
                       btnSign.style.display = 'none';
                   }
                } else {
                    btnSign.style.display = 'none';
                }
                
            } catch(e) { alert('Lỗi kết nối'); }
        }

        async function signContract() {
            if(!confirm('Tôi đã đọc và đồng ý ký vào hợp đồng này.')) return;
            const id = document.getElementById('view-contract-id').value;
            
            try {
                const res = await fetch('/api/rentals.php?action=sign_contract', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                });
                const result = await res.json();
                if(result.success) {
                    alert('Đã ký hợp đồng!');
                    document.getElementById('view-contract-modal').style.display = 'none';
                    loadData();
                } else {
                    alert('Lỗi: ' + (result.error || 'Unknown'));
                }
            } catch(e) { alert('Lỗi kết nối'); }
        }

        async function deleteContract(id) {
            if(!confirm('Xóa hợp đồng này? Hành động này không thể hoàn tác.')) return;
             try {
                const res = await fetch('/api/rentals.php?action=delete_contract', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                });
                const result = await res.json();
                if(result.success) {
                    alert('Đã xóa hợp đồng!');
                    resetRightColumn();
                    loadData();
                } else {
                    alert('Lỗi: ' + (result.error || 'Unknown'));
                }
            } catch(e) { alert('Lỗi kết nối'); }
        }
        function getStatusBadge(status) {
             if(status === 'pending') return '<span style="color:orange; font-weight:bold;">Chờ xác nhận</span>';
             else if(status === 'confirmed') return '<span style="color:green; font-weight:bold;">Đã xác nhận</span>';
             else if(status === 'cancelled') return '<span style="color:red; font-weight:bold;">Đã hủy</span>';
             else if(status === 'completed') return '<span style="color:blue; font-weight:bold;">Đã hoàn thành</span>';
             return '<span style="color:grey; font-weight:bold;">' + status + '</span>';
        }
        let revenueChart = null;

        async function loadStats() {
            const year = document.getElementById('stats-year').value;
            const month = document.getElementById('stats-month').value;
            
            try {
                const res = await fetch(`/api/rentals.php?action=get_payment_stats&year=${year}&month=${month}`);
                const data = await res.json();
                
                const canvas = document.getElementById('revenueChart');
                if (!canvas) return; 
                const ctx = canvas.getContext('2d');
                
                if (revenueChart) revenueChart.destroy();
                
                // If month is 0 (All year), we expect data keys 1-12
                // If month > 0, we expect data key 'month'
                
                let labels, values;
                if (month > 0) {
                     labels = [`Tháng ${month}`];
                     values = [data[month] || 0];
                } else {
                     labels = Array.from({length: 12}, (_, i) => `Tháng ${i+1}`);
                     // Ensure data is array or object keyed by 1-12. API returns {1: val, 2: val...}
                     values = [];
                     for(let i=1; i<=12; i++) values.push(data[i] || 0);
                }

                revenueChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Doanh thu (VNĐ)',
                            data: values,
                            backgroundColor: '#0866ff',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            } catch (e) { console.error(e); }
        }

        async function loadActiveRentalsForStats() {
            try {
                const res = await fetch('/api/rentals.php?action=get_active_rentals_for_stats');
                const rentals = await res.json();
                const select = document.getElementById('stat-pay-booking');
                select.innerHTML = '<option value="">-- Chọn phòng --</option>';
                if (Array.isArray(rentals)) {
                    rentals.forEach(r => {
                        const opt = document.createElement('option');
                        opt.value = r.booking_id;
                        opt.text = r.title;
                        opt.dataset.tenant = r.tenant_name;
                        opt.dataset.price = r.price;
                        select.appendChild(opt);
                    });
                }
            } catch (e) {
                console.error(e);
            }
        }

        function onBookingSelect(select) {
            const opt = select.options[select.selectedIndex];
            if (opt.value) {
                document.getElementById('stat-pay-tenant').value = opt.dataset.tenant || '';
                document.getElementById('stat-pay-price').value = parseInt(opt.dataset.price).toLocaleString() + ' đ';
                document.getElementById('stat-pay-amount').value = opt.dataset.price;
            } else {
                document.getElementById('stat-pay-tenant').value = '';
                document.getElementById('stat-pay-price').value = '';
                document.getElementById('stat-pay-amount').value = '';
            }
        }

        async function submitStatsPayment(e) {
            e.preventDefault();
            const bookingId = document.getElementById('stat-pay-booking').value;
            const amount = document.getElementById('stat-pay-amount').value;
            const date = document.getElementById('stat-pay-date').value;
            const forMonth = document.getElementById('stat-pay-for-month').value;
            const note = document.getElementById('stat-pay-note').value;
            if (!bookingId) {
                alert('Vui lòng chọn phòng!');
                return;
            }
            try {
                const res = await fetch('/api/rentals.php?action=add_payment', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ booking_id: bookingId, amount: amount, date: date, payment_for_month: forMonth, note: note })
                });
                const result = await res.json();
                if (result.success) {
                    alert('Đã lưu thông tin thanh toán!');
                    loadStats(); 
                    document.getElementById('stat-pay-booking').value = '';
                    onBookingSelect(document.getElementById('stat-pay-booking'));
                    document.getElementById('stat-pay-note').value = '';
                } else {
                    alert('Lỗi: ' + (result.error || result.message));
                }
            } catch (err) {
                console.error(err);
                alert('Có lỗi xảy ra.');
            }
        }
    </script>
</body>
</html>
