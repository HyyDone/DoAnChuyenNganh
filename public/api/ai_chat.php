<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/upload.php';
require_once __DIR__ . '/../../helpers/CityNormalizer.php';

session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);


set_time_limit(120);


function debug_log($message) {
    $logFile = __DIR__ . '/../../debug_ai_chat.log';
    $time = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$time] $message" . PHP_EOL, FILE_APPEND);
}


register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        debug_log("FATAL ERROR: " . print_r($error, true));
        if (!headers_sent()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Internal Server Error (Check logs)']);
        }
    }
});

try {
    ob_start();
    
    header('Content-Type: application/json');
    debug_log("Request received via " . $_SERVER['REQUEST_METHOD']);

    $userId = $_SESSION['user_id'] ?? null;
    $uploadedImagePaths = [];

    
    if (!empty($_FILES['images'])) {
        debug_log("FILES['images'] detected: " . print_r($_FILES['images'], true));
        
        $targetDir = __DIR__ . '/../../public/uploads/ai_generated/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        
        if (is_array($_FILES['images']['name'])) {
            $count = count($_FILES['images']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['images']['error'][$i] === 0) {
                    $fileData = [
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error' => $_FILES['images']['error'][$i],
                        'size' => $_FILES['images']['size'][$i]
                    ];
                    $uploadResult = upload_avatar($fileData, $targetDir);
                    if (isset($uploadResult['path'])) {
                        $uploadedImagePaths[] = str_replace('uploads/avatars/', 'uploads/ai_generated/', $uploadResult['path']);
                        debug_log("Image uploaded: " . end($uploadedImagePaths));
                    } else {
                        debug_log("Upload failed for file $i: " . ($uploadResult['error'] ?? 'Unknown error'));
                    }
                } else {
                    debug_log("File upload error code for file $i: " . $_FILES['images']['error'][$i]);
                }
            }
        } else {
            
            $uploadResult = upload_avatar($_FILES['images'], $targetDir);
            if (isset($uploadResult['path'])) {
                $uploadedImagePaths[] = str_replace('uploads/avatars/', 'uploads/ai_generated/', $uploadResult['path']);
                debug_log("Single image uploaded: " . end($uploadedImagePaths));
            } else {
                debug_log("Single upload failed: " . ($uploadResult['error'] ?? 'Unknown error'));
            }
        }
    } else {
        debug_log("No FILES['images'] found.");
    }
    
    

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        
        debug_log("User Message: " . $message);

        if (empty($message) && empty($uploadedImagePaths)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tin nhắn hoặc gửi ảnh.']);
            exit;
        }


    $listingsContext = getListingsContext($pdo);

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

    $systemPrompt = constructSystemPrompt($listingsContext, $uploadedImagePaths);
    debug_log("SYSTEM PROMPT: " . $systemPrompt);

    $aiResponse = callOllama($message, $history, $systemPrompt);

    $actionResult = handleAiActions($pdo, $userId, $aiResponse, $uploadedImagePaths, $message);
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'response' => $actionResult['response'],
        'action_performed' => $actionResult['type'] ?? null
    ]);
    exit;
    }

} catch (Throwable $e) {
    debug_log("EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    if (!headers_sent()) {
         ob_clean();
         echo json_encode(['success' => false, 'message' => 'Internal Server Error (See logs)']);
    }
}

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
    $base .= " - SEARCH (tìm phòng) luôn TRẢ VỀ JSON.\n";
    $base .= " - SOCIAL POST và RENTAL POST chỉ kích hoạt khi có từ khóa RÕ RÀNG.\n";
    $base .= " - Không được tự suy diễn hành động.\n\n";

    $base .= "CHỐNG BỊA ĐẶT:\n";
    $base .= " - Không được tự tạo phòng trọ, bài viết hoặc ID không tồn tại.\n";
    $base .= " - Chỉ trả lời dựa trên dữ liệu được cung cấp từ hệ thống.\n";
    $base .= " - Nếu không có dữ liệu phù hợp, hãy trích xuất JSON 'search' để hệ thống tìm kiếm trong database.\n\n";

    $base .= "NGÔN NGỮ & GIỌNG ĐIỆU:\n";
    $base .= " - Luôn trả lời bằng tiếng Việt.\n";
    $base .= " - Giọng điệu thân thiện, hỗ trợ, giống nhân viên tư vấn thuê trọ.\n";
    $base .= " - Không dùng từ ngữ học thuật hoặc máy móc.\n\n";

    $base .= "1. GREETING (Chào hỏi):\n";
    $base .= "   - Nếu người dùng chào (ví dụ: 'xin chào', 'hi', 'hello'), hãy trả lời chính xác câu này: \"Xin chào, bạn muốn tôi giúp gì?\"\n";
    $base .= "   - Trả lời bằng text thông thường, KHÔNG dùng JSON.\n\n";

    $base .= "2. SEARCH (Tìm kiếm phòng - KHÁCH THUÊ):\n";
    $base .= "   - Khi người dùng muốn TÌM, HỎI THUÊ, xem phòng ở khu vực nào đó hoặc mức giá nào đó.\n";
    $base .= "   - QUAN TRỌNG: BẮT BUỘC TRẢ VỀ JSON. KHÔNG trả lời bằng text.\n";
    $base .= "   - JSON: {\"action\": \"search\", \"city\": \"Tên thành phố (nếu có)\", \"price_max\": \"Giá tối đa (số)\", \"query\": \"Yêu cầu khác (quận, tên đường...)\"}\n";
    $base .= "   - Ví dụ: 'Tìm phòng ở HN giá 3 triệu' -> {\"action\": \"search\", \"city\": \"Hà Nội\", \"price_max\": 3000000}\n";
    $base .= "   - Ví dụ: 'Có phòng nào ở Quận 1 không' -> {\"action\": \"search\", \"query\": \"Quận 1\"}\n";
    $base .= "   - Ví dụ: 'Khu vực Bình Quới giá 7 triệu' -> {\"action\": \"search\", \"query\": \"Bình Quới\", \"price_max\": 7000000}\n\n";

    $base .= "3. SOCIAL POST (Đăng bài - CHỦ TRỌ/CỘNG ĐỒNG):\n";
    $base .= "   - Chỉ khi người dùng yêu cầu rõ ràng: 'đăng tin giúp mình', 'đăng bài', 'viết status', 'post lên tường'.\n";
    $base .= "   - JSON: {\"action\": \"create_post\", ...}\n\n";

    $base .= "4. RENTAL POST (Đăng tin cho thuê - CHỦ TRỌ):\n";
    $base .= "   - Chỉ khi người dùng nói rõ là muốn CHO THUÊ hoặc ĐĂNG TIN CỦA HỌ.\n";
    $base .= "   - QUAN TRỌNG: BẮT BUỘC TRẢ VỀ JSON. KHÔNG trả lời bằng text.\n";
    $base .= "   - JSON: {\"action\": \"create_listing\", \"title\": \"...\", \"price\": 123, \"area\": 20, \"city\": \"...\", \"district\": \"...\", \"address\": \"...\", \"room_type\": \"...\", \"description\": \"...\"}\n";
    $base .= "   - Nếu thiếu thông tin, JSON vẫn phải được tạo với các trường rỗng hoặc giá trị mặc định để hệ thống xử lý.\n\n";
    
    if (empty($imagePaths)) {
        $base .= "LƯU Ý QUAN TRỌNG VỀ ẢNH:\n";
        $base .= " - Người dùng KHÔNG gửi ảnh.\n";
        $base .= " - Nếu KHÔNG có ảnh, KHÔNG được tự động tạo SOCIAL POST hoặc RENTAL POST.\n";
        $base .= " - Nếu người dùng có ý định đăng nhưng chưa có ảnh, hãy yêu cầu họ gửi ảnh trước khi tiếp tục.\n";
        $base .= " - Trong trường hợp không có ảnh và nội dung mơ hồ, ưu tiên hiểu là CONSULTATION (tìm phòng).\n\n";
    }


    $base .= "QUY TẮC TUYỆT ĐỐI (CRITICAL):\n";
    $base .= " - KHI THỰC HIỆN HÀNH ĐỘNG (Search, Create Post, Create Listing), BẮT BUỘC PHẢI TRẢ VỀ JSON DUY NHẤT.\n";
    $base .= " - KHÔNG BAO GIỜ trả lời bằng văn bản kiểu 'Tôi đã tạo tin...', 'Đã đăng thành công...'. HỆ THỐNG sẽ tự thông báo cho người dùng sau khi nhận được JSON từ bạn.\n";
    $base .= " - Nếu người dùng yêu cầu tạo NHIỀU tin cùng lúc, HÃY CHỈ TẠO TIN ĐẦU TIÊN bằng JSON. Sau đó người dùng sẽ yêu cầu tiếp.\n";
    $base .= " - NẾU KHÔNG CÓ JSON, HÀNH ĐỘNG SẼ KHÔNG ĐƯỢC THỰC HIỆN.\n\n";

    return $base;
}

