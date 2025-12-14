<?php
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $sql = "SELECT l.*, u.full_name as owner_name, u.avatar as owner_avatar, 
                (SELECT file_path FROM listing_images WHERE listing_id = l.id LIMIT 1) as image_path
                FROM listings l 
                JOIN users u ON l.owner_id = u.id 
                WHERE l.status = 'available'";
        
        $params = [];

        if (!empty($_GET['price_min'])) {
            $sql .= " AND l.price >= ?";
            $params[] = $_GET['price_min'];
        }
        if (!empty($_GET['price_max'])) {
            $sql .= " AND l.price <= ?";
            $params[] = $_GET['price_max'];
        }
        if (!empty($_GET['city'])) {
            $sql .= " AND l.city LIKE ?";
            $params[] = '%' . $_GET['city'] . '%';
        }
        // Note: 'capacity' is not in the schema provided earlier, assuming 'room_type' or description for now. 
        // If user meant specific capacity column, we might need to add it. 
        // For now, let's filter by room_type if provided, or just ignore capacity if not in schema.
        // Looking at schema: room_type ENUM('studio','share','private')
        if (!empty($_GET['room_type'])) {
             $sql .= " AND l.room_type = ?";
             $params[] = $_GET['room_type'];
        }

        $sql .= " ORDER BY l.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $listings]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    try {
        $owner_id = $_SESSION['user_id'];
        $title = $_POST['title'] ?? 'Phòng trọ mới'; // Default title if not provided
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? 'Ho Chi Minh';
        $district = $_POST['district'] ?? '';
        $room_type = $_POST['room_type'] ?? 'private';

        // Basic validation
        if (empty($price) || empty($address)) {
            throw new Exception("Giá và địa chỉ là bắt buộc.");
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO listings (owner_id, title, description, price, address, city, district, room_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$owner_id, $title, $description, $price, $address, $city, $district, $room_type]);
        $listing_id = $pdo->lastInsertId();

        // Handle Image Upload (Multiple)
        if (!empty($_FILES['images']['name'][0])) {
            $target_dir = __DIR__ . "/../../public/uploads/listings/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $stmt_img = $pdo->prepare("INSERT INTO listing_images (listing_id, file_path, is_cover) VALUES (?, ?, ?)");
            
            $fileCount = count($_FILES['images']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '_' . $i . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    $db_path = "uploads/listings/" . $new_filename; // relative path for DB

                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $target_file)) {
                        $is_cover = ($i === 0) ? 1 : 0; // First image is cover
                        $stmt_img->execute([$listing_id, $db_path, $is_cover]);
                    }
                }
            }
        }
        // Fallback for single 'image' (legacy)
        elseif (!empty($_FILES['image']['name'])) {
            $target_dir = __DIR__ . "/../../public/uploads/listings/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            $db_path = "uploads/listings/" . $new_filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $stmt_img = $pdo->prepare("INSERT INTO listing_images (listing_id, file_path, is_cover) VALUES (?, ?, 1)");
                $stmt_img->execute([$listing_id, $db_path]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Đăng tin thành công!', 'listing_id' => $listing_id]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
