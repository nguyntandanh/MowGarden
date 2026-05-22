<?php
session_start();

if (!isset($_SESSION['pending_order'])) {
    header("Location: index.php");
    exit();
}

$order = $_SESSION['pending_order'];
$amount = $order['total_amount'];

if (!isset($_SESSION['pending_order']['order_code'])) {
    $_SESSION['pending_order']['order_code'] = "MOW" . time();
}
$orderCode = $_SESSION['pending_order']['order_code'];
$bank_id = "BIDV"; 
$account_no = "6504527952";
$account_name = "NGUYEN TAN DANH";
$qr_url = "https://img.vietqr.io/image/{$bank_id}-{$account_no}-compact2.png?amount={$amount}&addInfo={$orderCode}&accountName=" . urlencode($account_name);

include_once 'includes/header.php';
?>

<style>
    .payment-container { 
        max-width: 600px; 
        margin: 50px auto 100px; 
        background: #fff; 
        padding: 40px; 
        border-radius: 12px; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.08); 
        text-align: center; 
        border: 1px solid #eee; 
    }
    .payment-title { 
        font-size: 24px; 
        font-weight: 800; 
        color: #2d5a27; 
        margin-bottom: 10px; 
    }
    .payment-desc { 
        color: #666; 
        font-size: 15px; 
        margin-bottom: 30px; 
    }
    
    .qr-box { 
        background: #f9f9f9; 
        padding: 20px; 
        border-radius: 12px; 
        display: inline-block; 
        margin-bottom: 30px; 
        border: 1px dashed #ddd; 
    }
    .qr-box img { 
        max-width: 100%; 
        height: auto; 
        border-radius: 8px; 
    }
    
    .payment-info { 
        text-align: left; 
        background: #e8f5e9; 
        padding: 20px; 
        border-radius: 8px; 
        margin-bottom: 30px; 
        color: #2e7d32; 
    }
    .payment-info p { 
        margin: 8px 0; 
        font-size: 15px; 
        display: flex; 
        justify-content: space-between; 
    }
    .payment-info strong { 
        font-weight: 700; 
        color: #1b5e20; 
    }
    
    .status-box { 
        margin-bottom: 20px; 
        color: #d32f2f; 
        font-weight: bold; 
    }
    .btn-confirm { 
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
        transition: 0.3s; 
        margin-bottom: 15px;
    }
    .btn-confirm:hover {
        background: #1f401b;
    }
    .btn-cancel { 
        display: inline-block; 
        margin-top: 10px; 
        color: #777; 
        text-decoration: none; 
        font-size: 14px; 
        font-weight: 600; 
        transition: 0.2s;
    }
    .btn-cancel:hover {
        color: #d32f2f;
    }
</style>

<div class="payment-container">
    <div class="payment-title">THANH TOÁN ĐƠN HÀNG</div>
    <p class="payment-desc">Vui lòng mở App Ngân hàng và quét mã QR bên dưới.</p>
    
    <div class="qr-box">
        <img src="<?php echo $qr_url; ?>" alt="QR Code Thanh Toán">
    </div>
    
    <div class="payment-info">
        <p>
            <span>Khách hàng:</span> 
            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
        </p>
        <p>
            <span>Số tiền:</span> 
            <strong><?php echo number_format($amount, 0, ',', '.'); ?> đ</strong>
        </p>
        <p>
            <span>Nội dung CK:</span> 
            <strong><?php echo $orderCode; ?></strong>
        </p>
    </div>
    
    <div class="status-box" id="statusBox"><i class="fa-solid fa-spinner fa-spin"></i> Đang chờ xác nhận thanh toán...
</div>
    <form action="payment_confirm.php" method="POST" id="paymentForm">
        <input type="hidden" name="orderCode" value="<?php echo $orderCode; ?>">        
        <div style="text-align: center;">
            <a href="checkout.php" class="btn-cancel">Hủy và quay lại giỏ hàng</a>
        </div>
    </form>
</div>

<script>
    let checkInterval = setInterval(function() {
        fetch('check_payment.php?orderCode=<?php echo $orderCode; ?>')
        .then(res => res.json())
        .then(data => {
            if(data.status === 'PAID') {
                clearInterval(checkInterval);
                
                let statusBox = document.getElementById('statusBox');
                statusBox.innerHTML = '<i class="fa-solid fa-check-circle"></i> Đã nhận được tiền! Đang chuyển hướng...';
                statusBox.style.color = '#2d5a27';
                
                setTimeout(() => {
                    document.getElementById('paymentForm').submit();
                }, 1500);
            }
        })
        .catch(error => console.log("Đang chờ thanh toán..."));
    }, 3000); 

    document.getElementById('btnConfirm').addEventListener('click', function() {
        document.getElementById('paymentForm').submit();
    });
</script>

<?php include_once 'includes/footer.php'; ?>