<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth.php';
require_role('owner');
$uid = current_user_id();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $district = trim($_POST['district'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if (!$title || !$price) $errors[] = 'Thiếu thông tin bắt buộc.';
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO listings (owner_id,title,description,price,district) VALUES (?,?,?,?,?)");
        $stmt->execute([$uid, $title, $desc, $price, $district]);
        $lid = $pdo->lastInsertId();
        // handle images
        if (!empty($_FILES['images']['tmp_name'][0])) { // sửa ở đây
            $dest = __DIR__ . '/../public/uploads/rooms/';
            if (!is_dir($dest)) mkdir($dest, 0755, true);
            foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK && is_uploaded_file($tmp)) { // sửa ở đây
                    $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $filename = uniqid() . "." . $ext;
                    move_uploaded_file($tmp, $dest . $filename);
                    $stmtImg = $pdo->prepare("INSERT INTO listing_images (listing_id,file_path) VALUES (?,?)");
                    $stmtImg->execute([$lid, 'uploads/rooms/' . $filename]);
                }
            }
        }
        header('Location: /owners/my_rooms.php');
        exit;
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Add Room</title>
</head>

<body>
    <h2>Add Room</h2>
    <?php
    // sửa cú pháp hiển thị lỗi
    if (!empty($errors)) {
        foreach ($errors as $e) {
            echo "<div style='color:red'>" . htmlspecialchars($e) . "</div>";
        }
    }
    ?>
    <form method="post" enctype="multipart/form-data">
        <label>Title: <input name="title" required></label><br>
        <label>Price: <input name="price" type="number" step="1000" required></label><br>
        <label>District: <input name="district"></label><br>
        <label>Description:<br><textarea name="description"></textarea></label><br>
        <label>Images: <input type="file" name="images[]" multiple accept="image/*"></label><br>
        <button type="submit">Create</button>
    </form>
</body>

</html>