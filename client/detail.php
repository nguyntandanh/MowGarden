<?php
session_start();
require_once '../config/Database.php';
require_once '../classes/Product.php';
require_once '../classes/Category.php';

// Kiểm tra xem có truyền ID sản phẩm lên URL không
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$product_id = (int)$_GET['id'];

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

// Lấy thông tin chi tiết của cây
$plant = $product->getProductById($product_id);

if (!$plant) {
    header("Location: index.php");
    exit();
}

$cart_count = 0;
if(isset($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $quantity) {
        $cart_count += $quantity;
    }
}

// Xử lý dữ liệu hiển thị
$name = $plant['ProductName'];
$price = number_format($plant['Price'], 0, ',', '.');
$category_name = $plant['CategoryName'] ? $plant['CategoryName'] : 'Chưa phân loại';
$description = $plant['Description'];
$stock = $plant['StockQuantity'];

$image_url = $plant['ImageUrl']; 
$clean_path = ltrim($image_url, '/'); 
$img_src = !empty($image_url) ? "../" . $clean_path : "../Images/default-plant.jpg";

include_once 'includes/header.php';
?>

<style>
    /* ----- KHUNG CHI TIẾT SẢN PHẨM ----- */
    .detail-container { 
        max-width: 1200px; 
        margin: 40px auto 80px; 
        padding: 0 20px; 
        display: flex; 
        gap: 50px; 
        flex-wrap: wrap; 
    }
    
    /* Cột Trái: Hình ảnh */
    .detail-image { 
        flex: 1; 
        min-width: 400px; 
    }
    .detail-image img { 
        width: 100%; 
        height: auto; 
        max-height: 600px; 
        object-fit: cover; 
        border-radius: 12px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
        border: 1px solid #f1f1f1; 
    }
    
    /* Cột Phải: Thông tin */
    .detail-info { 
        flex: 1; 
        min-width: 400px; 
        display: flex; 
        flex-direction: column; 
    }
    
    .breadcrumb { 
        font-size: 13px; 
        color: #777; 
        margin-bottom: 15px; 
    }
    .breadcrumb a { 
        color: #2d5a27; 
        text-decoration: none; 
        font-weight: 600; 
    }
    .breadcrumb a:hover { 
        text-decoration: underline; 
    }
    
    .detail-info h1 { 
        font-size: 32px; 
        font-weight: 800; 
        color: #222; 
        margin: 0 0 15px 0; 
        line-height: 1.3; 
    }
    .detail-price { 
        font-size: 28px; 
        font-weight: 800; 
        color: #2d5a27; 
        margin: 0 0 25px 0; 
    }
    
    .detail-desc { 
        font-size: 15px; 
        line-height: 1.7; 
        color: #555; 
        margin-bottom: 30px; 
        text-align: justify; 
        padding-top: 20px; 
        border-top: 1px solid #eee; 
    }
    
    /* Tình trạng kho */
    .stock-status { 
        font-size: 14px; 
        font-weight: 600; 
        margin-bottom: 25px; 
        display: inline-block; 
        padding: 5px 12px; 
        border-radius: 20px; 
    }
    .in-stock { 
        background-color: #e8f5e9; 
        color: #2e7d32; 
    }
    .out-of-stock { 
        background-color: #ffebee; 
        color: #c62828; 
    }
    
    /* Khu vực Số lượng và Nút Mua (Đã thiết kế lại 1 nút Primary) */
    .action-area { 
        display: flex; 
        gap: 15px; 
        align-items: center; 
        margin-bottom: 30px; 
    }
    
    .qty-input { 
        width: 70px; 
        height: 45px; 
        border: 1px solid #ddd; 
        border-radius: 25px; 
        text-align: center; 
        font-size: 16px; 
        font-weight: 600; 
        color: #333; 
        outline: none; 
        transition: 0.3s; 
    }
    .qty-input:focus { 
        border-color: #2d5a27; 
    }
    
    .btn-add-cart { 
        height: 45px; 
        padding: 0 30px; 
        border-radius: 25px; 
        font-size: 14px; 
        font-weight: bold; 
        cursor: pointer; 
        transition: all 0.3s; 
        flex: 1; 
        border: none; 
        background: #2d5a27; 
        color: white; 
        border: 2px solid #2d5a27; 
    }
    .btn-add-cart:hover { 
        background: #1f401b; 
        border-color: #1f401b; 
    }
    
    @media (max-width: 768px) {
        .detail-container { 
            flex-direction: column; 
            gap: 30px; 
        }
        .detail-image, .detail-info { 
            min-width: 100%; 
        }
        .action-area { 
            flex-wrap: wrap; 
        }
        .btn-add-cart { 
            flex: unset; 
            width: 100%; 
        }
    }
</style>

<div class="detail-container">
    <div class="detail-image">
        <img src="<?php echo $img_src; ?>" alt="<?php echo $name; ?>">
    </div>
    
    <div class="detail-info">
        <div class="breadcrumb">
            <a href="index.php">Trang chủ</a> &nbsp; <i class="fa-solid fa-angle-right" style="font-size: 10px;"></i> &nbsp; 
            <a href="index.php?category_id=<?php echo $plant['CategoryID']; ?>#danh-muc"><?php echo $category_name; ?></a> &nbsp; <i class="fa-solid fa-angle-right" style="font-size: 10px;"></i> &nbsp; 
            <?php echo $name; ?>
        </div>
        
        <h1><?php echo $name; ?></h1>
        <div class="detail-price"><?php echo $price; ?> đ</div>
        
        <?php if($stock > 0): ?>
            <div class="stock-status in-stock"><i class="fa-solid fa-check-circle"></i> Tình trạng: Còn hàng (<?php echo $stock; ?>)</div>
        <?php else: ?>
            <div class="stock-status out-of-stock"><i class="fa-solid fa-times-circle"></i> Tình trạng: Hết hàng</div>
        <?php endif; ?>
        
        <div class="detail-desc">
            <?php echo nl2br($description); ?>
        </div>
        
        <div class="action-area">
            <input type="number" id="qty" class="qty-input" value="1" min="1" max="<?php echo $stock > 0 ? $stock : 1; ?>">
            <button class="btn-add-cart" id="btn-add" data-id="<?php echo $product_id; ?>">
                <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng
            </button>
        </div>
        
        <div style="font-size: 13px; color: #777; line-height: 1.8;">
            <div>
                <i class="fa-solid fa-truck-fast" style="color: #2d5a27; width: 20px;"></i> Giao hàng tận nơi toàn quốc
            </div>
            <div>
                <i class="fa-solid fa-leaf" style="color: #2d5a27; width: 20px;"></i> Đảm bảo cây khỏe đẹp khi nhận
            </div>
            <div>
                <i class="fa-solid fa-phone" style="color: #2d5a27; width: 20px;"></i> Hỗ trợ tư vấn chăm sóc trọn đời
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#btn-add').click(function() {
            var productId = $(this).data('id');
            var btn = $(this);

            $.ajax({
                url: 'add_to_cart.php',
                type: 'POST',
                data: { id: productId },
                success: function(response) {
                    $('#cart-count').text(response);
                    var originalText = btn.html();
                    btn.html('<i class="fa-solid fa-check"></i> Đã thêm vào giỏ').css({'background-color': '#ff9800', 'border-color': '#ff9800'});
                    setTimeout(function(){ 
                        btn.html(originalText).css({'background-color': '#2d5a27', 'border-color': '#2d5a27'}); 
                    }, 1500);
                }
            });
        });
    });
</script>

<?php include_once 'includes/footer.php'; ?>