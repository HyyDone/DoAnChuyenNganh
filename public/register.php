<?php
require_once __DIR__ . '/../config/db.php';
session_start();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (!$username || !$email || !$password) $errors[] = "Vui lòng điền đầy đủ thông tin.";
    if ($password !== $confirm) $errors[] = "Mật khẩu xác nhận không khớp.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ.";

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) $errors[] = "Email hoặc username đã tồn tại.";

    if (empty($errors)) {
        
        $hash = hash('sha256', $password);
        
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);
        
        $userId = $pdo->lastInsertId();
        $_SESSION['user_id'] = $userId;
        
        
        header('Location: /index.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Đăng ký - Cộng đồng Thuê Trọ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 432px;
            padding: 20px;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo h1 {
            color: #1877f2;
            font-size: 3.5rem;
            font-weight: bold;
            margin: 0;
            letter-spacing: -2px;
        }

        .register-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 8px 16px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 16px;
            border-bottom: 1px solid #dadde1;
            text-align: center;
        }

        .card-header h2 {
            margin: 0;
            font-size: 25px;
            color: #1c1e21;
        }

        .card-header p {
            margin: 5px 0 0;
            color: #606770;
            font-size: 15px;
        }

        .card-body {
            padding: 16px;
        }

        .form-group {
            margin-bottom: 12px;
        }

        input, select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccd0d5;
            border-radius: 5px;
            font-size: 15px;
            background-color: #f5f6f7;
            box-sizing: border-box;
        }

        input:focus, select:focus {
            border-color: #1877f2;
            outline: none;
            background-color: white;
        }

        .register-btn {
            width: 100%;
            background-color: #00a400;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: bold;
            padding: 10px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.2s;
        }

        .register-btn:hover {
            background-color: #008a00; /* Darker green */
        }

        .login-link {
            text-align: center;
            margin-top: 16px;
            font-size: 14px;
        }

        .login-link a {
            color: #1877f2;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .error-msg {
            color: red;
            font-size: 13px;
            margin-bottom: 10px;
            background: #ffebe8;
            border: 1px solid #dd3c10;
            padding: 8px;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo">
            <h1>Thuê Trọ Online</h1>
        </div>
        <div class="register-card">
            <div class="card-header">
                <h2>Tạo tài khoản mới</h2>
                <p>Nhanh chóng và dễ dàng.</p>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="error-msg">
                        <?php foreach ($errors as $e) echo "<div>" . htmlspecialchars($e) . "</div>"; ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="/register.php">
                    <div class="form-group">
                        <input name="username" placeholder="Tên người dùng" required>
                    </div>
                    <div class="form-group">
                        <input name="email" type="email" placeholder="Số di động hoặc email" required>
                    </div>
                    <div class="form-group">
                        <input name="password" type="password" placeholder="Mật khẩu mới" required>
                    </div>
                    <div class="form-group">
                        <input name="password_confirm" type="password" placeholder="Nhập lại mật khẩu" required>
                    </div>
                    <!-- Role mặc định là tenant, đã xử lý ở backend -->
                    <p style="font-size: 11px; color: #777; margin-bottom: 10px;">
                        Bằng cách nhấp vào Đăng ký, bạn đồng ý với Điều khoản, Chính sách dữ liệu và Chính sách cookie của chúng tôi.
                    </p>
                    <button type="submit" class="register-btn">Đăng ký</button>
                </form>
                <div class="login-link">
                    <a href="/login.php">Bạn đã có tài khoản ư?</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>