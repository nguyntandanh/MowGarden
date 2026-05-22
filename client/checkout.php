<?php
session_start();
require_once '../config/Database.php';
require_once '../classes/Product.php';

$order_success = false;
$error_message = '';

if (isset($_SESSION['order_success'])) {
    $order_success = true;
    unset($_SESSION['order_success']);
}

if (isset($_SESSION['order_error'])) {
    $error_message = $_SESSION['order_error'];
    unset($_SESSION['order_error']);
}

if (!$order_success && (!isset($_SESSION['cart']) || empty($_SESSION['cart']))) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$default_name = '';
$default_phone = '';
$account_id = isset($_SESSION['account_id']) ? $_SESSION['account_id'] : 4;

$query_profile = "SELECT * FROM `Account` WHERE `AccountID` = :account_id LIMIT 1";
$stmt_profile = $db->prepare($query_profile);
$stmt_profile->bindParam(':account_id', $account_id, PDO::PARAM_INT);
if ($stmt_profile->execute()) {
    $user_profile = $stmt_profile->fetch(PDO::FETCH_ASSOC);
    if ($user_profile) {
        $default_name = isset($user_profile['FullName']) ? $user_profile['FullName'] : (isset($user_profile['Username']) ? $user_profile['Username'] : '');
        $default_phone = isset($user_profile['Phone']) ? $user_profile['Phone'] : '';
    }
}

$total_amount = 0;
$cart_count = 0;
$cart_items = [];

