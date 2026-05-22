<?php
session_start();
if (!isset($_SESSION['account_id']) || $_SESSION['account_id'] != 1) {
    header("Location: ../../client/index.php");
    exit();
}

require_once '../../config/Database.php';
$db = (new Database())->getConnection();

$message = '';
$message_type = '';

// --- XỬ LÝ NHẬP KHO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_stock'])) {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $add_qty = isset($_POST['add_qty']) ? (int)$_POST['add_qty'] : 0;

    if ($product_id > 0 && $add_qty > 0) {
        try {
            $db->beginTransaction();

            // 1. Cộng thêm số lượng vào bảng Product
            $stmt_update = $db->prepare("UPDATE `Product` SET StockQuantity = StockQuantity + ? WHERE ProductID = ?");
            $stmt_update->execute([$add_qty, $product_id]);

            // 2. Lưu vào bảng lịch sử nhập kho
            $stmt_history = $db->prepare("INSERT INTO `inventoryhistory` (ProductID, QuantityAdded, DateAdded) VALUES (?, ?, NOW())");
            $stmt_history->execute([$product_id, $add_qty]);

            $db->commit();
            $message = "Nhập kho thành công! Đã thêm {$add_qty} sản phẩm.";
            $message_type = "success";
        } catch (Exception $e) {
            $db->rollBack();
            $message = "Lỗi hệ thống: " . $e->getMessage();
            $message_type = "error";
        }
    } else {
        $message = "Vui lòng chọn sản phẩm và nhập số lượng hợp lệ!";
        $message_type = "error";
    }
}

