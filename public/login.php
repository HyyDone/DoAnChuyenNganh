<?php
require_once __DIR__ . '/../config/db.php';
session_start();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$loginInput || !$password) $errors[] = "Vui lòng nhập tài khoản và mật khẩu.";
    if (empty($errors)) {
        // Allow login by Username, Email, or Phone
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ? OR phone = ? LIMIT 1");
        $stmt->execute([$loginInput, $loginInput, $loginInput]);
        $user = $stmt->fetch();
        
        // Kiểm tra mật khẩu bằng SHA256
        if ($user && hash('sha256', $password) === $user['password'] && $user['is_active']) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            // $_SESSION['role'] = $user['role']; // Role không còn trong DB
            header('Location: /index.php');
            exit;
        } else {
            $errors[] = "Email hoặc mật khẩu không đúng, hoặc tài khoản bị khoá.";
        }
    }
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Đăng nhập - Cộng đồng Thuê Trọ</title>
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
            display: flex;
            max-width: 980px;
            width: 100%;
            padding: 20px;
            justify-content: space-between;
            align-items: center;
        }

        .left-col {
            width: 50%;
            padding-right: 32px;
        }

        .left-col h1 {
            color: #1877f2;
            font-size: 4rem;
            font-weight: bold;
            margin: 0 0 10px;
            letter-spacing: -2px;
        }

        .left-col p {
            font-size: 1.7rem;
            line-height: 1.4;
            color: #1c1e21;
            margin: 0;
        }

        .right-col {
            width: 40%;
            max-width: 400px;
        }

        .login-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 8px 16px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .login-card input {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 12px;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            font-size: 17px;
            box-sizing: border-box;
        }

        .login-card input:focus {
            border-color: #1877f2;
            outline: none;
            box-shadow: 0 0 0 2px #e7f3ff;
        }

        .login-btn {
            width: 100%;
            background-color: #1877f2;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 20px;
            font-weight: bold;
            padding: 10px;
            cursor: pointer;
            margin-top: 6px;
            transition: background-color 0.2s;
        }

        .login-btn:hover {
            background-color: #166fe5;
        }

        .forgot-link {
            display: block;
            margin-top: 16px;
            color: #1877f2;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .divider {
            border-bottom: 1px solid #dadde1;
            margin: 20px 0;
        }

        .create-btn {
            background-color: #42b72a;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 17px;
            font-weight: bold;
            padding: 0 16px;
            height: 48px;
            line-height: 48px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }

        .create-btn:hover {
            background-color: #36a420;
        }

        .error-msg {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
            text-align: left;
        }

        @media (max-width: 900px) {
            .container {
                flex-direction: column;
                text-align: center;
            }

            .left-col {
                width: 100%;
                padding: 0;
                margin-bottom: 40px;
            }

            .left-col p {
                font-size: 1.2rem;
            }

            .right-col {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="left-col">
            <h1>Thuê Trọ Online</h1>
            <p>Thuê Trọ Online Ở trọ đúng giá – Sống đúng chất.</p>
        </div>
        <div class="right-col">
            <div class="login-card">
                <?php if (!empty($errors)): ?>
                    <div class="error-msg">
                        <?php foreach ($errors as $e) echo "<div>" . htmlspecialchars($e) . "</div>"; ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="/login.php">
                    <input name="email" type="text" placeholder="Email, Username hoặc Số điện thoại" required>
                    <input name="password" type="password" placeholder="Mật khẩu" required>
                    <button type="submit" class="login-btn">Đăng nhập</button>
                </form>
                <a href="#" class="forgot-link">Quên mật khẩu?</a>
                <div class="divider"></div>
                <a href="/register.php" class="create-btn">Tạo tài khoản mới</a>
            </div>
            <p style="margin-top: 28px; font-size: 14px;"><a href="#" style="font-weight: bold; color: #1c1e21; text-decoration: none;">Tạo Trang</a> dành cho người nổi tiếng, thương hiệu hoặc doanh nghiệp.</p>
        </div>
    </div>
</body>

</html>