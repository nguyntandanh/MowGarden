<?php
session_start();
require_once '../config/Database.php';
require_once '../classes/Product.php';
require_once '../classes/Category.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {
    if (isset($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $product_id => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        }
    }
    header("Location: cart.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    $remove_id = (int)$_GET['id'];
    if (isset($_SESSION['cart'][$remove_id])) {
        unset($_SESSION['cart'][$remove_id]);
    }
    header("Location: cart.php");
    exit();
}

$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $quantity) {
        $cart_count += $quantity;
    }
}

include_once 'includes/header.php';
?>

<style>
    .cart-container { 
        max-width: 1200px; 
        margin: 50px auto 80px; 
        padding: 0 20px; 
        display: flex; 
        gap: 30px; 
        flex-wrap: wrap; 
    }
    
    .cart-title { 
        width: 100%; 
        font-size: 28px; 
        font-weight: 800; 
        color: #2d5a27; 
        text-transform: uppercase; 
        margin-bottom: 20px; 
        border-bottom: 2px solid #eee; 
        padding-bottom: 15px; 
    }
    
    .cart-items { 
        flex: 2; 
        min-width: 600px; 
        background: #fff; 
        border-radius: 8px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        overflow: hidden; 
        border: 1px solid #eee; 
    }
    .cart-table { 
        width: 100%; 
        border-collapse: collapse; 
    }
    .cart-table th { 
        background: #f8f9fa; 
        padding: 15px; 
        text-align: left; 
        font-size: 14px; 
        color: #555; 
        font-weight: 600; 
        border-bottom: 1px solid #ddd; 
    }
    .cart-table td { 
        padding: 20px 15px; 
        vertical-align: middle; 
        border-bottom: 1px solid #f1f1f1; 
    }
    
    .product-col { 
        display: flex; 
        align-items: center; 
        gap: 15px; 
    }
    .product-col img { 
        width: 80px; 
        height: 80px; 
        object-fit: cover; 
        border-radius: 8px; 
        border: 1px solid #eee; 
    }
    .product-col .info h4 { 
        margin: 0 0 5px 0; 
        font-size: 15px; 
        font-weight: 600; 
        color: #333; 
        display: -webkit-box; 
        -webkit-line-clamp: 2; 
        -webkit-box-orient: vertical; 
        overflow: hidden; 
    }
    .product-col .info a { 
        text-decoration: none; 
        color: inherit; 
        transition: 0.3s; 
    }
    .product-col .info a:hover { 
        color: #2d5a27; 
    }
    
    .price-col { 
        font-weight: 600; 
        color: #555; 
        white-space: nowrap; 
    }
    .total-col { 
        font-weight: 800; 
        color: #2d5a27; 
        font-size: 16px; 
        white-space: nowrap; 
    }
    
    .qty-input { 
        width: 60px; 
        height: 35px; 
        border: 1px solid #ddd; 
        border-radius: 4px; 
        text-align: center; 
        font-size: 15px; 
        font-weight: 600; 
        outline: none; 
    }
    .qty-input:focus { 
        border-color: #2d5a27; 
    }
    
    .btn-remove { 
        color: #dc3545; 
        background: #ffebee; 
        border: none; 
        width: 35px; 
        height: 35px; 
        border-radius: 4px; 
        cursor: pointer; 
        transition: 0.3s; 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        text-decoration: none; 
    }
    .btn-remove:hover { 
        background: #dc3545; 
        color: white; 
    }
    
    .cart-actions { 
        padding: 20px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        background: #fafafa; 
    }
    .btn-continue { 
        text-decoration: none; 
        color: #555; 
        font-weight: 600; 
        font-size: 14px; 
        transition: 0.3s; 
    }
    .btn-continue:hover { 
        color: #2d5a27; 
    }
    .btn-update { 
        background: transparent; 
        color: #2d5a27; 
        border: 2px solid #2d5a27; 
        padding: 10px 20px; 
        border-radius: 25px; 
        font-weight: bold; 
        cursor: pointer; 
        transition: 0.3s; 
    }
    .btn-update:hover { 
        background: #2d5a27; 
        color: white; 
    }

    .cart-summary { 
        flex: 1; 
        min-width: 320px; 
        background: #fff; 
        padding: 25px; 
        border-radius: 8px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        border: 1px solid #eee; 
        height: fit-content; 
        position: sticky; 
        top: 100px; 
    }
    .summary-title { 
        font-size: 18px; 
        font-weight: 800; 
        margin-bottom: 20px; 
        color: #333; 
    }
    
    .summary-row { 
        display: flex; 
        justify-content: space-between; 
        margin-bottom: 15px; 
        font-size: 15px; 
        color: #555; 
    }
    .summary-total { 
        display: flex; 
        justify-content: space-between; 
        margin-top: 20px; 
        padding-top: 20px; 
        border-top: 1px solid #ddd; 
        font-size: 20px; 
        font-weight: 800; 
        color: #2d5a27; 
    }
    
    .btn-checkout { 
        display: block; 
        width: 100%; 
        background: #2d5a27; 
        color: white; 
        text-align: center; 
        padding: 15px 0; 
        border-radius: 25px; 
        font-size: 16px; 
        font-weight: bold; 
        text-decoration: none; 
        margin-top: 25px; 
        transition: 0.3s; 
        border: none; 
        cursor: pointer; 
    }
    .btn-checkout:hover { 
        background: #1f401b; 
    }

    .empty-cart { 
        width: 100%; 
        text-align: center; 
        padding: 50px 20px; 
        background: #fff; 
        border-radius: 8px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
    }
    .empty-cart i { 
        font-size: 60px; 
        color: #ddd; 
        margin-bottom: 20px; 
    }
    .empty-cart h3 { 
        font-size: 20px; 
        color: #333; 
        margin-bottom: 20px; 
    }
    .empty-cart .btn-shop { 
        display: inline-block; 
        background: #2d5a27; 
        color: white; 
        padding: 12px 30px; 
        border-radius: 25px; 
        text-decoration: none; 
        font-weight: bold; 
        transition: 0.3s; 
    }
    .empty-cart .btn-shop:hover { 
        background: #1f401b; 
    }

    @media (max-width: 768px) {
        .cart-container { 
            flex-direction: column; 
        }
        .cart-items, .cart-summary { 
            min-width: 100%; 
        }
        .cart-table thead { 
            display: none; 
        }
        .cart-table, .cart-table tbody, .cart-table tr, .cart-table td { 
            display: block; 
            width: 100%; 
        }
        .cart-table tr { 
            margin-bottom: 15px; 
            border-bottom: 2px solid #eee; 
            padding-bottom: 15px; 
        }
        .cart-table td { 
            text-align: right; 
            padding: 10px 15px; 
            position: relative; 
            border-bottom: none; 
        }
        .cart-table td::before { 
            content: attr(data-label); 
            position: absolute; 
            left: 15px; 
            width: 45%; 
            text-align: left; 
            font-weight: 600; 
            color: #555; 
        }
        .product-col { 
            justify-content: flex-end; 
            text-align: right; 
        }
        .product-col img { 
            display: none; 
        }
    }