if (!$order_success && isset($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $stmt_checkout = $product->getProductsByIDs($ids);
    $cart_items = $stmt_checkout->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart_items as $item) {
        $qty = $_SESSION['cart'][$item['ProductID']];
        $total_amount += $item['Price'] * $qty;
        $cart_count += $qty;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    
    $province = isset($_POST['province']) ? trim($_POST['province']) : '';
    $district = isset($_POST['district']) ? trim($_POST['district']) : '';
    $ward = isset($_POST['ward']) ? trim($_POST['ward']) : '';
    $specific_address = isset($_POST['specific_address']) ? trim($_POST['specific_address']) : '';
    
    $payment_method = $_POST['payment_method'];

    if (!empty($customer_name) && !empty($customer_phone) && !empty($province) && !empty($district) && !empty($ward) && !empty($specific_address)) {
        
        $shipping_address = $specific_address . ", " . $ward . ", " . $district . ", " . $province;

        if ($payment_method == 'Online') {
            $_SESSION['pending_order'] = [
                'account_id' => $account_id,
                'customer_name' => $customer_name,
                'customer_phone' => $customer_phone,
                'shipping_address' => $shipping_address,
                'total_amount' => $total_amount,
                'payment_method' => $payment_method,
                'cart_items' => $_SESSION['cart']
            ];
            header("Location: payment.php");
            exit();
            
        } else {
            try {
                $db->beginTransaction();
                $query_order = "INSERT INTO `Orders` (AccountID, OrderDate, TotalAmount, PaymentMethod, PaymentStatus, Status, ShippingAddress, CustomerPhone, CustomerName)
                                VALUES (:account_id, NOW(), :total_amount, :payment_method, :payment_status, :status, :shipping_address, :customer_phone, :customer_name)";
                $stmt_order = $db->prepare($query_order);
                $payment_status = 'Chưa thanh toán';
                $status = '0';
                $stmt_order->bindParam(':account_id', $account_id, PDO::PARAM_INT);
                $stmt_order->bindParam(':total_amount', $total_amount);
                $stmt_order->bindParam(':payment_method', $payment_method);
                $stmt_order->bindParam(':payment_status', $payment_status);
                $stmt_order->bindParam(':status', $status);
                $stmt_order->bindParam(':shipping_address', $shipping_address);
                $stmt_order->bindParam(':customer_phone', $customer_phone);
                $stmt_order->bindParam(':customer_name', $customer_name);
                $stmt_order->execute();
                $new_order_id = $db->lastInsertId();
                $query_detail = "INSERT INTO `OrderDetail` (OrderID, ProductID, Quantity, Price) VALUES (:order_id, :product_id, :quantity, :price)";
                $stmt_detail = $db->prepare($query_detail);
                $query_update_stock = "UPDATE `Product` SET StockQuantity = StockQuantity - :qty WHERE ProductID = :p_id";
                $stmt_stock = $db->prepare($query_update_stock);

                foreach ($cart_items as $item) {
                    $p_id = $item['ProductID'];
                    $qty = $_SESSION['cart'][$p_id];
                    $price = $item['Price'];
                    $stmt_detail->bindParam(':order_id', $new_order_id, PDO::PARAM_INT);
                    $stmt_detail->bindParam(':product_id', $p_id, PDO::PARAM_INT);
                    $stmt_detail->bindParam(':quantity', $qty, PDO::PARAM_INT);
                    $stmt_detail->bindParam(':price', $price);
                    $stmt_detail->execute();
                    $stmt_stock->bindParam(':qty', $qty, PDO::PARAM_INT);
                    $stmt_stock->bindParam(':p_id', $p_id, PDO::PARAM_INT);
                    $stmt_stock->execute();
                }

                $db->commit();
                unset($_SESSION['cart']);
                $order_success = true;

            } catch (Exception $e) {
                $db->rollBack();
                $error_message = "Có lỗi hệ thống xảy ra: " . $e->getMessage();
            }
        }
    } else {
        $error_message = "Vui lòng điền và chọn đầy đủ thông tin giao hàng!";
    }
}

include_once 'includes/header.php';
?>

<style>
    .checkout-container { 
        max-width: 1200px; 
        margin: 40px auto 80px; 
        padding: 0 20px; 
        display: flex; 
        gap: 40px; 
        flex-wrap: wrap; 
    }
    .checkout-title { 
        width: 100%; 
        font-size: 26px; 
        font-weight: 800; 
        color: #2d5a27; 
        text-transform: uppercase; 
        margin-bottom: 10px; 
        border-bottom: 2px solid #eee; 
        padding-bottom: 15px; 
    }
    
    .checkout-form { 
        flex: 1.5; 
        min-width: 500px; 
    }
    .form-section { 
        background: white; 
        padding: 30px; 
        border-radius: 8px; 
        border: 1px solid #eee; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.03); 
        margin-bottom: 25px; 
    }
    .section-subtitle { 
        font-size: 18px; 
        font-weight: 700; 
        color: #333; 
        margin: 0 0 20px 0; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
    }
    
    .form-group { 
        margin-bottom: 20px; 
    }
    .form-group label { 
        display: block; 
        font-size: 13px; 
        font-weight: 600; 
        color: #555; 
        margin-bottom: 8px; 
        text-transform: uppercase; 
    }
    .form-control { 
        width: 100%; 
        height: 45px; 
        border: 1px solid #ddd; 
        border-radius: 6px; 
        padding: 0 15px; 
        font-family: inherit; 
        font-size: 14px; 
        outline: none; 
        box-sizing: border-box; 
        transition: 0.3s; 
        background-color: #fff; 
    }
    .form-control:focus { 
        border-color: #2d5a27; 
        box-shadow: 0 0 5px rgba(45,90,39,0.1); 
    }
    
    .address-row { 
        display: grid; 
        grid-template-columns: repeat(3, 1fr); 
        gap: 15px; 
        margin-bottom: 20px; 
    }
    
    .payment-methods { 
        display: flex; 
        flex-direction: column; 
        gap: 12px; 
    }
    .payment-method-item { 
        border: 1px solid #ddd; 
        padding: 15px; 
        border-radius: 6px; 
        display: flex; 
        align-items: center; 
        gap: 12px; 
        cursor: pointer; 
        transition: 0.3s; 
    }
    .payment-method-item:hover { 
        border-color: #2d5a27; 
        background: #fdfdfd; 
    }
    .payment-method-item input[type="radio"] { 
        accent-color: #2d5a27; 
        width: 18px; 
        height: 18px; 
        margin: 0; 
    }
    .payment-method-item label { 
        font-weight: 600; 
        font-size: 14px; 
        color: #333; 
        cursor: pointer; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
    }

    .cod-notice-box { 
        display: none; 
        background-color: #fff3cd; 
        color: #856404; 
        padding: 15px 20px; 
        border-radius: 6px; 
        font-size: 13.5px; 
        font-weight: 600; 
        line-height: 1.5; 
        margin-top: 12px; 
        border: 1px solid #ffeeba; 
    }
    .cod-notice-box i { 
        margin-right: 6px; 
        font-size: 16px; 
    }

    .checkout-summary { 
        flex: 1; 
        min-width: 350px; 
    }
    .summary-box { 
        background: white; 
        padding: 30px; 
        border-radius: 8px; 
        border: 1px solid #eee; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.03); 
        position: sticky; top: 100px; 
    }
    
    .checkout-item-list { 
        max-height: 240px; 
        overflow-y: auto; 
        margin-bottom: 20px; 
        padding-right: 5px; 
    }
    .checkout-item { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 12px 0; 
        border-bottom: 1px solid #f5f5f5; 
        font-size: 14px; 
    }
    .checkout-item .item-info { 
        max-width: 70%; 
    }
    .checkout-item .item-info h5 { 
        margin: 0 0 4px 0; 
        font-size: 14px; 
        color: #333; 
        font-weight: 600; 
        display: -webkit-box; 
        -webkit-line-clamp: 1; 
        -webkit-box-orient: vertical; 
        overflow: hidden; 
    }
    .checkout-item .item-info span { 
        font-size: 12px; 
        color: #777; 
    }
    .checkout-item .item-price { 
        font-weight: 800; 
        color: #333; 
        white-space: nowrap; 
    }
    
    .summary-row { 
        display: flex; 
        justify-content: space-between; 
        margin-bottom: 15px; 
        font-size: 14px; 
        color: #666; 
    }
    .summary-total { 
        display: flex; 
        justify-content: space-between; 
        margin-top: 20px; 
        padding-top: 20px; 
        border-top: 1px solid #eee; 
        font-size: 20px; 
        font-weight: 800; 
        color: #2d5a27; 
        white-space: nowrap; 
    }
    
    .btn-submit-order { 
        display: block; 
        width: 100%; 
        background: #2d5a27; 
        color: white; 
        border: none; 
        height: 50px; 
        border-radius: 25px; 
        font-size: 16px; 
        font-weight: bold; 
        cursor: pointer; 
        margin-top: 25px; 
        transition: 0.3s; 
    }
    .btn-submit-order:hover { 
        background: #1f401b; 
    }
    .alert-danger { 
        background: #ffebee; 
        color: #c62828; 
        padding: 12px 20px; 
        border-radius: 6px; 
        font-size: 14px; 
        font-weight: 600; 
        margin-bottom: 20px; 
        border: 1px solid #ffcdd2; 
    }

    .success-box { 
        max-width: 600px; 
        margin: 60px auto 100px; 
        text-align: center; 
        background: white; 
        padding: 50px 40px; 
        border-radius: 12px; 
        border: 1px solid #eee; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
    }
    .success-icon { 
        font-size: 70px; 
        color: #2d5a27; 
        margin-bottom: 25px; 
    }
    .success-box h3 { 
        font-size: 24px; 
        color: #333; 
        font-weight: 800; 
    }
    .success-box p { 
        color: #666; 
        font-size: 15px; 
        line-height: 1.6; 
        margin-bottom: 30px; 
    }
    .btn-back-home { 
        display: inline-block; 
        background: #2d5a27; 
        color: white; 
        text-decoration: none; 
        padding: 12px 35px; 
        border-radius: 25px; 
        font-weight: bold; 
        transition: 0.3s; 
    }
    .btn-back-home:hover { 
        background: #1f401b; 
        }
</style>

<?php if ($order_success): ?>
    <div class="success-box">
        <div class="success-icon"><i class="fa-solid fa-circle-check"></i></div>
        <h3>ĐẶT HÀNG THÀNH CÔNG!</h3>
        <p>Cảm ơn bạn đã mua sắm tại MowGarden. Đơn hàng của bạn đã được ghi nhận vào cơ sở dữ liệu hệ thống. Đội ngũ tư vấn vườn cây sẽ liên hệ trực tiếp với bạn qua điện thoại để xác nhận lịch giao cây.</p>
        <a href="index.php" class="btn-back-home">Quay lại Trang chủ</a>
    </div>
<?php else: ?>
    <div class="checkout-container">
        <div class="checkout-title">Thanh toán đơn hàng</div>
        
        <form id="checkoutForm" action="checkout.php" method="POST" class="checkout-form">
            <?php if (!empty($error_message)): ?>
                <div class="alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="form-section">
                <div class="section-subtitle"><i class="fa-solid fa-truck-ramp-box" style="color:#2d5a27;"></i> Thông tin nhận hàng</div>
                
                <div class="form-group">
                    <label>Họ và tên người nhận</label>
                    <input type="text" name="customer_name" class="form-control" placeholder="Nhập họ tên người nhận cây..." required value="<?php echo htmlspecialchars($default_name); ?>">
                </div>
                
                <div class="form-group">
                    <label>Số điện thoại liên lạc</label>
                    <input type="tel" name="customer_phone" class="form-control" placeholder="Nhập số điện thoại nhận hàng..." required value="<?php echo htmlspecialchars($default_phone); ?>">
                </div>
                
                <div class="form-group">
                    <label>Địa chỉ giao hàng</label>
                    <div class="address-row">
                        <select id="province" name="province" class="form-control" required>
                            <option value="" data-code="">-- Đang tải dữ liệu... --</option>
                        </select>
                        <select id="district" name="district" class="form-control" required>
                            <option value="" data-code="">-- Chọn Quận/Huyện --</option>
                        </select>
                        <select id="ward" name="ward" class="form-control" required>
                            <option value="" data-code="">-- Chọn Xã/Phường/Khu phố --</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" id="specificAddressGroup" style="display: none;">
                    <label>Số nhà, tên đường cụ thể</label>
                    <input type="text" id="specific_address" name="specific_address" class="form-control" placeholder="Ghi rõ số nhà, ngõ ngách, tên đường cụ thể...">
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-subtitle">
                    <i class="fa-solid fa-credit-card" style="color:#2d5a27;"></i> Phương thức thanh toán
                </div>
                <div class="payment-methods">
                    <div class="payment-method-item">
                        <input type="radio" id="cod" name="payment_method" value="COD" checked>
                        <label for="cod">
                            <i class="fa-solid fa-hand-holding-dollar" style="color:#555;"></i> Thanh toán khi nhận hàng (COD)
                        </label>
                    </div>
                    <div class="payment-method-item">
                        <input type="radio" id="online" name="payment_method" value="Online">
                        <label for="online">
                            <i class="fa-solid fa-building-columns" style="color:#555;"></i> Chuyển khoản ngân hàng trực tuyến</label>
                    </div>
                </div>
                
                <div id="codNotice" class="cod-notice-box">
                    <i class="fa-solid fa-circle-info"></i> Ghi chú: Hình thức COD chỉ áp dụng và giao dịch tiền mặt đối với khu vực xung quanh TP. Thủ Dầu Một.
                </div>
            </div>
            
            <button type="submit" id="hidden-submit" name="place_order" style="display: none;"></button>
        </form>
        
        <div class="checkout-summary">
            <div class="summary-box">
                <div class="section-subtitle" style="margin-bottom: 15px;">
                    <i class="fa-solid fa-basket-shopping" style="color:#2d5a27;"></i> Đơn hàng gồm có
                </div>
                
                <div class="checkout-item-list">
                    <?php 
                    foreach ($cart_items as $item) {
                        $id = $item['ProductID'];
                        $qty = $_SESSION['cart'][$id];
                        $subtotal = $item['Price'] * $qty;
                    ?>
                    <div class="checkout-item">
                        <div class="item-info">
                            <h5><?php echo $item['ProductName']; ?></h5>
                            <span>Số lượng: <?php echo $qty; ?></span>
                        </div>
                        <div class="item-price"><?php echo number_format($subtotal, 0, ',', '.'); ?>&nbsp;đ</div>
                    </div>
                    <?php } ?>
                </div>
                
                <div class="summary-row">
                    <span>Tạm tính</span>
                    <span style="font-weight: 600; color:#333;"><?php echo number_format($total_amount, 0, ',', '.'); ?>&nbsp;đ</span>
                </div>
                <div class="summary-row">
                    <span>Phí vận chuyển</span>
                    <span style="color: #2e7d32; font-weight: 600;">Miễn phí</span>
                </div>
                
                <div class="summary-total">
                    <span>Tổng cộng</span>
                    <span><?php echo number_format($total_amount, 0, ',', '.'); ?>&nbsp;đ</span>
                </div>
                
                <button type="button" class="btn-submit-order" onclick="document.getElementById('hidden-submit').click();">
                    <i class="fa-solid fa-lock"></i> XÁC NHẬN ĐẶT HÀNG
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    jQuery(document).ready(function($) {
        const provinceSel = $('#province');
        const districtSel = $('#district');
        const wardSel = $('#ward');
        const specificGroup = $('#specificAddressGroup');
        const specificInput = $('#specific_address');
        const codNotice = $('#codNotice');

        const apiHost = "https://provinces.open-api.vn/api/v1/";

        function loadProvinces() {
            $.get(apiHost + "?depth=1", function(data) {
                provinceSel.empty().append('<option value="" data-code="">-- Chọn Tỉnh/Thành phố --</option>');
                data.forEach(item => {
                    provinceSel.append(`<option value="${item.name}" data-code="${item.code}">${item.name}</option>`);
                });
                
                handlePaymentMethodLogic();
            });
        }

        function handlePaymentMethodLogic() {
            const isCOD = $('#cod').is(':checked');
            
            if (isCOD) {
                let bdOption = provinceSel.find("option").filter(function() {
                    return $(this).text().includes('Bình Dương');
                });

                if(bdOption.length > 0) {
                    provinceSel.val(bdOption.val()).css('pointer-events', 'none').css('background-color', '#f5f5f5');
                    let bdCode = bdOption.attr('data-code');
                    
                    $.get(apiHost + "p/" + bdCode + "?depth=2", function(data) {
                        districtSel.empty().append('<option value="" data-code="">-- Chọn Quận/Huyện --</option>');
                        data.districts.forEach(item => {
                            if(item.name.includes('Thủ Dầu Một')) {
                                districtSel.append(`<option value="${item.name}" data-code="${item.code}">${item.name}</option>`);
                            }
                        });
                        
                        let tdmOption = districtSel.find("option:contains('Thủ Dầu Một')");
                        if (tdmOption.length > 0) {
                            districtSel.val(tdmOption.val()).css('pointer-events', 'none').css('background-color', '#f5f5f5');
                            districtSel.trigger('change'); 
                        }
                    });
                }
                
                codNotice.html('<i class="fa-solid fa-circle-info"></i> Ghi chú: Hình thức COD chỉ áp dụng và giao dịch tiền mặt đối với khu vực xung quanh TP. Thủ Dầu Một (trực thuộc TP.HCM sau sáp nhập). Hệ thống đã tự động giới hạn vùng định tuyến.').slideDown(300);
            } else {
                provinceSel.css('pointer-events', 'auto').css('background-color', '#fff');
                districtSel.css('pointer-events', 'auto').css('background-color', '#fff');
                
                provinceSel.val('');
                districtSel.empty().append('<option value="">-- Chọn Quận/Huyện --</option>');
                wardSel.empty().append('<option value="">-- Chọn Xã/Phường/Khu phố --</option>');
                
                specificGroup.hide();
                specificInput.removeAttr('required');
                codNotice.slideUp(300);
            }
        }

        provinceSel.on('change', function() {
            const provinceCode = $(this).find('option:selected').attr('data-code');
            districtSel.empty().append('<option value="">-- Đang tải dữ liệu... --</option>');
            wardSel.empty().append('<option value="">-- Chọn Xã/Phường/Khu phố --</option>');
            specificGroup.hide();
            specificInput.removeAttr('required');

            if (provinceCode) {
                $.get(apiHost + "p/" + provinceCode + "?depth=2", function(data) {
                    districtSel.empty().append('<option value="">-- Chọn Quận/Huyện --</option>');
                    data.districts.forEach(item => {
                        districtSel.append(`<option value="${item.name}" data-code="${item.code}">${item.name}</option>`);
                    });
                });
            } else {
                districtSel.empty().append('<option value="">-- Chọn Quận/Huyện --</option>');
            }
        });

        districtSel.on('change', function() {
            const districtCode = $(this).find('option:selected').attr('data-code');
            wardSel.empty().append('<option value="">-- Đang tải dữ liệu... --</option>');
            specificGroup.hide();
            specificInput.removeAttr('required');

            if (districtCode) {
                $.get(apiHost + "d/" + districtCode + "?depth=2", function(data) {
                    wardSel.empty().append('<option value="">-- Chọn Xã/Phường/Khu phố --</option>');
                    data.wards.forEach(item => {
                        wardSel.append(`<option value="${item.name}" data-code="${item.code}">${item.name}</option>`);
                    });
                });
            } else {
                wardSel.empty().append('<option value="">-- Chọn Xã/Phường/Khu phố --</option>');
            }
        });

        wardSel.on('change', function() {
            if ($(this).val() !== "") {
                specificGroup.slideDown(250);
                specificInput.attr('required', 'required');
            } else {
                specificGroup.slideUp(200);
                specificInput.removeAttr('required');
            }
        });

        $('input[name="payment_method"]').on('change', function() {
            handlePaymentMethodLogic();
        });

        loadProvinces();
    });
</script>

<?php include_once 'includes/footer.php'; ?>