function callOllama($message, $history, $systemPrompt) {
    $messages = [];
    $messages[] = ['role' => 'system', 'content' => $systemPrompt];
    
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
    
    $opts = [
        'http' => [
            'method'  => $method,
            'header'  => "Content-Type: application/json\r\n" .
                         "Accept: application/json\r\n" .
                         "Content-Length: $contentLength\r\n",
            'content' => $jsonPayload,
            'timeout' => 60,
            'ignore_errors' => true
        ]
    ];
    
    $context  = stream_context_create($opts);
    
    
    $stream = @fopen($url, 'r', false, $context);
    
    if ($stream === false) {
        $error = error_get_last();
        return ['success' => false, 'error' => $error['message'] ?? 'Connection failed'];
    }

    $meta = stream_get_meta_data($stream);
    $headers = $meta['wrapper_data'] ?? [];
    $body = stream_get_contents($stream);
    fclose($stream);
    
    $statusLine = $headers[0] ?? '';

    if ($statusLine) {
        preg_match('{HTTP\/\S*\s(\d{3})}', $statusLine, $match);
        $status = $match[1] ?? 500;
        
        if ($status >= 400) {
            return ['success' => false, 'error' => "HTTP $status: " . $body];
        }
    }
    
    return ['success' => true, 'data' => $body];
}

