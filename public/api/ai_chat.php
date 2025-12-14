<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/upload.php';
require_once __DIR__ . '/../../helpers/CityNormalizer.php';

session_start();
// Suppress deprecation warnings for PHP 8.5+ regarding http_response_header
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ob_start(); // Buffer output to catch any spurious warnings

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;

// 1. Handle File Uploads (if any)
// 1. Handle File Uploads (Multiple)
$uploadedImagePaths = [];

// Handle single 'image' (legacy support)
if (!empty($_FILES['image']) && !empty($_FILES['image']['name'])) {
    $targetDir = __DIR__ . '/../../public/uploads/ai_generated/';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    $uploadResult = upload_avatar($_FILES['image'], $targetDir);
    if (isset($uploadResult['path'])) {
        $uploadedImagePaths[] = str_replace('uploads/avatars/', 'uploads/ai_generated/', $uploadResult['path']);
    }
}

// Handle multiple 'images[]'
if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $targetDir = __DIR__ . '/../../public/uploads/ai_generated/';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    
    $count = count($_FILES['images']['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
             $tmpFile = [
                'name' => $_FILES['images']['name'][$i],
                'type' => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error' => $_FILES['images']['error'][$i],
                'size' => $_FILES['images']['size'][$i]
            ];
            $uploadResult = upload_avatar($tmpFile, $targetDir);
            if (isset($uploadResult['path'])) {
                 $uploadedImagePaths[] = str_replace('uploads/avatars/', 'uploads/ai_generated/', $uploadResult['path']);
            }
        }
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

    if (empty($message) && empty($uploadedImagePaths)) {
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
    $systemPrompt = constructSystemPrompt($listingsContext, $uploadedImagePaths);

    // 5. Call Ollama
    $aiResponse = callOllama($message, $history, $systemPrompt);

    // 6. Intent Parsing & Execution
    $actionResult = handleAiActions($pdo, $userId, $aiResponse, $uploadedImagePaths, $message);
    
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

function constructSystemPrompt($listingsData, $imagePaths) {
    $base = "Bạn là trợ lý ảo AI cho trang web Thuê Trọ. Nhiệm vụ của bạn là tư vấn tìm phòng, giúp đăng bài viết, và đăng tin phòng trọ.\n";
    $base .= "Dưới đây là danh sách phòng trọ hiện có trong hệ thống:\n{$listingsData}\n\n";
    
    $base .= "QUY TẮC QUAN TRỌNG (JSON ACTION MODE):\n";
    $base .= "Bạn phải phân tích ý định của người dùng và chọn hành động phù hợp:\n\n";

    $base .= "QUY TẮC ƯU TIÊN:\n";
    $base .= " - GREETING luôn ưu tiên cao nhất.\n";
    $base .= " - SOCIAL POST và RENTAL POST chỉ kích hoạt khi có từ khóa RÕ RÀNG.\n";
    $base .= " - Nếu câu nói chỉ mang tính kể chuyện hoặc hỏi thông tin → KHÔNG tạo JSON.\n";
    $base .= " - Không được tự suy diễn hành động.\n\n";

    $base .= "CHỐNG BỊA ĐẶT:\n";
    $base .= " - Không được tự tạo phòng trọ, bài viết hoặc ID không tồn tại.\n";
    $base .= " - Chỉ trả lời dựa trên dữ liệu được cung cấp từ hệ thống.\n";
    $base .= " - Nếu không có dữ liệu phù hợp, hãy nói rõ: 'Hiện tôi chưa tìm thấy thông tin phù hợp'.\n\n";

    $base .= "NGÔN NGỮ & GIỌNG ĐIỆU:\n";
    $base .= " - Luôn trả lời bằng tiếng Việt.\n";
    $base .= " - Giọng điệu thân thiện, hỗ trợ, giống nhân viên tư vấn thuê trọ.\n";
    $base .= " - Không dùng từ ngữ học thuật hoặc máy móc.\n\n";

    $base .= "1. GREETING (Chào hỏi):\n";
    $base .= "   - Nếu người dùng chào (ví dụ: 'xin chào', 'hi', 'hello'), hãy trả lời chính xác câu này: \"Xin chào, bạn muốn tôi giúp gì?\"\n";
    $base .= "   - Trả lời bằng text thông thường, KHÔNG dùng JSON.\n\n";

    $base .= "2. CONSULTATION (Tư vấn/Tìm phòng - KHÁCH THUÊ):\n";
    $base .= "   - Khi người dùng muốn TÌM KIẾM, HỎI THUÊ, hoặc TƯ VẤN phòng (ví dụ: 'tư vấn', 'tìm phòng', 'tìm trọ', 'tìm phòng trọ', 'mình muốn thuê', 'có phòng nào ở...').\n";
    $base .= "   - TRẢ LỜI BẰNG TEXT TỰ NHIÊN. TUYỆT ĐỐI KHÔNG tạo tin đăng.\n";
    $base .= "   - Cung cấp thông tin phòng và đường dẫn: [Tên phòng](listing_detail.php?id=ID).\n\n";
    $base .= "   - CHỈ được nói 'tôi tìm thấy phòng' khi có ÍT NHẤT 1 phòng với ID, tên và giá cụ thể.\n";
    $base .= "   - Mỗi phòng phải có link dạng: listing_detail.php?id=ID.\n";
    $base .= "   - ID phải là số nguyên tồn tại trong danh sách được cung cấp.\n";
    $base .= "   - TUYỆT ĐỐI KHÔNG dùng các cụm mơ hồ như: 'Phòng trọ', 'Giá thỏa thuận', 'Thông tin phù hợp'.\n";
    $base .= "   - Nếu KHÔNG có phòng phù hợp, PHẢI trả lời đúng câu sau và KHÔNG thêm nội dung khác:\n";
    $base .= "     \"Hiện tôi chưa tìm thấy phòng phù hợp với yêu cầu của bạn. Bạn có thể cho tôi biết thêm khu vực hoặc mức giá mong muốn không?\"\n\n";
    $base .= "   - CONSULTATION (tìm phòng) LUÔN trả lời bằng TEXT, KHÔNG BAO GIỜ trả JSON.\n";

    $base .= "3. SOCIAL POST (Đăng bài - CHỦ TRỌ/CỘNG ĐỒNG):\n";
    $base .= "   - Chỉ khi người dùng yêu cầu rõ ràng: 'đăng tin giúp mình', 'đăng bài', 'viết status', 'post lên tường'.\n";
    $base .= "   - JSON: {\"action\": \"create_post\", ...}\n\n";

    $base .= "4. RENTAL POST (Đăng tin cho thuê - CHỦ TRỌ):\n";
    $base .= "   - Chỉ khi người dùng nói rõ là muốn CHO THUÊ hoặc ĐĂNG TIN CỦA HỌ (ví dụ: 'tôi có phòng cho thuê', 'đăng giúp mình phòng này', 'đăng phòng trọ', 'cho thuê phòng giá 3tr').\n";
    $base .= "   - LƯU Ý: Nếu người dùng chỉ nói 'tìm trọ' hoặc 'muốn thuê', ĐÓ LÀ KHÁCH THUÊ -> Về mục 2.\n";
    $base .= "   - JSON: {\"action\": \"create_listing\", ...}\n\n";
    
    if (empty($imagePaths)) {
        $base .= "LƯU Ý QUAN TRỌNG VỀ ẢNH:\n";
        $base .= " - Người dùng KHÔNG gửi ảnh.\n";
        $base .= " - Nếu KHÔNG có ảnh, KHÔNG được tự động tạo SOCIAL POST hoặc RENTAL POST.\n";
        $base .= " - Nếu người dùng có ý định đăng nhưng chưa có ảnh, hãy yêu cầu họ gửi ảnh trước khi tiếp tục.\n";
        $base .= " - Trong trường hợp không có ảnh và nội dung mơ hồ, ưu tiên hiểu là CONSULTATION (tìm phòng).\n\n";
    }


    $base .= "LƯU Ý: Nếu không rõ ý định, hãy hỏi lại người dùng thay vì tự động thực hiện hành động.";
    
    if (!empty($imagePaths)) {
        $count = count($imagePaths);
        $base .= "\nNgười dùng ĐÃ GỬI kèm {$count} hình ảnh. Đường dẫn ảnh đầu tiên: {$imagePaths[0]}. Nếu bài viết/tin đăng cần 1 ảnh, hãy dùng ảnh này. Nếu cần nhiều ảnh, hệ thống đã lưu tất cả.\n";
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

function handleAiActions($pdo, $userId, $aiResponseText, $imagePaths, $userMessage) {
    // Try to parse JSON from response
    $jsonStart = strpos($aiResponseText, '{');
    $jsonEnd = strrpos($aiResponseText, '}');
    
    if ($jsonStart !== false && $jsonEnd !== false) {
        $jsonStr = substr($aiResponseText, $jsonStart, $jsonEnd - $jsonStart + 1);
        $actionData = json_decode($jsonStr, true);

        if ($actionData && isset($actionData['action'])) {
            // --- SAFETY GUARDRAILS ---
            $msgLower = strtolower($userMessage);
            $isSearchIntent = (
                strpos($msgLower, 'tìm') !== false ||
                strpos($msgLower, 'muốn thuê') !== false ||
                strpos($msgLower, 'cần thuê') !== false ||
                strpos($msgLower, 'tư vấn') !== false ||
                strpos($msgLower, 'có phòng') !== false
            );
            $isPostIntent = (
                strpos($msgLower, 'cho thuê') !== false ||
                strpos($msgLower, 'đăng') !== false ||
                strpos($msgLower, 'post') !== false
            );

            // If it looks like a search but AI wants to create listing -> BLOCK IT
            if ($actionData['action'] === 'create_listing' && $isSearchIntent && !$isPostIntent) {
                // Check if the AI just fabricated generic info to fill the form
                $title = $actionData['title'] ?? 'Phòng trọ';
                $price = $actionData['price'] ?? 0;
                $addr = $actionData['address'] ?? '';
                $desc = $actionData['description'] ?? '';

                // If mostly empty/default data, assume it didn't find anything real
                if (
                    (empty($addr) || $addr === 'Chưa cập nhật') && 
                    (stripos($title, 'Phòng trọ') !== false || empty($title)) &&
                    (empty($price) || $price == 0 || stripos($price, 'Thỏa thuận') !== false)
                ) {
                     return [
                        'response' => "Hiện tôi chưa tìm thấy thông tin phù hợp với yêu cầu của bạn. Bạn có thể cho tôi biết thêm khu vực hoặc mức giá mong muốn không?"
                    ];
                }

                // If it seems to have valid info, show it
                $priceStr = is_numeric($price) ? number_format($price) . ' VND' : $price;
                
                $responseMsg = "Tôi tìm thấy thông tin phù hợp với yêu cầu của bạn:\n";
                $responseMsg .= "- **$title**\n";
                $responseMsg .= "- Giá: $priceStr\n";
                if ($addr) $responseMsg .= "- Địa chỉ: $addr\n";
                if ($desc) $responseMsg .= "- Mô tả: $desc";
                
                return ['response' => $responseMsg];
            }
            
            // NEW: If it looks like a search but AI wants to create SOCIAL POST -> BLOCK IT
            if ($actionData['action'] === 'create_post' && $isSearchIntent && !$isPostIntent) {
                 return [
                    'response' => "Hiện tôi chưa tìm thấy thông tin phù hợp với yêu cầu của bạn. Bạn có thể cho tôi biết thêm khu vực hoặc mức giá mong muốn không?"
                ];
            }
            // -------------------------

            if ($actionData['action'] === 'create_post') {
                return executeCreatePost($pdo, $userId, $actionData, $imagePaths);
            } elseif ($actionData['action'] === 'create_listing') {
                return executeCreateListing($pdo, $userId, $actionData, $imagePaths);
            } elseif ($actionData['action'] === 'consultation') {

                $msg = mb_strtolower(trim($userMessage));

                $isSearch = (
                    str_contains($msg, 'tìm') ||
                    str_contains($msg, 'thuê') ||
                    str_contains($msg, 'phòng') ||
                    str_contains($msg, 'trọ')
                );

                if ($isSearch) {
                    $city = null;

                    // 1️⃣ ưu tiên city AI
                    if (!empty($actionData['city'])) {
                        $city = CityNormalizer::normalize($actionData['city']);
                    }

                    // 2️⃣ fallback: parse từ user message
                    if (!$city) {
                        if (preg_match('/h(à|a)\s*n(ộ|o)i/', $msg)) {
                            $city = 'Ha Noi';
                        } elseif (
                            preg_match('/h(ồ|o)\s*ch(í|i)\s*minh/', $msg) ||
                            preg_match('/\bhcm\b/', $msg)
                        ) {
                            $city = 'Ho Chi Minh';
                        }
                    }


                    if ($city) {
                        $stmt = $pdo->prepare("
                            SELECT id, title, price, address
                            FROM listings
                            WHERE city = ? AND status = 'available'
                            ORDER BY created_at DESC
                            LIMIT 5
                        ");
                        $stmt->execute([$city]);
                        $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if ($listings) {
                            $reply = "🏠 **Tôi tìm thấy một số phòng tại {$city}:**\n\n";

                            foreach ($listings as $l) {
                                $reply .= "• **{$l['title']}**\n";
                                $reply .= "  💰 " . number_format($l['price']) . " VND\n";
                                $reply .= "  📍 {$l['address']}\n";
                                $reply .= "  🔗 listing_detail.php?id={$l['id']}\n\n";
                            }

                            return ['response' => $reply];
                        }

                        return [
                            'response' => "😔 Hiện chưa có phòng trọ nào tại **{$city}**. Bạn muốn tìm khu vực khác không?"
                        ];
                    }

                    return [
                        'response' => "📍 Bạn muốn tìm phòng ở **thành phố nào**? (Hà Nội / TP.HCM / Đà Nẵng)"
                    ];
                }

                // ---- NOT SEARCH → SAFE AI TEXT ONLY ----
                $text = $actionData['description'] ?? $actionData['message'] ?? null;

                if ($text && trim($text) !== '') {
                    return ['response' => $text];
                }

                return [
                    'response' => 'Hiện tôi chưa tìm thấy thông tin phù hợp. Bạn có thể nói rõ hơn nhu cầu của mình không?'
                ];
            }
        }
    }

    
    // Default: Chat response (clean up any JSON artifacts if mixed)
    $cleanResponse = trim($aiResponseText);
    if ($cleanResponse === '{}' || $cleanResponse === '[]') {
         return ['response' => 'Hiện tôi chưa tìm thấy thông tin phù hợp với yêu cầu của bạn. Bạn có thể cho tôi biết thêm khu vực hoặc mức giá mong muốn không?'];
    }
    return ['response' => $aiResponseText];
}

function executeCreatePost($pdo, $userId, $data, $imagePaths) {
    if (!$userId) return ['response' => 'Vui lòng đăng nhập để đăng bài.'];
    
    $caption = $data['caption'] ?? 'Bài viết mới';
    // Remove [Hình ảnh X] or [Image X] patterns
    $caption = preg_replace('/\[(Hình ảnh|Image)\s*\d+\]/ui', '', $caption);
    $caption = trim($caption);

    $primaryImage = $imagePaths[0] ?? null; // Posts table currently optimized for 1 image, or we need to update logic.
    // Ideally we should check if posts table supports multiple images via a related table, but for now stick to 1.
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $caption, $primaryImage]); // Keep primary image in posts table for backward compatibility/thumbnail
        $postId = $pdo->lastInsertId();

        if (!empty($imagePaths)) {
            $stmtImg = $pdo->prepare("INSERT INTO post_images (post_id, file_path) VALUES (?, ?)");
            foreach ($imagePaths as $path) {
                $stmtImg->execute([$postId, $path]);
            }
        }
        $pdo->commit();

        return [
            'response' => "Tôi đã đăng bài viết mới của bạn với nội dung: \"{$caption}\" và " . count($imagePaths) . " ảnh.",
            'type' => 'post_created'
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['response' => 'Lỗi khi đăng bài: ' . $e->getMessage()];
    }
}

function executeCreateListing($pdo, $userId, $data, $imagePaths) {
    if (!$userId) return ['response' => 'Vui lòng đăng nhập để đăng tin.'];

    $title = $data['title'] ?? 'Phòng trọ mới';
    $price = intval(str_replace(['.', ','], '', $data['price'] ?? 0));
    $addr = $data['address'] ?? 'Chưa cập nhật';
    $city = $data['city'] ?? '';
    if (empty($city)) {
        if (stripos($addr, 'Hà Nội') !== false || stripos($addr, 'Ha Noi') !== false) {
            $city = 'Ha Noi';
        } elseif (stripos($addr, 'Đà Nẵng') !== false || stripos($addr, 'Da Nang') !== false) {
            $city = 'Da Nang';
        } else {
            $city = null;
        }
    }
    
    // Normalize City
    $city = CityNormalizer::normalize($city);
    if (!$city) {
        return ['response' => 'Vui lòng cho biết thành phố (Hà Nội, TP.HCM, Đà Nẵng).'];
    }

    // Clean address to remove redundant city info
    $addr = preg_replace('/(,\s*)?(?:\bThành phố|\bTP)?\s*(?:\bHà Nội|\bHa Noi|\bHồ Chí Minh|\bHo Chi Minh|\bĐà Nẵng|\bDa Nang)\s*$/iu', '', $addr);
    $addr = trim($addr, " \t\n\r\0\x0B,");

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

        if (!empty($imagePaths)) {
            $stmtImg = $pdo->prepare("INSERT INTO listing_images (listing_id, file_path, is_cover) VALUES (?, ?, ?)");
            foreach ($imagePaths as $index => $path) {
                 $isCover = ($index === 0) ? 1 : 0;
                 $stmtImg->execute([$listingId, $path, $isCover]);
            }
        }
        $pdo->commit();
        
        return [
            'response' => "Tôi đã tạo tin đăng phòng trọ thành công! Bạn có thể xem nó trong danh sách.",
            'type' => 'listing_created'
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['response' => 'Lỗi khi tạo tin đăng: ' . $e->getMessage()];
    }
}
