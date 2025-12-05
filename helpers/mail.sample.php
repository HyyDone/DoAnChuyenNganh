<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Đường dẫn tuyệt đối đến thư mục vendor, điều chỉnh nếu cần thiết dựa trên cấu trúc thư mục thực tế
require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';

function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Cấu hình Server
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Bật debug output nếu cần
        $mail->isSMTP();                                            // Gửi bằng SMTP
        $mail->CharSet    = 'UTF-8';                                // Thiết lập font chữ UTF-8
        $mail->Host       = 'smtp.gmail.com';                       // Set SMTP server
        $mail->SMTPAuth   = true;                                   // Bật SMTP authentication
        $mail->Username   = 'YOUR_EMAIL@gmail.com';                 // SMTP username
        $mail->Password   = 'YOUR_APP_PASSWORD';                    // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Bật mã hóa TLS
        $mail->Port       = 465;                                    // Cổng TCP

        // Người nhận
        $mail->setFrom('YOUR_EMAIL@gmail.com', 'Rental System');
        $mail->addAddress($to);     // Thêm người nhận

        // Nội dung
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Có thể log lỗi ra file hoặc return message
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