function handleAiActions($pdo, $userId, $aiResponseText, $imagePaths, $userMessage) {
    $jsonStart = strpos($aiResponseText, '{');
    $jsonEnd = strrpos($aiResponseText, '}');
    
    if ($jsonStart !== false && $jsonEnd !== false) {
        $jsonStr = substr($aiResponseText, $jsonStart, $jsonEnd - $jsonStart + 1);
        debug_log("JSON EXTRACTED: " . $jsonStr);
        
        $actionData = json_decode($jsonStr, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            debug_log("JSON DECODE ERROR: " . json_last_error_msg());
        } else {
            debug_log("ACTION DATA: " . print_r($actionData, true));
        }

        if ($actionData && isset($actionData['action'])) {
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

            
            if (($actionData['action'] === 'create_listing' || $actionData['action'] === 'create_post') && $isSearchIntent && !$isPostIntent) {
                $actionData['action'] = 'consultation';
            }

            if ($actionData['action'] === 'create_post') {
                if (empty($imagePaths)) {
                    return ['response' => 'Để đăng bài viết, bạn cần gửi kèm ít nhất 1 hình ảnh. Vui lòng tải ảnh lên và yêu cầu lại.'];
                }
                return executeCreatePost($pdo, $userId, $actionData, $imagePaths);
            } elseif ($actionData['action'] === 'create_listing') {
                if (empty($imagePaths)) {
                    return ['response' => 'Để đăng tin phòng trọ, bạn cần gửi kèm hình ảnh thực tế. Vui lòng tải ảnh lên và yêu cầu lại.'];
                }
                return executeCreateListing($pdo, $userId, $actionData, $imagePaths);
            } elseif ($actionData['action'] === 'search' || $actionData['action'] === 'consultation') {
                
                $city = null;
                $priceMax = null;
                $queryText = "";

                if (!empty($actionData['city'])) {
                    $city = CityNormalizer::normalize($actionData['city']);
                }
                
                if (!empty($actionData['price_max'])) {
                    $priceMax = intval(str_replace(['.', ','], '', $actionData['price_max']));
                }

                if (!empty($actionData['query'])) {
                    $queryText = $actionData['query'];
                }

                
                if (!$city && !$priceMax && !$queryText) {
                     $msg = function_exists('mb_strtolower') ? mb_strtolower($userMessage) : strtolower($userMessage);
                     if (strpos($msg, 'hà nội') !== false || strpos($msg, 'hn') !== false) $city = 'Ha Noi';
                     elseif (strpos($msg, 'hồ chí minh') !== false || strpos($msg, 'hcm') !== false) $city = 'Ho Chi Minh';
                     elseif (strpos($msg, 'đà nẵng') !== false || strpos($msg, 'đn') !== false) $city = 'Da Nang';
                     
                     if (preg_match('/(\d+)\s*(triệu|tr)/i', $msg, $m)) {
                         $priceMax = floatval($m[1]) * 1000000;
                     }
                }

                $sql = "SELECT id, title, price, address, city FROM listings WHERE status = 'available' ";
                $params = [];

                if ($city) {
                    $sql .= " AND city LIKE ? "; 
                    $params[] = "%$city%";
                }
                
                if ($priceMax) {
                    $sql .= " AND price <= ? ";
                    $params[] = $priceMax;
                }

                if ($queryText) {
                    $sql .= " AND (title LIKE ? OR address LIKE ? OR description LIKE ?) ";
                    $searchTerm = "%$queryText%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
                
                $sql .= " ORDER BY created_at DESC LIMIT 5";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($listings) {
                    $reply = "🔍 **Kết quả tìm kiếm" . ($city ? " tại $city" : "") . ":**\n\n";
                    foreach ($listings as $l) {
                        $reply .= "🏠 **{$l['title']}**\n";
                        $reply .= "💰 " . number_format($l['price']) . " VND\n";
                        
                        $reply .= "👉 [Xem chi tiết](listing_detail.php?id={$l['id']})\n\n";
                    }
                    return ['response' => $reply];
                } else {
                     return ['response' => "😞 Không tìm thấy phòng nào phù hợp" . ($city ? " tại $city" : "") . ($priceMax ? " với giá dưới " . number_format($priceMax) : "") . "."];
                }
            }

                return [
                    'response' => 'Hiện tôi chưa tìm thấy thông tin phù hợp. Bạn có thể nói rõ hơn nhu cầu của mình không?'
                ];
        }
    }

    $cleanResponse = trim($aiResponseText);
    if ($cleanResponse === '{}' || $cleanResponse === '[]') {
         return ['response' => 'Hiện tôi chưa tìm thấy thông tin phù hợp với yêu cầu của bạn. Bạn có thể cho tôi biết thêm khu vực hoặc mức giá mong muốn không?'];
    }
    return ['response' => $aiResponseText];
}

function executeCreatePost($pdo, $userId, $data, $imagePaths) {
    if (!$userId) return ['response' => 'Vui lòng đăng nhập để đăng bài.'];
    
    $caption = $data['caption'] ?? $data['content'] ?? 'Bài viết mới';
    $caption = preg_replace('/\[(Hình ảnh|Image)\s*\d+\]/ui', '', $caption);
    $caption = trim($caption);

    $primaryImage = $imagePaths[0] ?? null; 
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $caption, $primaryImage]);
        $postId = $pdo->lastInsertId();

        if (!empty($imagePaths)) {
            $stmtImg = $pdo->prepare("INSERT INTO post_images (post_id, file_path) VALUES (?, ?)");
            foreach ($imagePaths as $path) {
                $stmtImg->execute([$postId, $path]);
            }
        }
        $pdo->commit();

        return [
            'response' => "Tôi đã đăng bài viết mới của bạn thành công! Bạn có thể xem nó tại: <a href='/index.php#post-{$postId}' target='_blank'>Xem bài viết</a>",
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
    
    $city = CityNormalizer::normalize($city);
    if (!$city) {
        return ['response' => 'Vui lòng cho biết thành phố (Hà Nội, TP.HCM, Đà Nẵng).'];
    }

    $addr = preg_replace('/(,\s*)?(?:\bThành phố|\bTP)?\s*(?:\bHà Nội|\bHa Noi|\bHồ Chí Minh|\bHo Chi Minh|\bĐà Nẵng|\bDa Nang)\s*$/iu', '', $addr);
    $addr = trim($addr, " \t\n\r\0\x0B,");

    $district = $data['district'] ?? '';
    $area = isset($data['area']) ? floatval($data['area']) : null;

    $rawType = strtolower($data['room_type'] ?? '');
    $type = 'private'; 
    if (strpos($rawType, 'studio') !== false) {
        $type = 'studio';
    } elseif (strpos($rawType, 'share') !== false || strpos($rawType, 'ghép') !== false || strpos($rawType, 'chung') !== false) {
        $type = 'share';
    } elseif (strpos($rawType, 'private') !== false || strpos($rawType, 'riêng') !== false) {
        $type = 'private';
    }
    $desc = strip_tags($data['description'] ?? '');

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO listings (owner_id, title, price, address, city, district, area, room_type, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')");
        $stmt->execute([$userId, $title, $price, $addr, $city, $district, $area, $type, $desc]);
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
            'response' => "Tôi đã tạo tin đăng phòng trọ thành công! Bạn có thể xem chi tiết tại: <a href='/listing_detail.php?id={$listingId}' target='_blank'>Xem tin đăng</a>",
            'type' => 'listing_created'
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['response' => 'Lỗi khi tạo tin đăng: ' . $e->getMessage()];
    }
}