</style>

<div class="cart-container">
    <div class="cart-title">GIỎ HÀNG CỦA BẠN</div>
    
    <?php if (empty($_SESSION['cart'])): ?>
        <div class="empty-cart">
            <i class="fa-solid fa-basket-shopping"></i>
            <h3>Giỏ hàng của bạn đang trống</h3>
            <p style="color: #777; margin-bottom: 25px;">Hãy khám phá các loại cây cảnh tuyệt đẹp tại MowGarden nhé!</p>
            <a href="index.php" class="btn-shop">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <?php
        // Lấy danh sách ID từ Session
        $ids = array_keys($_SESSION['cart']);
        $stmt_cart = $product->getProductsByIDs($ids);
        
        $total_amount = 0;
        ?>
        
        <form action="cart.php" method="POST" class="cart-items">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    while ($row = $stmt_cart->fetch(PDO::FETCH_ASSOC)) {
                        $id = $row['ProductID'];
                        $name = $row['ProductName'];
                        $price = $row['Price'];
                        $quantity = $_SESSION['cart'][$id];
                        $subtotal = $price * $quantity;
                        $total_amount += $subtotal;
                        
                        $image_url = $row['ImageUrl']; 
                        $clean_path = ltrim($image_url, '/'); 
                        $img_src = !empty($image_url) ? "../" . $clean_path : "../Images/default-plant.jpg";
                    ?>
                    <tr>
                        <td data-label="Sản phẩm">
                            <div class="product-col">
                                <img src="<?php echo $img_src; ?>" alt="<?php echo $name; ?>">
                                <div class="info">
                                    <h4><a href="detail.php?id=<?php echo $id; ?>"><?php echo $name; ?></a></h4>
                                </div>
                            </div>
                        </td>
                        <td data-label="Đơn giá" class="price-col"><?php echo number_format($price, 0, ',', '.'); ?> đ</td>
                        <td data-label="Số lượng">
                            <input type="number" name="qty[<?php echo $id; ?>]" value="<?php echo $quantity; ?>" min="1" class="qty-input">
                        </td>
                        <td data-label="Thành tiền" class="total-col"><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</td>
                        <td data-label="Xóa">
                            <a href="cart.php?action=remove&id=<?php echo $id; ?>" class="btn-remove" title="Xóa sản phẩm" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?');">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            
            <div class="cart-actions">
                <a href="index.php" class="btn-continue"><i class="fa-solid fa-arrow-left"></i> Tiếp tục mua sắm</a>
                <button type="submit" name="update_cart" class="btn-update"><i class="fa-solid fa-arrows-rotate"></i> Cập nhật giỏ hàng</button>
            </div>
        </form>
        
        <div class="cart-summary">
            <div class="summary-title">TÓM TẮT ĐƠN HÀNG</div>
            
            <div class="summary-row">
                <span>Tạm tính</span>
                <span><?php echo number_format($total_amount, 0, ',', '.'); ?> đ</span>
            </div>
            
            <div class="summary-row">
                <span>Phí giao hàng</span>
                <span>Miễn phí</span>
            </div>
            
            <div class="summary-total">
                <span>Tổng cộng</span>
                <span><?php echo number_format($total_amount, 0, ',', '.'); ?> đ</span>
            </div>
            
            <a href="checkout.php" class="btn-checkout">Tiến hành Thanh toán</a>
            
            <div style="text-align: center; margin-top: 15px; font-size: 13px; color: #777;">
                <i class="fa-solid fa-shield-halved"></i> Thanh toán bảo mật và an toàn
            </div>
        </div>
        
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>