// ==========================================
// THÊM ĐIỀU KIỆN WHERE IsDeleted = 0 VÀO ĐÂY
// ==========================================
$stmt = $db->query("SELECT ProductID, ProductName, ImageUrl, StockQuantity FROM `Product` WHERE IsDeleted = 0 ORDER BY StockQuantity ASC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Phân loại mảng
$out_of_stock = [];
$low_stock = [];
$safe_stock = [];

foreach ($products as $p) {
    if ($p['StockQuantity'] == 0) {
        $out_of_stock[] = $p;
    } elseif ($p['StockQuantity'] <= 10) {
        $low_stock[] = $p;
    } else {
        $safe_stock[] = $p;
    }
}

include '../includes/header_admin.php';
?>

<style>
    .inventory-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 20px; 
    }
    .inventory-header h2 { 
        color: #1b4332; 
        margin: 0; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
    }
    .btn-history { 
        background: #5bc0de; 
        color: white; 
        padding: 10px 20px; 
        border-radius: 6px; 
        text-decoration: none; 
        font-weight: bold; 
    }
    .btn-history:hover { 
        background: #31b0d5; 
    }
    
    .inventory-container { 
        display: flex; 
        gap: 20px; 
        align-items: flex-start; 
    }
    
    .list-section { 
        flex: 2; 
        background: #fff; 
        padding: 20px; 
        border-radius: 8px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
    }
    .list-title { 
        font-size: 16px; 
        font-weight: bold; 
        border-bottom: 1px dashed #ddd; 
        padding-bottom: 10px; 
        margin-bottom: 15px; 
        display: flex; 
        justify-content: space-between; 
        color: #555;
    }
    .list-title span { 
        font-size: 12px; 
        color: #999; 
        font-weight: normal; 
        font-style: italic; 
    }
    
    .accordion-header { 
        padding: 12px 15px; 
        border-radius: 6px; 
        font-weight: bold; 
        cursor: pointer; 
        display: flex; 
        justify-content: space-between; 
        margin-bottom: 5px; 
    }
    .acc-red { 
        background: #f8d7da; 
        color: #721c24; 
    }
    .acc-yellow { 
        background: #fff3cd; 
        color: #856404; 
    }
    .acc-green { 
        background: #d4edda; 
        color: #155724; 
    }
    
    .accordion-content { 
        display: none; 
        padding: 0 10px; 
        margin-bottom: 15px; 
    }
    .accordion-content.active { 
        display: block; 
    }
    
    .product-item { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 10px; 
        border-bottom: 1px solid #f1f1f1; 
        cursor: pointer; 
        transition: 0.2s; 
    }
    .product-item:hover { 
        background: #f9f9f9; 
    }
    .product-info { 
        display: flex; 
        align-items: center; 
        gap: 15px; 
    }
    .product-info img { 
        width: 40px; 
        height: 40px; 
        object-fit: cover; 
        border-radius: 4px; 
        border: 1px solid #ddd; 
    }
    .product-name { 
        font-weight: 600; 
        color: #333; 
        font-size: 14px; 
    }
    .product-qty { 
        font-weight: bold; 
        color: #856404; 
    }
    
    .form-section { 
        flex: 1; 
        background: #fff; 
        padding: 25px; 
        border-radius: 8px; 
        border: 1px solid #f5c6cb; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        position: sticky; 
        top: 20px; 
        border-color: #ffeeba; 
    }
    .form-title { 
        color: #b8860b; 
        font-size: 18px; 
        font-weight: bold; 
        text-align: center; 
        margin-bottom: 25px; 
    }
    .form-group { 
        margin-bottom: 20px; 
    }
    .form-group label { 
        display: block; 
        font-weight: 600; 
        margin-bottom: 8px; 
        font-size: 14px; 
        color: #555;
    }
    
    .selected-box { 
        background: #f4f4f4; 
        padding: 12px; 
        border-radius: 6px; 
        border: 1px dashed #ccc; 
        font-size: 14px; 
        color: #777; 
    }
    .form-control { 
        width: 100%; 
        padding: 12px; 
        border: 1px solid #ddd; 
        border-radius: 6px; 
        font-size: 15px; 
        box-sizing: border-box; }
    .btn-submit { 
        width: 100%; 
        background: #6cbf73; 
        color: white; 
        border: none; 
        padding: 14px; 
        border-radius: 6px; 
        font-size: 15px; 
        font-weight: bold; 
        cursor: pointer; 
        transition: 0.3s; 
    }
    .btn-submit:hover { 
        background: #5a9d5f; 
    }

    .alert { 
        padding: 15px; 
        border-radius: 6px; 
        margin-bottom: 20px; 
        font-weight: bold; 
    }
    .alert-success { 
        background: #d4edda; 
        color: #155724; 
    }
    .alert-error { 
        background: #f8d7da; 
        color: #721c24; 
        }
</style>

<div class="inventory-header">
    <h2><i class="fa-solid fa-boxes-stacked"></i> KIỂM KHO & NHẬP KHO</h2>
    <a href="history.php" class="btn-history"><i class="fa-solid fa-clock-rotate-left"></i> Xem lịch sử nhập kho</a>
</div>

