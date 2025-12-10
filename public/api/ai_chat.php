<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/upload.php'; // Re-using existing upload helper

session_start();
// Suppress deprecation warnings for PHP 8.5+ regarding http_response_header
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ob_start(); // Buffer output to catch any spurious warnings

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;

// 1. Handle File Uploads (if any)
$uploadedImagePath = null;
if (!empty($_FILES['image'])) {
    // Determine target directory based on predicted intent (listing vs post)
    // For now, we'll store in a generic 'uploads/ai' or reuse existing structure.
    // Let's use 'uploads/posts' as a safe default, or 'uploads/listings'. 
    // Since we don't know the intent yet, let's put it in 'uploads/temp' or just 'uploads/posts' for now.
    $targetDir = __DIR__ . '/../../public/uploads/ai_generated/';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    
    $uploadResult = upload_avatar($_FILES['image'], $targetDir); // using upload_avatar as a generic uploader
    if (isset($uploadResult['path'])) {
        // Fix path to be relative to public
        $uploadedImagePath = str_replace('uploads/avatars/', 'uploads/ai_generated/', $uploadResult['path']);
    }
}

// 2. Parsed Input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle both JSON (standard chat) and FormData (file upload + text)
    $input = [];
    $message = '';
    $history = [];

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? '');
        $history = $input['history'] ?? [];
    } else {
        $message = trim($_POST['message'] ?? '');
        $history = isset($_POST['history']) ? json_decode($_POST['history'], true) : [];
    }

    if (empty($message) && empty($uploadedImagePath)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tin nhắn hoặc gửi ảnh.']);
        exit;
    }

    // 3. RAG: Fetch Listings Context
    $listingsContext = getListingsContext($pdo);

    // [OPTIMIZATION] Pre-check for standard Greeting
    $lowerMsg = strtolower($message);
    if (in_array($lowerMsg, ['xin chào', 'hi', 'hello', 'chào', 'chao'])) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'response' => 'Xin chào, bạn muốn tôi giúp gì?',
            'action_performed' => null
        ]);
        exit;
    }

    // 4. Construct System Prompt
    $systemPrompt = constructSystemPrompt($listingsContext, $uploadedImagePath);

    // 5. Call Ollama
    $aiResponse = callOllama($message, $history, $systemPrompt);

    // 6. Intent Parsing & Execution
    $actionResult = handleAiActions($pdo, $userId, $aiResponse, $uploadedImagePath);
    
    // If AI performed an action (like posting), it returns a specific message.
    // We append that to the final response.
    
    // Clean buffer before outputting JSON to ensure no warnings/text are prepended
    ob_clean();
    echo json_encode([
        'success' => true,
        'response' => $actionResult['response'],
        'action_performed' => $actionResult['type'] ?? null // e.g., 'post_created', 'listing_created'
    ]);
    exit;
}

// --- Helper Functions ---

function getListingsContext($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, title, price, address, city, room_type FROM listings WHERE status = 'available' ORDER BY created_at DESC LIMIT 10");
        $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $text = "Dữ liệu phòng trọ hiện có (ID - Tiêu đề - Giá - Địa chỉ):\n";
        foreach ($listings as $l) {
            $price = number_format($l['price']) . " VND";
            $text .= "- ID {$l['id']}: {$l['title']} - Giá {$price} - {$l['address']}, {$l['city']} ({$l['room_type']})\n";
            $text .= "  Url chi tiết: /listing_detail.php?id={$l['id']}\n";
        }
        return $text;
    } catch (Exception $e) {
        return "Không thể lấy dữ liệu phòng trọ.";
    }
}

