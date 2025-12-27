<?php
require_once __DIR__ . '/../../config/db.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

if ($action === 'send_otp') {
    
    $stmtUser = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $userRow = $stmtUser->fetch();
    $email = $userRow['email'] ?? null;

    
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy email người dùng']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
        $stmt->execute([$otp, $expiry, $userId]);

        
        require_once __DIR__ . '/../../helpers/mail.php';
        $subject = "Mã xác thực chủ trọ - Thuê Trọ Online";
        $body = "
            <h3>Mã xác thực của bạn</h3>
            <p>Chào bạn,</p>
            <p>Mã OTP để kích hoạt vai trò chủ trọ của bạn là: <b style='font-size:20px; color:#1877f2;'>$otp</b></p>
            <p>Mã này sẽ hết hạn sau 10 phút.</p>
            <p>Thân mến,<br>Đội ngũ Thuê Trọ Online</p>
        ";

        if (sendMail($email, $subject, $body)) {
             echo json_encode(['success' => true, 'message' => 'Mã OTP đã được gửi đến email ' . $email]);
        } else {
             
             error_log("Failed to send OTP email to $email");
             echo json_encode(['success' => false, 'message' => 'Không thể gửi email. Vui lòng thử lại sau.']);
        }

        
        $logFile = __DIR__ . '/../../public/otp_log.txt';
        $logEntry = "[" . date('Y-m-d H:i:s') . "] OTP for User ID $userId ($email): $otp\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

} elseif ($action === 'verify_otp') {
    $input = json_decode(file_get_contents('php://input'), true);
    $otp = $input['otp'] ?? '';

    if (empty($otp)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã OTP']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT otp_code, otp_expires_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && $user['otp_code'] === $otp) {
            if (new DateTime() <= new DateTime($user['otp_expires_at'])) {
                
                $update = $pdo->prepare("UPDATE users SET is_landlord = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
                $update->execute([$userId]);
                
                
                
                
                echo json_encode(['success' => true, 'message' => 'Xác thực thành công! Bạn đã là chủ trọ.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Mã OTP đã hết hạn']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Mã OTP không chính xác']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
