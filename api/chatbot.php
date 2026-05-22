<?php
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
$user_message = $data['message'] ?? '';

if (empty($user_message)) {
    echo json_encode(['error' => 'Vui lòng nhập tin nhắn.']);
    exit();
}

require_once '../config/Database.php';
$db = (new Database())->getConnection();

$stmt = $db->query("SELECT ProductName, Price FROM product WHERE IsDeleted = 0 AND StockQuantity > 0 LIMIT 30");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$context_data = "Danh sách sản phẩm hiện có tại cửa hàng MowGarden:\n";
foreach ($products as $p) {
    $context_data .= "- " . $p['ProductName'] . " (Giá: " . number_format($p['Price'], 0, ',', '.') . " VNĐ)\n";
}

$full_prompt = "THÔNG TIN CỬA HÀNG:\n" . $context_data . "\n\nCHỈ THỊ CHO BẠN:\nBạn là nhân viên tư vấn nhiệt tình của tiệm cây cảnh MowGarden. Hãy trả lời tự nhiên, thân thiện, xưng 'Mình' và gọi khách là 'Bạn'. Trả lời đầy đủ câu chữ. Tư vấn dựa trên thông tin cửa hàng cung cấp ở trên. Nếu khách hỏi cây không có trong danh sách, hãy khéo léo gợi ý cây khác.\n\nKHÁCH HÀNG HỎI:\n" . $user_message . "\n\nCÂU TRẢ LỜI CỦA BẠN:";

$api_key = 'AIzaSyDIYjnKxM1tihHvx2ywmCTpEdsDzAAh4gU'; 
$url = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=' . $api_key;

$post_data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $full_prompt]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    curl_close($ch);
    echo json_encode(['error' => 'Lỗi kết nối nội bộ: ' . $error_msg]);
    exit();
}
curl_close($ch);

if ($http_code == 200) {
    $result = json_decode($response, true);
    
    $bot_reply = '';
    if (isset($result['candidates'][0]['content']['parts'])) {
        foreach ($result['candidates'][0]['content']['parts'] as $part) {
            if (isset($part['text'])) {
                $bot_reply .= $part['text'];
            }
        }
    }
    
    if (empty($bot_reply)) {
        $bot_reply = 'Xin lỗi, hiện tại tôi chưa có câu trả lời phù hợp. Bạn vui lòng liên hệ hotline nhé!';
    }
    
    echo json_encode(['reply' => $bot_reply]);
} else {
    $error_detail = json_decode($response, true);
    $api_error_msg = $error_detail['error']['message'] ?? 'Lỗi không xác định';
    echo json_encode(['error' => 'API Error (' . $http_code . '): ' . $api_error_msg]);
}
?>