<?php if ($message != ''): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<div class="inventory-container">
    <div class="list-section">
        <div class="list-title">
            <span><i class="fa-solid fa-list-ul"></i> DANH SÁCH CẦN KIỂM KHO</span>
            <span>* Click vào sản phẩm để nhập kho</span>
        </div>

        <div class="accordion-header acc-red" onclick="toggleAcc('acc-out')">
            <span><i class="fa-solid fa-circle-xmark"></i> Hết hàng (0) (<?php echo count($out_of_stock); ?>)</span>
            <i class="fa-solid fa-chevron-down"></i>
        </div>
        <div class="accordion-content active" id="acc-out">
            <?php foreach ($out_of_stock as $p): $fullPath = "../.." . $p['ImageUrl']; ?>
                <div class="product-item" onclick="selectProduct(<?php echo $p['ProductID']; ?>, '<?php echo addslashes($p['ProductName']); ?>')">
                    <div class="product-info">
                        <img src="<?php echo $fullPath; ?>" onerror="this.src='../../Images/default.jpg'">
                        <span class="product-name"><?php echo htmlspecialchars($p['ProductName']); ?></span>
                    </div>
                    <span class="product-qty" style="color: #721c24;">0</span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="accordion-header acc-yellow" onclick="toggleAcc('acc-low')">
            <span><i class="fa-solid fa-triangle-exclamation"></i> Sắp hết hàng (1-10) (<?php echo count($low_stock); ?>)</span>
            <i class="fa-solid fa-chevron-down"></i>
        </div>
        <div class="accordion-content active" id="acc-low">
            <?php foreach ($low_stock as $p): $fullPath = "../.." . $p['ImageUrl']; ?>
                <div class="product-item" onclick="selectProduct(<?php echo $p['ProductID']; ?>, '<?php echo addslashes($p['ProductName']); ?>')">
                    <div class="product-info">
                        <img src="<?php echo $fullPath; ?>" onerror="this.src='../../Images/default.jpg'">
                        <span class="product-name"><?php echo htmlspecialchars($p['ProductName']); ?></span>
                    </div>
                    <span class="product-qty"><?php echo $p['StockQuantity']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="accordion-header acc-green" onclick="toggleAcc('acc-safe')">
            <span><i class="fa-solid fa-circle-check"></i> Tồn kho an toàn (>10) (<?php echo count($safe_stock); ?>)</span>
            <i class="fa-solid fa-chevron-down"></i>
        </div>
        <div class="accordion-content" id="acc-safe">
            <?php foreach ($safe_stock as $p): $fullPath = "../.." . $p['ImageUrl']; ?>
                <div class="product-item" onclick="selectProduct(<?php echo $p['ProductID']; ?>, '<?php echo addslashes($p['ProductName']); ?>')">
                    <div class="product-info">
                        <img src="<?php echo $fullPath; ?>" onerror="this.src='../../Images/default.jpg'">
                        <span class="product-name"><?php echo htmlspecialchars($p['ProductName']); ?></span>
                    </div>
                    <span class="product-qty" style="color: #155724;"><?php echo $p['StockQuantity']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-section">
        <div class="form-title">
            <i class="fa-solid fa-download"></i> THAO TÁC NHẬP KHO
        </div>
        
        <form action="" method="POST">
            <input type="hidden" name="product_id" id="target_product_id" value="">
            
            <div class="form-group">
                <label>1. Sản phẩm đang chọn:</label>
                <div class="selected-box" id="display_name">
                    <i class="fa-regular fa-hand-pointer" style="color: #f0ad4e;"></i> Bấm chọn sản phẩm bên trái...
                </div>
            </div>

            <div class="form-group">
                <label>2. Số lượng nhập thêm:</label>
                <input type="number" name="add_qty" class="form-control" placeholder="Nhập số lượng..." min="1" required>
            </div>

            <button type="submit" name="import_stock" class="btn-submit" onclick="return checkSelection();">XÁC NHẬN NHẬP KHO</button>
        </form>
    </div>
</div>

<script>
    function toggleAcc(id) {
        let el = document.getElementById(id);
        if (el.style.display === "none" || el.style.display === "") {
            el.style.display = "block";
        } else {
            el.style.display = "none";
        }
    }

    function selectProduct(id, name) {
        document.getElementById('target_product_id').value = id;
        document.getElementById('display_name').innerHTML = '<span style="color:#2d5a27; font-weight:bold;">' + name + '</span>';
        document.getElementById('display_name').style.background = '#e8f5e9';
        document.getElementById('display_name').style.border = '1px solid #c3e6cb';
    }

    function checkSelection() {
        if(document.getElementById('target_product_id').value == '') {
            alert("Vui lòng click chọn 1 sản phẩm ở danh sách bên trái trước!");
            return false;
        }
        return confirm("Xác nhận nhập thêm số lượng cho sản phẩm này?");
    }
</script>

<?php include '../includes/footer_admin.php'; ?>