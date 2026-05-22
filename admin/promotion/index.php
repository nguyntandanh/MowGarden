<?php
session_start();
if (!isset($_SESSION['account_id']) || $_SESSION['account_id'] != 1) {
    header("Location: ../../client/index.php");
    exit();
}

require_once '../../config/Database.php';
$db = (new Database())->getConnection();

// ==========================================
// 1. XỬ LÝ LƯU THIẾT LẬP KHUYẾN MÃI (POST)
// ==========================================
$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_promo'])) {
    $p_id = $_POST['product_id'];
    $d_price = !empty($_POST['discount_price']) ? $_POST['discount_price'] : NULL;
    $start = !empty($_POST['start_date']) ? $_POST['start_date'] : NULL;
    $end = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;

    $query = "UPDATE product SET DiscountPrice = ?, PromoStart = ?, PromoEnd = ? WHERE ProductID = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$d_price, $start, $end, $p_id])) {
        $msg = "Cập nhật khuyến mãi thành công!";
    }
}

// ==========================================
// 2. XỬ LÝ SẮP XẾP VÀ TRUY VẤN (GET)
// ==========================================
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$order_by = "p.ProductID DESC"; // Mặc định

if ($sort == 'desc') {
    // Sắp xếp giảm nhiều nhất (Tính toán % ngay trong SQL)
    $order_by = "((p.Price - p.DiscountPrice) / p.Price) DESC";
} elseif ($sort == 'asc') {
    // Sắp xếp giảm ít nhất
    $order_by = "((p.Price - p.DiscountPrice) / p.Price) ASC";
}

// THÊM ĐIỀU KIỆN WHERE p.IsDeleted = 0 ĐỂ ẨN CÁC SẢN PHẨM ĐÃ XÓA MỀM
$query = "SELECT ProductID, ProductName, Price, ImageUrl, DiscountPrice, PromoStart, PromoEnd 
          FROM product p 
          WHERE p.IsDeleted = 0 
          ORDER BY $order_by";
$stmt = $db->query($query);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header_admin.php';
?>

