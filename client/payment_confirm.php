<?php
session_start();
require_once '../config/Database.php';
require_once '../classes/Product.php';

if (!isset($_SESSION['pending_order'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);
$order = $_SESSION['pending_order'];

try {
    $db->beginTransaction();

    $query_order = "INSERT INTO `Orders` (AccountID, OrderDate, TotalAmount, PaymentMethod, PaymentStatus, Status, ShippingAddress, CustomerPhone, CustomerName)
                    VALUES (:account_id, NOW(), :total_amount, :payment_method, :payment_status, :status, :shipping_address, :customer_phone, :customer_name)";
    $stmt_order = $db->prepare($query_order);
    $stmt_order->execute([
        ':account_id' => $order['account_id'],
        ':total_amount' => $order['total_amount'],
        ':payment_method' => $order['payment_method'],
        ':payment_status' => 'Đã thanh toán (Online)',
        ':status' => '0',
        ':shipping_address' => $order['shipping_address'],
        ':customer_phone' => $order['customer_phone'],
        ':customer_name' => $order['customer_name']
    ]);
    
    $new_order_id = $db->lastInsertId();
    $stmt_detail = $db->prepare("INSERT INTO `OrderDetail` (OrderID, ProductID, Quantity, Price) VALUES (?, ?, ?, ?)");
    $stmt_stock = $db->prepare("UPDATE `Product` SET StockQuantity = StockQuantity - ? WHERE ProductID = ?");
    $ids = array_keys($order['cart_items']);
    $stmt_checkout = $product->getProductsByIDs($ids);
    $cart_items = $stmt_checkout->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart_items as $item) {
        $p_id = $item['ProductID'];
        $qty = $order['cart_items'][$p_id];
        $stmt_detail->execute([$new_order_id, $p_id, $qty, $item['Price']]);
        $stmt_stock->execute([$qty, $p_id]);
    }

    $db->commit();
    
    if (isset($order['order_code'])) {
        $orderCode = $order['order_code'];
        $file_path = "../payments/" . $orderCode . ".txt";
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    unset($_SESSION['cart']);
    unset($_SESSION['pending_order']);
    
    $_SESSION['order_success'] = true;

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['order_error'] = "Lỗi hệ thống trong quá trình ghi nhận đơn hàng: " . $e->getMessage();
}

header("Location: checkout.php");
exit();
?>