function constructSystemPrompt($listingsData, $imagePath) {
    $base = "Bạn là trợ lý ảo AI cho trang web Thuê Trọ. Nhiệm vụ của bạn là tư vấn tìm phòng, giúp đăng bài viết, và đăng tin phòng trọ.\n";
    $base .= "Dưới đây là danh sách phòng trọ hiện có trong hệ thống:\n{$listingsData}\n\n";
    
    $base .= "QUY TẮC QUAN TRỌNG (JSON ACTION MODE):\n";
    $base .= "Bạn phải phân tích ý định của người dùng và chọn hành động phù hợp:\n\n";

    $base .= "1. GREETING (Chào hỏi):\n";
    $base .= "   - Nếu người dùng chào (ví dụ: 'xin chào', 'hi', 'hello'), hãy trả lời chính xác câu này: \"Xin chào, bạn muốn tôi giúp gì?\"\n";
    $base .= "   - Trả lời bằng text thông thường, KHÔNG dùng JSON.\n\n";

    $base .= "2. CONSULTATION (Tư vấn/Tìm phòng):\n";
    $base .= "   - Nếu người dùng hỏi về phòng trọ (ví dụ: 'tư vấn phòng', 'tìm phòng ở HCM', 'có phòng nào giá rẻ không?').\n";
    $base .= "   - Chỉ trả lời bằng text, giới thiệu các phòng từ danh sách trên kèm đường dẫn chi tiết.\n";
    $base .= "   - TUYỆT ĐỐI KHÔNG tự ý tạo tin đăng (create_listing) trong trường hợp này.\n\n";

    $base .= "3. SOCIAL POST (Đăng bài lên trang chủ):\n";
    $base .= "   - Chỉ khi người dùng yêu cầu rõ ràng việc đăng bài lên 'trang chủ', 'tường', 'bảng tin' hoặc 'post bài' (ví dụ: 'đăng bài chào mọi người', 'post status này').\n";
    $base .= "   - Trả lời bằng JSON: {\"action\": \"create_post\", \"caption\": \"Nội dung bài viết...\"}\n\n";

    $base .= "4. RENTAL POST (Đăng tin cho thuê/Trang thuê trọ):\n";
    $base .= "   - Chỉ khi người dùng yêu cầu đăng tin 'cho thuê', 'đăng phòng', 'đăng lên trang thuê trọ' (ví dụ: 'tôi muốn cho thuê phòng này', 'đăng tin phòng giá 3tr').\n";
    $base .= "   - Trả lời bằng JSON: {\"action\": \"create_listing\", \"title\": \"...\", \"price\": 123456, \"address\": \"...\", \"city\": \"...\", \"room_type\": \"...\" (private/share/studio), \"description\": \"...\"}\n\n";

    $base .= "LƯU Ý: Nếu không rõ ý định, hãy hỏi lại người dùng thay vì tự động thực hiện hành động.";
    
    if ($imagePath) {
        $base .= "\nNgười dùng ĐÃ GỬI kèm một hình ảnh tại đường dẫn: {$imagePath}. Nếu họ muốn đăng bài hoặc đăng tin, hãy dùng đường dẫn này.\n";
    }

    return $base;
}

function callOllama($message, $history, $systemPrompt) {
    $messages = [];
    $messages[] = ['role' => 'system', 'content' => $systemPrompt];
    
    // Add recent history
    $history = array_slice($history, -4);
    foreach ($history as $msg) {
        $role = $msg['sender'] === 'user' ? 'user' : 'assistant';
        $messages[] = ['role' => $role, 'content' => $msg['text']];
    }
    
    $messages[] = ['role' => 'user', 'content' => $message];

    $payload = [
        'model' => 'qwen2.5:3b',
        'messages' => $messages,
        'stream' => false,
        'format' => 'json', 
        'options' => ['temperature' => 0.5]
    ];

    $response = sendHttpRequest('http://127.0.0.1:11434/api/chat', 'POST', $payload);
    
    if (!$response['success']) {
        error_log("Ollama Error: " . $response['error']);
        return "Lỗi kết nối AI: " . $response['error'];
    }
    
    $data = json_decode($response['data'], true);
    return $data['message']['content'] ?? 'Xin lỗi, tôi không hiểu phản hồi của AI.';
}

