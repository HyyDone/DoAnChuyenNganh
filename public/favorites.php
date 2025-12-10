<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Get user info for booking form
$stmt = $pdo->prepare("SELECT full_name, phone FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Phòng trọ yêu thích</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }
        .fav-container {
            max-width: 1200px;
            margin: 20px auto;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            padding: 0 15px;
        }
        .fav-list {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .fav-item {
            display: flex;
            gap: 15px;
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            cursor: pointer;
            transition: background 0.2s;
        }
        .fav-item:hover, .fav-item.active {
            background: #f9f9f9;
        }
        .fav-item:first-child { padding-top: 0; }
        .fav-item:last-child { border-bottom: none; padding-bottom: 0; }
        
        .fav-img {
            width: 120px;
            height: 90px;
            object-fit: cover;
            border-radius: 4px;
        }
        .fav-info {
            flex: 1;
        }
        .fav-title { font-weight: bold; font-size: 1.1rem; color: #333; margin-bottom: 5px; }
        .fav-price { color: #dc3545; font-weight: bold; margin-bottom: 5px; }
        .fav-address { color: #666; font-size: 0.9rem; }
        
        .fav-actions {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
        }
        .btn-remove {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 5px;
        }
        .btn-view {
            background: #e4e6eb;
            color: black;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        /* Right Column */
        .booking-panel {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .panel-title { font-size: 1.2rem; font-weight: bold; margin-bottom: 15px; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        
        .selected-room-info {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-submit { width: 100%; background: #0866ff; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: #0056b3; }
        
        .empty-state { text-align: center; padding: 40px; color: #666; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="fav-container">
        <!-- Left: List -->
        <div class="fav-list" id="favList">
            <div style="text-align:center;">Đang tải...</div>
        </div>

        <!-- Right: Booking -->
        <div class="booking-panel">
            <div class="panel-title">Đăng ký thuê</div>
            
            <div id="bookingContent">
                <div class="selected-room-info" id="selectedInfo" style="display:none;">
                    <div style="font-weight:bold; margin-bottom:5px;" id="selectedTitle"></div>
                    <div style="color:#dc3545; font-weight:bold;" id="selectedPrice"></div>
                </div>

                <form id="bookingForm" onsubmit="submitBooking(event)">
                    <input type="hidden" id="listingId" value="">
                    
                    <div class="form-group">
                        <label class="form-label">Họ và tên</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ngày bắt đầu dự kiến</label>
                        <input type="date" id="startDate" class="form-control" required>
                    </div>

                    <button type="submit" class="btn-submit" id="btnSubmit" disabled>Gửi yêu cầu</button>
                    <p style="font-size:0.8rem; color:#666; margin-top:10px; text-align:center;">
                        Vui lòng chọn phòng bên trái để gửi yêu cầu.
                    </p>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        let favorites = [];
        let selectedId = null;

        document.addEventListener('DOMContentLoaded', loadFavorites);

        async function loadFavorites() {
            try {
                const res = await fetch('/api/favorites.php');
                const data = await res.json();
                
                const container = document.getElementById('favList');
                container.innerHTML = '';

                if (data.success && data.favorites && data.favorites.length > 0) {
                    favorites = data.favorites;
                    favorites.forEach((fav, index) => {
                         const item = document.createElement('div');
                         item.className = 'fav-item';
                         item.onclick = (e) => {
                             if (!e.target.closest('.btn-remove') && !e.target.closest('.btn-view')) {
                                 selectRoom(fav.id);
                             }
                         };
                         item.id = 'fav-' + fav.id;
                         
                         const img = fav.image_url || 'assets/default-room.jpg';
                         
                         item.innerHTML = `
                            <img src="${img}" class="fav-img">
                            <div class="fav-info">
                                <div class="fav-title">${fav.title}</div>
                                <div class="fav-price">${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(fav.price)}</div>
                                <div class="fav-address"><i class="fa-solid fa-location-dot"></i> ${fav.address}, ${fav.city}</div>
                            </div>
                            <div class="fav-actions">
                                <button class="btn-remove" onclick="removeFavorite(${fav.id})" title="Bỏ yêu thích">
                                    <i class="fa-solid fa-heart"></i>
                                </button>
                                <a href="/listing_detail.php?id=${fav.id}" class="btn-view">Chi tiết</a>
                            </div>
                         `;
                         container.appendChild(item);
                    });

                    // Auto select first
                    if (favorites.length > 0) {
                        selectRoom(favorites[0].id);
                    }
                } else {
                    container.innerHTML = '<div class="empty-state"><i class="fa-regular fa-heart" style="font-size:40px; margin-bottom:10px;"></i><br>Bạn chưa có phòng trọ yêu thích nào.</div>';
                    document.getElementById('bookingContent').style.opacity = '0.5';
                    document.getElementById('bookingContent').style.pointerEvents = 'none';
                }
            } catch (e) {
                console.error(e);
            }
        }

        function selectRoom(id) {
            selectedId = id;
            document.querySelectorAll('.fav-item').forEach(el => el.classList.remove('active'));
            const el = document.getElementById('fav-' + id);
            if (el) el.classList.add('active');

            const room = favorites.find(f => f.id == id);
            if (room) {
                document.getElementById('selectedInfo').style.display = 'block';
                document.getElementById('selectedTitle').innerText = room.title;
                document.getElementById('selectedPrice').innerText = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(room.price);
                
                document.getElementById('listingId').value = room.id;
                document.getElementById('btnSubmit').disabled = false;
                document.getElementById('btnSubmit').innerText = 'Gửi yêu cầu thuê';
            }
        }

        function removeFavorite(id) {
            if (!confirm('Bạn có chắc muốn bỏ phòng này khỏi danh sách yêu thích?')) return;
            
            fetch('/api/favorites.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({listing_id: id})
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadFavorites(); // Reload list
                } else {
                    alert('Lỗi: ' + data.message);
                }
            });
        }

        async function submitBooking(e) {
            e.preventDefault();
            if (!selectedId) return;

            const btn = document.getElementById('btnSubmit');
            const originalText = btn.innerText;
            btn.innerText = 'Đang gửi...';
            btn.disabled = true;

            const data = {
                listing_id: selectedId,
                start_date: document.getElementById('startDate').value
            };

            try {
                const res = await fetch('/api/bookings.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                
                if (result.success) {
                    alert('Gửi yêu cầu thành công! Chủ nhà sẽ liên hệ với bạn sớm.');
                    e.target.reset();
                    // Keep selection
                } else {
                    alert('Lỗi: ' + (result.error || 'Có lỗi xảy ra'));
                }
            } catch (err) {
                console.error(err);
                alert('Lỗi kết nối.');
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
