<?php
header('Content-Type: application/json');

$requestBody = file_get_contents('php://input');

file_put_contents('webhook_log.txt', "[" . date('Y-m-d H:i:s') . "] " . $requestBody . PHP_EOL, FILE_APPEND);

$data = json_decode($requestBody, true);
$content = $data['transactionContent'] ?? $data['content'] ?? '';
$amount_received = $data['amountIn'] ?? $data['transferAmount'] ?? 0;

if (!empty($content) && $amount_received > 0) {
    $content = strtoupper($content); 
    preg_match('/MOW\d+/', $content, $matches);
    if (!empty($matches[0])) {
        $orderCode = $matches[0];
        if (!is_dir('../payments')) {
            mkdir('../payments', 0777, true);
        }
        $file_path = "../payments/" . $orderCode . ".txt";
        file_put_contents($file_path, "SUCCESS_REAL_MONEY");
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Đã ghi nhận thanh toán cho mã $orderCode"]);
    } else {
        http_response_code(200);
        echo json_encode(["success" => false, "message" => "Không tìm thấy mã MOW trong nội dung"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Dữ liệu Webhook không đúng định dạng SePay"]);
}
?>