<style>
    .promo-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 20px; 
    }
    .promo-header h2 { 
        color: #1b4332; 
        margin: 0; 
        text-transform: uppercase; 
        font-weight: 800; 
    }
    
    .sort-box { 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        font-weight: bold; 
        color: #555; 
    }
    .filter-select { 
        padding: 8px 12px; 
        border: 1px solid #ddd; 
        border-radius: 4px; 
        outline: none; 
        cursor: pointer; 
    }


    table { 
        width: 100%; 
        border-collapse: collapse; 
        background: #fff; 
        border-radius: 8px; 
        overflow: hidden; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
    }
    th { 
        background: #f8f9fa; 
        padding: 15px; 
        text-align: left; 
        border-bottom: 2px solid #eee; 
        color: #333; 
    }
    td { 
        padding: 15px; 
        border-bottom: 1px solid #eee; 
        vertical-align: middle; 
    }

    .product-cell { 
        display: flex; 
        align-items: center; 
        gap: 15px; 
    }
    .product-cell img { 
        width: 50px; 
        height: 50px; 
        object-fit: cover; 
        border-radius: 6px; 
        border: 1px solid #ddd; 
    }
    .product-name { 
        font-weight: 600; 
        color: #333; 
    }

    .price-old { 
        color: #888; 
        text-decoration: line-through; 
        font-size: 13px; 
    }
    .price-new { 
        color: #d9534f; 
        font-weight: bold; 
        font-size: 15px; 
        display: block; 
    }
    
    .percent-badge { 
        background: #fdeaea; 
        color: #d9534f; 
        padding: 4px 8px; 
        border-radius: 4px; 
        font-weight: bold; 
        border: 1px solid #f5c6cb; 
    }
    
    .status-active { 
        background: #d4edda; 
        color: #155724; 
        padding: 5px 10px; 
        border-radius: 4px; 
        font-size: 12px; 
        font-weight: bold; 
    }
    .status-normal { 
        background: #e9ecef; 
        color: #6c757d; 
        padding: 5px 10px; 
        border-radius: 4px; 
        font-size: 12px; 
        font-weight: bold; 
    }

    .btn-setup { 
        background: #007bff; 
        color: white; 
        border: none; 
        padding: 8px 12px; 
        border-radius: 4px; 
        cursor: pointer; 
        font-weight: bold; 
        transition: 0.2s; 
        display: flex; 
        align-items: center; 
        gap: 5px; 
    }
    .btn-setup:hover { 
        background: #0056b3; 
    }

    .modal { 
        display: none; 
        position: fixed; 
        z-index: 1000; 
        left: 0; 
        top: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(0,0,0,0.5); 
        align-items: center; 
        justify-content: center; 
    }
    .modal-content { 
        background: white; 
        width: 450px; 
        border-radius: 12px; 
        padding: 30px; 
        position: relative; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.3); 
    }
    .close-modal { 
        position: absolute; 
        top: 15px; 
        right: 20px; 
        font-size: 24px; 
        cursor: pointer; 
        color: #888; 
    }
    
    .modal-title { 
        font-size: 20px; 
        font-weight: bold; 
        color: #333; 
        text-align: center; 
        margin-bottom: 10px; 
    }
    .modal-subtitle { 
        text-align: center; 
        color: #666; 
        margin-bottom: 25px; 
        font-size: 14px; 
    }

    .form-group { 
        margin-bottom: 20px; 
        position: relative; 
    }
    .form-group label { 
        display: block; 
        font-weight: bold; 
        color: #d9534f; 
        margin-bottom: 8px; 
        font-size: 14px; 
    }
    .form-input { 
        width: 100%; 
        padding: 12px; 
        border: 1px solid #ddd; 
        border-radius: 6px; 
        box-sizing: border-box; 
        font-size: 15px; 
    }
    
    .realtime-percent { 
        position: absolute; 
        right: -60px; 
        top: 35px; 
        background: #fdeaea; 
        color: #d9534f; 
        padding: 10px; 
        border-radius: 4px; 
        font-weight: bold; 
        border: 1px solid #f5c6cb; 
    }
    
    .modal-footer { 
        display: flex; 
        justify-content: flex-end; 
        gap: 10px; 
        margin-top: 10px; 
    }
    .btn-close { 
        background: #eee; 
        color: #333; 
        border: none; 
        padding: 10px 20px; 
        border-radius: 4px; 
        cursor: pointer; 
        font-weight: bold; 
    }
    .btn-save { 
        background: #d9534f; 
        color: white; 
        border: none; 
        padding: 10px 25px; 
        border-radius: 4px; 
        cursor: pointer; 
        font-weight: bold; 
        }
</style>

<div class="promo-header">
    <h2><i class="fa-solid fa-tags"></i> KHUYẾN MÃI</h2>
    <div class="sort-box">
        Sắp xếp theo % giảm:
        <select class="filter-select" onchange="location.href='index.php?sort=' + this.value">
            <option value="default" <?php if($sort=='default') echo 'selected'; ?>>Mặc định</option>
            <option value="desc" <?php if($sort=='desc') echo 'selected'; ?>>Giảm nhiều nhất</option>
            <option value="asc" <?php if($sort=='asc') echo 'selected'; ?>>Giảm ít nhất</option>
        </select>
    </div>
</div>

