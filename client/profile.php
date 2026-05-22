<?php
session_start();
if (!isset($_SESSION['account_id'])) {
    header("Location: index.php");
    exit();
}

require_once '../config/Database.php';
$db = (new Database())->getConnection();

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $stmt = $db->prepare("UPDATE Account SET FullName = ?, Email = ?, Phone = ?, Address = ? WHERE AccountID = ?");
    if ($stmt->execute([$fullname, $email, $phone, $address, $_SESSION['account_id']])) {
        $_SESSION['fullname'] = $fullname; 
        $message = "<div style='color: green; margin-bottom: 15px; font-weight: bold;'>Cập nhật thành công!</div>";
    } else {
        $message = "<div style='color: red; margin-bottom: 15px;'>Cập nhật thất bại!</div>";
    }
}

$stmt = $db->prepare("SELECT * FROM Account WHERE AccountID = ?");
$stmt->execute([$_SESSION['account_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt_orders = $db->prepare("SELECT * FROM Orders WHERE AccountID = ? ORDER BY OrderDate DESC");
$stmt_orders->execute([$_SESSION['account_id']]);
$orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

include_once 'includes/header.php';
?>

<style>
    .profile-dashboard { 
        max-width: 1100px; 
        margin: 40px auto; 
        display: grid; 
        grid-template-columns: 1fr 1.5fr; 
        gap: 30px; 
    }
    .card { 
        background: #fff; 
        padding: 25px; 
        border-radius: 12px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
    }
    .title { 
        font-size: 20px; 
        font-weight: 800; 
        color: #2d5a27; 
        margin-bottom: 20px; 
        border-bottom: 2px solid #f0f0f0; 
        padding-bottom: 10px; 
    }
    
    /* Form */
    .form-group { 
        margin-bottom: 15px; 
    }
    .form-group label { 
        display: block; 
        margin-bottom: 5px; 
        font-weight: 600; 
        font-size: 14px; 
    }
    .form-group input { 
        width: 100%; 
        padding: 10px; 
        border: 1px solid #ddd; 
        border-radius: 6px; 
    }
    .btn-save { 
        width: 100%; 
        background: #2d5a27; 
        color: white; 
        padding: 10px; 
        border: none; 
        border-radius: 6px; 
        font-weight: bold; 
        cursor: pointer; 
    }
    
    /* Table */
    table { 
        width: 100%; 
        border-collapse: collapse; 
        font-size: 14px; 
    }
    th { 
        background: #f8f9fa; 
        padding: 10px; 
        text-align: left; 
    }
    td { 
        padding: 10px; 
        border-bottom: 1px solid #eee; 
    }
    .status-badge { 
        padding: 4px 8px; 
        border-radius: 10px; 
        font-size: 11px; 
        font-weight: bold; 
    }
    .status-pending { 
        background: #fff3cd; 
        color: #856404; 
        }
</style>

<div class="profile-dashboard">
    <div class="card">
        <div class="title">CẬP NHẬT THÔNG TIN</div>
        <?php echo $message; ?>
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" value="<?php echo htmlspecialchars($user['Username']); ?>" disabled>
            </div>
            <div class="form-group">
                <label>Họ và tên</label>
                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['FullName']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
            </div>
            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['Phone']); ?>" required>
            </div>
            <div class="form-group">
                <label>Địa chỉ</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($user['Address']); ?>" required>
            </div>
            <button type="submit" class="btn-save">LƯU THAY ĐỔI</button>
        </form>
    </div>

    <div class="card">
        <div class="title">LỊCH SỬ ĐƠN HÀNG</div>
        <?php if (count($orders) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Ngày</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['OrderID']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($order['OrderDate'])); ?></td>
                        <td><?php echo number_format($order['TotalAmount'], 0, ',', '.'); ?>đ</td>
                        <td><span class="status-badge status-pending"><?php echo $order['Status']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Bạn chưa có đơn hàng nào.</p>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>