function sendHttpRequest($url, $method, $data = []) {
    $jsonPayload = json_encode($data);
    $contentLength = strlen($jsonPayload);
    
    // Create params for file_get_contents (universal fallback, NO CURL needed)
    $opts = [
        'http' => [
            'method'  => $method,
            'header'  => "Content-Type: application/json\r\n" .
                         "Accept: application/json\r\n" .
                         "Content-Length: $contentLength\r\n",
            'content' => $jsonPayload,
            'timeout' => 60, // Increased timeout for AI generation
            'ignore_errors' => true // Capture error response body
        ]
    ];
    
    $context  = stream_context_create($opts);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        $error = error_get_last();
        return ['success' => false, 'error' => $error['message'] ?? 'Connection faied'];
    }
    
    // Check headers for status code - PHP 8.5+ compatible
    $statusLine = '';
    if (function_exists('http_get_last_response_headers')) {
        $headers = http_get_last_response_headers();
        if ($headers && isset($headers[0])) {
            $statusLine = $headers[0];
        }
    } elseif (isset($http_response_header)) {
        $statusLine = $http_response_header[0];
    }

    if ($statusLine) {
        preg_match('{HTTP\/\S*\s(\d{3})}', $statusLine, $match);
        $status = $match[1] ?? 500;
        
        if ($status >= 400) {
            return ['success' => false, 'error' => "HTTP $status: " . $result];
        }
    }
    
    return ['success' => true, 'data' => $result];
}

function handleAiActions($pdo, $userId, $aiResponseText, $imagePath) {
    // Try to parse JSON from response
    $jsonStart = strpos($aiResponseText, '{');
    $jsonEnd = strrpos($aiResponseText, '}');
    
    if ($jsonStart !== false && $jsonEnd !== false) {
        $jsonStr = substr($aiResponseText, $jsonStart, $jsonEnd - $jsonStart + 1);
        $actionData = json_decode($jsonStr, true);

        if ($actionData && isset($actionData['action'])) {
            if ($actionData['action'] === 'create_post') {
                return executeCreatePost($pdo, $userId, $actionData, $imagePath);
            } elseif ($actionData['action'] === 'create_listing') {
                return executeCreateListing($pdo, $userId, $actionData, $imagePath);
            }
        }
    }

    // Default: Chat response (clean up any JSON artifacts if mixed)
    return ['response' => $aiResponseText];
}

function executeCreatePost($pdo, $userId, $data, $imagePath) {
    if (!$userId) return ['response' => 'Vui lòng đăng nhập để đăng bài.'];
    
    $caption = $data['caption'] ?? 'Bài viết mới';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $caption, $imagePath]);
        return [
            'response' => "Tôi đã đăng bài viết mới của bạn với nội dung: \"{$caption}\"",
            'type' => 'post_created'
        ];
    } catch (Exception $e) {
        return ['response' => 'Lỗi khi đăng bài: ' . $e->getMessage()];
    }
}

function executeCreateListing($pdo, $userId, $data, $imagePath) {
    if (!$userId) return ['response' => 'Vui lòng đăng nhập để đăng tin.'];

    $title = $data['title'] ?? 'Phòng trọ mới';
    $price = intval(str_replace(['.', ','], '', $data['price'] ?? 0));
    $addr = $data['address'] ?? 'Chưa cập nhật';
    $city = $data['city'] ?? 'Ho Chi Minh';
    $rawType = strtolower($data['room_type'] ?? '');
    $type = 'private'; // Default
    if (strpos($rawType, 'studio') !== false) {
        $type = 'studio';
    } elseif (strpos($rawType, 'share') !== false || strpos($rawType, 'ghép') !== false || strpos($rawType, 'chung') !== false) {
        $type = 'share';
    } elseif (strpos($rawType, 'private') !== false || strpos($rawType, 'riêng') !== false) {
        $type = 'private';
    }
    $desc = $data['description'] ?? '';

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO listings (owner_id, title, price, address, city, room_type, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'available')");
        $stmt->execute([$userId, $title, $price, $addr, $city, $type, $desc]);
        $listingId = $pdo->lastInsertId();

        if ($imagePath) {
            $stmtImg = $pdo->prepare("INSERT INTO listing_images (listing_id, file_path, is_cover) VALUES (?, ?, 1)");
            $stmtImg->execute([$listingId, $imagePath]);
        }
        $pdo->commit();
        
        return [
            'response' => "Tôi đã tạo tin đăng phòng trọ thành công! (ID: {$listingId})",
            'type' => 'listing_created'
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['response' => 'Lỗi khi tạo tin đăng: ' . $e->getMessage()];
    }
}