<?php if($msg != ""): ?>
    <script>alert("<?php echo $msg; ?>");</script>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Sản phẩm</th>
            <th>Giá gốc</th>
            <th>Giá khuyến mãi</th>
            <th>% Giảm</th>
            <th>Thời gian áp dụng</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($products as $p): 
            $fullPath = "../.." . $p['ImageUrl'];
            $today = date('Y-m-d');
            $is_active = (!empty($p['DiscountPrice']) && $today >= $p['PromoStart'] && $today <= $p['PromoEnd']);
            $percent = 0;
            if(!empty($p['DiscountPrice']) && $p['Price'] > 0) {
                $percent = round((($p['Price'] - $p['DiscountPrice']) / $p['Price']) * 100);
            }
        ?>
        <tr>
            <td>
                <div class="product-cell">
                    <img src="<?php echo $fullPath; ?>" onerror="this.src='../../Images/default.jpg'">
                    <span class="product-name"><?php echo htmlspecialchars($p['ProductName']); ?></span>
                </div>
            </td>
            <td><span class="<?php echo $is_active ? 'price-old' : ''; ?>"><?php echo number_format($p['Price'], 0, ',', '.'); ?> đ</span></td>
            <td>
                <?php if($p['DiscountPrice'] > 0): ?>
                    <span class="price-new"><?php echo number_format($p['DiscountPrice'], 0, ',', '.'); ?> đ</span>
                <?php else: ?>
                    ---
                <?php endif; ?>
            </td>
            <td>
                <?php if($percent > 0): ?>
                    <span class="percent-badge">-<?php echo $percent; ?>%</span>
                <?php else: ?>
                    0%
                <?php endif; ?>
            </td>
            <td>
                <?php if($p['PromoStart']): ?>
                    <span style="font-size: 13px; color: #666;"><?php echo date('d/m/Y', strtotime($p['PromoStart'])); ?> - <?php echo date('d/m/Y', strtotime($p['PromoEnd'])); ?></span>
                <?php else: ?>
                    Chưa thiết lập
                <?php endif; ?>
            </td>
            <td>
                <?php if($is_active): ?>
                    <span class="status-active">Đang giảm giá</span>
                <?php else: ?>
                    <span class="status-normal">Gói thường</span>
                <?php endif; ?>
            </td>
            <td>
                <button class="btn-setup" onclick="openPromoModal(<?php echo $p['ProductID']; ?>, '<?php echo addslashes($p['ProductName']); ?>', <?php echo $p['Price']; ?>, '<?php echo $p['DiscountPrice']; ?>', '<?php echo $p['PromoStart']; ?>', '<?php echo $p['PromoEnd']; ?>')">
                    <i class="fa-solid fa-gear"></i> Thiết lập
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        
        <?php if(empty($products)): ?>
        <tr><td colspan="7" style="text-align: center; color: #888; padding: 20px;">Không có sản phẩm nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div id="promoModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div class="modal-title">
            <i class="fa-solid fa-bolt"></i> Thiết lập Khuyến mãi
        </div>
        <div class="modal-subtitle" id="modal_product_info">Tên sản phẩm - Giá gốc: 0 VNĐ</div>

        <form action="" method="POST">
            <input type="hidden" name="product_id" id="m_product_id">
            
            <div class="form-group">
                <label>Giá khuyến mãi mới:</label>
                <input type="number" name="discount_price" id="m_discount_price" class="form-input" placeholder="Nhập giá giảm..." oninput="calculatePercent()">
                <div id="m_percent_badge" class="realtime-percent" style="display:none;">-0%</div>
            </div>

            <div class="form-group">
                <label style="color: #333;">Ngày bắt đầu:</label>
                <input type="date" name="start_date" id="m_start_date" class="form-input">
            </div>

            <div class="form-group">
                <label style="color: #333;">Ngày kết thúc:</label>
                <input type="date" name="end_date" id="m_end_date" class="form-input">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-close" onclick="closeModal()">Đóng</button>
                <button type="submit" name="save_promo" class="btn-save">LƯU</button>
            </div>
        </form>
    </div>
</div>

<script>
    let originalPrice = 0;

    function openPromoModal(id, name, price, dPrice, start, end) {
        originalPrice = price;
        document.getElementById('m_product_id').value = id;
        document.getElementById('modal_product_info').innerHTML = `<strong>${name}</strong><br>Giá gốc: ${new Intl.NumberFormat('vi-VN').format(price)} VNĐ`;
        document.getElementById('m_discount_price').value = dPrice > 0 ? dPrice : "";
        document.getElementById('m_start_date').value = start;
        document.getElementById('m_end_date').value = end;
        
        calculatePercent();
        document.getElementById('promoModal').style.display = 'flex';
    }

    function calculatePercent() {
        let dPrice = document.getElementById('m_discount_price').value;
        let badge = document.getElementById('m_percent_badge');
        
        if (dPrice > 0 && dPrice < originalPrice) {
            let pct = Math.round(((originalPrice - dPrice) / originalPrice) * 100);
            badge.innerHTML = `-${pct}%`;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }

    function closeModal() {
        document.getElementById('promoModal').style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('promoModal')) closeModal();
    }
</script>

<?php include '../includes/footer_admin.php'; ?>