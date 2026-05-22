<?php
session_start();
if (!isset($_SESSION['account_id']) || $_SESSION['account_id'] != 1) {
    header("Location: ../../client/index.php");
    exit();
}

require_once '../../config/Database.php';
$db = (new Database())->getConnection();
$query = "SELECT h.HistoryID, h.QuantityAdded, h.DateAdded, p.ProductName, p.ImageUrl 
          FROM `inventoryhistory` h 
          JOIN `Product` p ON h.ProductID = p.ProductID 
          ORDER BY h.DateAdded DESC 
          LIMIT 100";
$stmt = $db->query($query);
$histories = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header_admin.php';
?>

<style>
    .history-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 25px; 
    }
    .history-header h2 { 
        color: #1b4332; 
        margin: 0; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
    }
    .btn-back { 
        background: #fff; 
        color: #333; 
        padding: 8px 15px; 
        border: 1px solid #ccc; 
        border-radius: 6px; 
        text-decoration: none; 
        font-size: 14px; 
        font-weight: bold; 
    }
    .btn-back:hover { 
        background: #f9f9f9; 
    }
    
    .history-table { 
        width: 100%; 
        border-collapse: collapse; 
        background: #fff; 
        border-radius: 8px; 
        overflow: hidden; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
    }
    .history-table th { 
        background: #1b4332; 
        color: white; 
        padding: 15px; 
        text-align: left; 
        font-size: 14px; 
    }
    .history-table td { 
        padding: 15px; 
        border-bottom: 1px solid #f1f1f1; 
        vertical-align: middle; 
    }
    
    .product-info-cell { 
        display: flex; 
        align-items: center; 
        gap: 15px; 
    }
    .product-info-cell img { 
        width: 45px; 
        height: 45px; 
        object-fit: cover; 
        border-radius: 4px; 
        border: 1px solid #ddd; 
    }
    .product-info-cell span { 
        font-weight: 600; 
        color: #333; 
    }
    
    .badge-add { 
        background: #5cb85c; 
        color: white; 
        padding: 5px 12px; 
        border-radius: 20px; 
        font-weight: bold; 
        font-size: 14px; 
        display: inline-block; 
    }
    .time-cell { 
        color: #666; 
        font-size: 14px; 
        }
</style>

<div class="history-header">
    <h2><i class="fa-solid fa-clock-rotate-left"></i> LỊCH SỬ NHẬP KHO</h2>
    <a href="index.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Quay lại Kho hàng</a>
</div>

<table class="history-table">
    <thead>
        <tr>
            <th width="10%">ID</th>
            <th width="40%">Sản phẩm được nhập</th>
            <th width="20%">Số lượng (+)</th>
            <th width="30%">Thời gian nhập</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($histories as $row): $fullPath = "../.." . $row['ImageUrl']; ?>
        <tr>
            <td style="color: #888;">#<?php echo $row['HistoryID']; ?></td>
            <td>
                <div class="product-info-cell">
                    <img src="<?php echo $fullPath; ?>" onerror="this.src='../../Images/default.jpg'">
                    <span><?php echo htmlspecialchars($row['ProductName']); ?></span>
                </div>
            </td>
            <td>
                <span class="badge-add">+ <?php echo $row['QuantityAdded']; ?></span>
            </td>
            <td class="time-cell">
                <?php echo date('d/m/Y - H:i:s', strtotime($row['DateAdded'])); ?>
            </td>
        </tr>
        <?php endforeach; ?>
        
        <?php if (empty($histories)): ?>
        <tr>
            <td colspan="4" style="text-align: center; padding: 30px; color: #888;">Chưa có dữ liệu nhập kho nào.</td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include '../includes/footer_admin.php'; ?>