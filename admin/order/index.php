<?php
session_start();
if (!isset($_SESSION['account_id']) || $_SESSION['account_id'] != 1) {
    header("Location: ../../client/index.php"); // Đã sửa: Lùi 2 cấp
    exit();
}

require_once '../../config/Database.php';
$db = (new Database())->getConnection();
$orders = $db->query("SELECT o.*, a.FullName FROM Orders o JOIN Account a ON o.AccountID = a.AccountID ORDER BY OrderDate DESC");

function getStatusInfo($status) {
    switch ($status) {
        case 0:  return ['label' => 'Chờ xử lý',   'class' => 'status-pending'];
        case 1:  return ['label' => 'Đã xác nhận', 'class' => 'status-confirmed'];
        case 2:  return ['label' => 'Đã giao',     'class' => 'status-delivered'];
        case -1: return ['label' => 'Đã hủy',      'class' => 'status-cancelled'];
        default: return ['label' => 'Không rõ',    'class' => ''];
    }
}

include '../includes/header_admin.php';
?>

<style>
    .status-badge { 
        padding: 6px 12px; 
        border-radius: 20px; 
        font-size: 12px; 
        font-weight: bold; 
        display: inline-block; 
    }
    .status-pending { 
        background: #fff3cd; 
        color: #856404; 
    } 
    .status-confirmed { 
        background: #d1ecf1; 
        color: #0c5460; 
    } 
    .status-delivered { 
        background: #d4edda; 
        color: #155724; 
    } 
    .status-cancelled { 
        background: #f8d7da; 
        color: #721c24; 
    } 
    
    table { 
        width: 100%; 
        border-collapse: collapse; 
        background: #fff; 
        border-radius: 8px; 
        overflow: hidden; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
    }
    th { 
        background: #f8f9fa; 
        padding: 15px; 
        text-align: left; 
        border-bottom: 2px solid #eee; 
    }
    td { 
        padding: 15px; 
        border-bottom: 1px solid #eee; 
    }
    
    .btn-update { 
        padding: 6px 12px; 
        background: #2d5a27; 
        color: white; 
        border: none; 
        border-radius: 4px; 
        cursor: pointer; 
        font-size: 13px; 
    }
    .btn-update:hover { 
        background: #1f401b; 
        }
</style>

<h1>Quản lý đơn hàng</h1>

<table>
    <tr>
        <th>Mã Đơn</th>
        <th>Khách hàng</th>
        <th>Ngày đặt</th> 
        <th style="text-align: right;">Tiền</th>
        <th>Trạng thái</th>
        <th>Hành động</th>
    </tr>
    <?php while($row = $orders->fetch(PDO::FETCH_ASSOC)): 
        $statusInfo = getStatusInfo($row['Status']);
    ?>
    <tr>
        <td>#<?php echo $row['OrderID']; ?></td>
        <td><?php echo htmlspecialchars($row['FullName']); ?></td>
        <td><?php echo date('d/m/Y H:i', strtotime($row['OrderDate'])); ?></td> 
        <td style="text-align: right; font-weight: 600;"><?php echo number_format($row['TotalAmount'], 0, ',', '.'); ?>đ</td>
        
        <td>
            <span class="status-badge <?php echo $statusInfo['class']; ?>">
                <?php echo $statusInfo['label']; ?>
            </span>
        </td>
        
        <td>
            <form action="update_status.php" method="POST" style="display: flex; gap: 5px; align-items: center;">
                <input type="hidden" name="order_id" value="<?php echo $row['OrderID']; ?>">
                <select name="status" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                    <option value="0" <?php if($row['Status'] == 0) echo 'selected'; ?>>Chờ xử lý</option>
                    <option value="1" <?php if($row['Status'] == 1) echo 'selected'; ?>>Đã xác nhận</option>
                    <option value="2" <?php if($row['Status'] == 2) echo 'selected'; ?>>Đã giao</option>
                    <option value="-1" <?php if($row['Status'] == -1) echo 'selected'; ?>>Hủy đơn</option>
                </select>
                <button type="submit" class="btn-update">Cập nhật</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<?php include '../includes/footer_admin.php'; ?>