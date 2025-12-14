CREATE DATABASE QuanLyNhaTro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; 
USE QuanLyNhaTro;

DROP TABLE IF EXISTS ai_conversations;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS listing_images;
DROP TABLE IF EXISTS listings;
DROP TABLE IF EXISTS users;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    phone VARCHAR(20),
    avatar VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    address VARCHAR(255),
    district VARCHAR(100),
    city VARCHAR(100) DEFAULT 'Ho Chi Minh',
    room_type ENUM('studio','share','private') DEFAULT 'private',
    status ENUM('available','booked','inactive') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS listing_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    is_cover TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE post_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT,
    image VARCHAR(255) NULL, 
    listing_id INT NULL,    
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    tenant_id INT NOT NULL,
    start_date DATE,
    end_date DATE,
    status ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
    deposit_amount DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(12,2),
    type ENUM('deposit','refund','payment') NOT NULL,
    status ENUM('pending','done','failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    target_type ENUM('user','post','listing') NOT NULL,
    target_id INT NOT NULL,
    reason VARCHAR(255),
    details TEXT,
    status ENUM('open','reviewed','resolved') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ai_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    input TEXT,
    response TEXT,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('request_booking', 'booking_confirmed', 'booking_rejected') NOT NULL,
    reference_id INT NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    listing_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (user_id, listing_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

CREATE TABLE damage_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    reporter_id INT NOT NULL, -- Tenant
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    cost DECIMAL(12,2),
    status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    content TEXT, -- HTML/Text content
    status ENUM('pending', 'signed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    signed_at TIMESTAMP NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL COMMENT 'Tiêu đề tin',
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'Slug SEO',
    image_url VARCHAR(255) DEFAULT NULL COMMENT 'Ảnh đại diện tin',
    category VARCHAR(100) NOT NULL COMMENT 'Thể loại tin tức',
    summary TEXT COMMENT 'Mô tả ngắn',
    content LONGTEXT NOT NULL COMMENT 'Nội dung chi tiết',
    views INT DEFAULT 0 COMMENT 'Lượt xem',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày đăng',
    INDEX idx_category (category),
    INDEX idx_views (views)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appliances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Người đăng',
    name VARCHAR(255) NOT NULL,
    city VARCHAR(50) DEFAULT 'Ho Chi Minh', -- [MỚI] Thành phố
    type ENUM('Tủ lạnh', 'Máy giặt', 'Quạt', 'Bếp Gas', 'Khác') NOT NULL,
    price DECIMAL(10, 2) NOT NULL COMMENT 'Giá thuê trên tháng',
    quantity INT DEFAULT 1,
    description TEXT,
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
    image_path VARCHAR(255) DEFAULT NULL,   -- [MỚI] Đường dẫn ảnh
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appliance_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appliance_id INT NOT NULL,
    renter_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    address VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appliance_id) REFERENCES appliances(id) ON DELETE CASCADE,
    FOREIGN KEY (renter_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO news (title, slug, image_url, category, summary, content, views, created_at) VALUES

-- ================== TIN THUÊ TRỌ ==================
(
'Giá thuê phòng trọ Hà Nội đầu năm 2025 có gì thay đổi?',
'gia-thue-phong-tro-ha-noi-dau-nam-2025',
NULL,
'Tin thuê trọ',
'Thị trường phòng trọ Hà Nội đầu năm 2025 ghi nhận nhiều biến động về giá, đặc biệt ở khu vực trung tâm.',
'Năm 2025, giá thuê phòng trọ tại Hà Nội có xu hướng tăng nhẹ từ 5–10% so với cuối năm trước. Nguyên nhân chủ yếu đến từ nhu cầu thuê nhà của sinh viên và người lao động tăng cao sau Tết. Các khu vực như Cầu Giấy, Đống Đa, Hai Bà Trưng ghi nhận mức giá trung bình từ 3–5 triệu/tháng cho phòng khép kín. Người thuê nên khảo sát nhiều nơi và ưu tiên hợp đồng dài hạn để có mức giá tốt.',
120,
'2025-01-05 09:00:00'
),
(
'Sinh viên nên thuê trọ khu vực nào để tiết kiệm chi phí?',
'sinh-vien-nen-thue-tro-khu-vuc-nao-tiet-kiem',
NULL,
'Tin thuê trọ',
'Việc chọn đúng khu vực thuê trọ giúp sinh viên tiết kiệm đáng kể chi phí sinh hoạt hàng tháng.',
'Đối với sinh viên, các khu vực gần trường nhưng không quá trung tâm như Hà Đông, Thanh Xuân, Thủ Đức (TP.HCM) là lựa chọn hợp lý. Giá thuê dao động từ 2–3 triệu/tháng, có thể ở ghép để giảm chi phí. Ngoài ra, sinh viên nên ưu tiên phòng có chủ nhà rõ ràng, điện nước tính theo giá nhà nước.',
95,
'2025-01-08 14:30:00'
),

-- ================== KINH NGHIỆM THUÊ NHÀ ==================
(
'5 kinh nghiệm thuê phòng trọ lần đầu không thể bỏ qua',
'5-kinh-nghiem-thue-phong-tro-lan-dau',
NULL,
'Kinh nghiệm thuê nhà',
'Thuê phòng trọ lần đầu cần lưu ý nhiều vấn đề để tránh rủi ro và phát sinh chi phí không đáng có.',
'Người thuê lần đầu nên kiểm tra kỹ hợp đồng, hệ thống điện nước, an ninh khu vực và hỏi rõ các chi phí phát sinh. Không nên đặt cọc khi chưa xem phòng trực tiếp. Ngoài ra, hãy chụp ảnh hiện trạng phòng khi nhận để tránh tranh chấp sau này.',
210,
'2025-01-10 10:15:00'
),
(
'Có nên thuê phòng trọ không hợp đồng hay không?',
'co-nen-thue-phong-tro-khong-hop-dong',
NULL,
'Kinh nghiệm thuê nhà',
'Nhiều người chọn thuê trọ không hợp đồng vì tiện lợi, nhưng tiềm ẩn nhiều rủi ro pháp lý.',
'Thuê phòng không hợp đồng giúp thủ tục nhanh gọn nhưng người thuê sẽ gặp bất lợi nếu xảy ra tranh chấp. Chủ nhà có thể tăng giá hoặc yêu cầu dọn đi bất ngờ. Do đó, dù là hợp đồng viết tay, người thuê vẫn nên có thỏa thuận rõ ràng về giá, thời gian và tiền cọc.',
160,
'2025-01-12 16:45:00'
),

-- ================== PHÁP LÝ NHÀ TRỌ ==================
(
'Thuê phòng trọ có cần đăng ký tạm trú không?',
'thue-phong-tro-co-can-dang-ky-tam-tru',
NULL,
'Pháp lý nhà trọ',
'Đăng ký tạm trú là nghĩa vụ pháp lý của người thuê trọ và chủ nhà theo quy định.',
'Theo quy định pháp luật, người thuê trọ phải đăng ký tạm trú tại công an địa phương. Chủ nhà có trách nhiệm hỗ trợ khai báo. Việc không đăng ký tạm trú có thể bị xử phạt hành chính. Đăng ký tạm trú giúp bảo vệ quyền lợi người thuê khi xảy ra sự cố.',
130,
'2025-01-14 08:20:00'
),
(
'Hợp đồng thuê nhà cần những điều khoản gì để đảm bảo quyền lợi?',
'hop-dong-thue-nha-can-nhung-dieu-khoan-gi',
NULL,
'Pháp lý nhà trọ',
'Hợp đồng thuê nhà rõ ràng giúp hạn chế tranh chấp giữa người thuê và chủ trọ.',
'Một hợp đồng thuê nhà cần có các điều khoản về thông tin các bên, giá thuê, tiền cọc, thời hạn thuê, trách nhiệm sửa chữa và điều kiện chấm dứt hợp đồng. Người thuê nên đọc kỹ trước khi ký và giữ lại một bản hợp đồng.',
175,
'2025-01-16 11:00:00'
),

-- ================== CẢNH BÁO LỪA ĐẢO ==================
(
'Cảnh báo chiêu trò lừa đặt cọc phòng trọ online',
'canh-bao-lua-dat-coc-phong-tro-online',
NULL,
'Cảnh báo lừa đảo',
'Nhiều đối tượng lợi dụng nhu cầu thuê trọ để lừa đảo tiền cọc qua mạng.',
'Các đối tượng thường đăng phòng giá rẻ bất thường, yêu cầu chuyển cọc trước để giữ phòng. Sau khi nhận tiền, họ chặn liên lạc. Người thuê chỉ nên đặt cọc khi đã xem phòng trực tiếp và ký thỏa thuận rõ ràng.',
320,
'2025-01-18 09:40:00'
),
(
'Nhận biết dấu hiệu phòng trọ lừa đảo sinh viên',
'dau-hieu-phong-tro-lua-dao-sinh-vien',
NULL,
'Cảnh báo lừa đảo',
'Sinh viên là đối tượng dễ bị lừa khi tìm phòng trọ đầu năm học.',
'Dấu hiệu lừa đảo gồm: yêu cầu chuyển tiền giữ chỗ, không cho xem phòng, thông tin mập mờ về địa chỉ. Sinh viên nên đi xem phòng cùng bạn bè và ưu tiên thuê qua kênh uy tín.',
280,
'2025-01-20 15:10:00'
),

-- ================== TIN THỊ TRƯỜNG ==================
(
'Xu hướng thuê phòng trọ năm 2025: Phòng nhỏ, tiện nghi',
'xu-huong-thue-phong-tro-nam-2025',
NULL,
'Tin thị trường',
'Năm 2025, người thuê ưu tiên phòng trọ nhỏ gọn nhưng đầy đủ tiện nghi.',
'Thị trường cho thấy nhu cầu phòng khép kín, có máy lạnh, gác lửng tăng mạnh. Người thuê sẵn sàng trả giá cao hơn để có không gian riêng tư và an ninh tốt. Chủ trọ cần nâng cấp cơ sở vật chất để thu hút khách.',
190,
'2025-01-22 10:00:00'
),
(
'Thị trường phòng trọ TP.HCM sau Tết có gì đáng chú ý?',
'thi-truong-phong-tro-tphcm-sau-tet',
NULL,
'Tin thị trường',
'Sau Tết Nguyên Đán, nhu cầu thuê phòng trọ tại TP.HCM tăng mạnh.',
'Lao động quay lại thành phố khiến lượng tìm phòng tăng cao ở các quận Bình Thạnh, Tân Bình, Thủ Đức. Giá thuê ổn định nhưng phòng đẹp, giá tốt thường hết nhanh. Người thuê nên tìm sớm để có nhiều lựa chọn.',
205,
'2025-01-24 13:25:00'
);



