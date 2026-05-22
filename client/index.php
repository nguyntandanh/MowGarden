<?php
session_start();
require_once '../config/Database.php';
require_once '../classes/Product.php';
require_once '../classes/Category.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);
$category = new Category($db);

$stmt_featured = $product->getAllProducts();

$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

$limit = 8; 
$start = ($current_page - 1) * $limit;

$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search_keyword)) {
    $stmt_cat_products = $product->searchProducts($search_keyword, $sort, $start, $limit);
    $total_products = $product->countSearchProducts($search_keyword);
    $section_title = "KẾT QUẢ TÌM KIẾM";
    $section_desc = "Hiển thị kết quả cho từ khóa: '<span style='color:#2d5a27; font-weight:bold;'>".htmlspecialchars($search_keyword)."</span>'";
    $url_params = "search=" . urlencode($search_keyword); 
} 
else {
    $stmt_categories = $category->getAllCategories();
    $categories_list = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
    $selected_cat_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : (isset($categories_list[0]) ? $categories_list[0]['CategoryID'] : 0);
    $stmt_cat_products = $product->getProductsByCategoryWithFilter($selected_cat_id, $sort, $start, $limit);
    $total_products = $product->countProductsByCategory($selected_cat_id);
    $section_title = "DANH MỤC SẢN PHẨM";
    $section_desc = "Tìm kiếm cây cảnh phù hợp theo sở thích cá nhân";
    $url_params = "category_id=" . $selected_cat_id; 
}

$total_pages = ceil($total_products / $limit);
$cart_count = 0;
if(isset($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $quantity) {
        $cart_count += $quantity;
    }
}

include_once 'includes/header.php';
?>

<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css"/>

<style>
    /* ----- BANNER VÀ TIÊU ĐỀ ----- */
    .banner-wrapper { 
        padding: 0 20px; 
        max-width: 1200px; 
        margin: 20px auto; 
    }
    .banner-slider { 
        border-radius: 12px; 
        overflow: hidden; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
    }
    .banner-slide { 
        position: relative; 
        height: 400px; 
    }
    .banner-slide img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
        filter: brightness(0.65); 
    }
    .hero-text { 
        position: absolute; 
        top: 50%; 
        left: 50%; 
        transform: translate(-50%, -50%); 
        text-align: center; 
        color: white; 
        width: 100%; 
        z-index: 2; 
    }
    .hero-text h1 { 
        font-size: 42px; 
        font-weight: 800; 
        margin: 0 0 10px 0; 
        text-transform: uppercase; 
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5); 
    }
    .hero-text p { 
        font-size: 16px; 
        margin: 0 0 20px 0; 
        text-shadow: 1px 1px 3px rgba(0,0,0,0.5); 
    }
    .btn-buy-now { 
        background: #2d5a27; 
        color: white; 
        border: 2px solid #2d5a27; 
        padding: 10px 25px; 
        border-radius: 25px; 
        font-size: 14px; 
        font-weight: bold; 
        cursor: pointer; 
        transition: 0.3s; 
    }
    .btn-buy-now:hover { 
        background: transparent; 
        color: white; 
        border-color: white; 
    }

    .section-title { 
        text-align: center; 
        margin: 50px 0 25px; 
    }
    .section-title h2 { 
        color: #2d5a27; 
        font-size: 26px; 
        text-transform: uppercase; 
        margin-bottom: 5px; 
        font-weight: 800; 
    }
    .section-title p { 
        color: #777; 
        font-size: 14px; 
        margin: 0; 
    }

    /* ----- MŨI TÊN SLIDER ----- */
    .slick-arrow { 
        position: absolute; 
        top: 50%; 
        transform: translateY(-50%); 
        z-index: 10; 
        width: 40px; 
        height: 40px; 
        background-color: rgba(45, 90, 39, 0.8) !important; 
        border-radius: 50%; 
        display: flex !important; 
        justify-content: center; 
        align-items: center; 
        color: white !important; 
        font-size: 20px; 
        font-weight: bold; 
        border: none; 
        cursor: pointer; 
        transition: 0.3s; 
    }
    .slick-arrow:hover { 
        background-color: rgba(45, 90, 39, 1) !important; 
    }
    .slick-arrow::before { 
        display: none; 
    }
    .slick-prev { 
        left: -15px; 
    } 
    .slick-next { 
        right: -15px; 
    }
    .banner-slider .slick-prev { 
        left: 20px; 
    }
    .banner-slider .slick-next { 
        right: 20px; 
    }
    .banner-slider .slick-dots { 
        bottom: 15px; 
    }
    .banner-slider .slick-dots li button:before { 
        color: white; 
        font-size: 12px; 
    }
    .banner-slider .slick-dots li.slick-active button:before { 
        color: #4CAF50; 
    }

    /* ----- THANH BỘ LỌC (DANH MỤC & GIÁ) ----- */
    .filter-bar { 
        max-width: 1200px; 
        margin: 0 auto 30px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 0 10px; 
        border-bottom: 1px solid #eee; 
        padding-bottom: 15px; 
    }
    .category-tabs { 
        display: flex; 
        gap: 10px; 
    }
    .tab-btn { 
        display: inline-block; 
        padding: 8px 20px; 
        background: #f1f1f1; 
        color: #555; 
        text-decoration: none; 
        border-radius: 20px; 
        font-size: 13px; 
        font-weight: 600; 
        transition: 0.3s; 
    }
    .tab-btn.active { 
        background: #2d5a27; 
        color: white; 
    }
    .sort-box .sort-btn { 
        padding: 8px 20px; 
        background: transparent; 
        color: #2d5a27; 
        border: 2px solid #2d5a27; 
        border-radius: 20px; 
        text-decoration: none; 
        font-size: 13px; 
        font-weight: bold; 
        transition: 0.3s; 
        display: inline-block; 
    }
    .sort-box .sort-btn.active { 
        background: #ff9800; 
        color: white; 
        border-color: #ff9800; 
    }

    /* ----- THIẾT KẾ CARD & GRID ----- */
    .product-wrapper { 
        max-width: 1200px; 
        margin: 0 auto; 
        padding: 0 10px; 
        position: relative; 
    }
    .card-spacing { 
        padding: 10px; 
    }
    .product-grid { 
        display: grid; 
        grid-template-columns: repeat(4, 1fr); 
        gap: 20px; 
    } 
    
    .card { 
        background: white; 
        padding: 15px; 
        border-radius: 8px; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        text-align: center; 
        border: 1px solid #eee; 
        transition: 0.3s; 
    }
    .card:hover { 
        box-shadow: 0 8px 16px rgba(0,0,0,0.1); 
        border-color: #2d5a27; 
    }
    
    /* LINK CHI TIẾT SẢN PHẨM */
    .product-link { 
        text-decoration: none; 
        color: inherit; 
        display: block; 
        width: 100%; 
        transition: 0.3s; 
    }
    .product-link:hover h3 { 
        color: #2d5a27; 
    }
    
    .card-img-top { 
        width: 100%; 
        height: 220px; 
        object-fit: cover; 
        border-radius: 8px; 
        margin-bottom: 15px; 
        transition: 0.3s; 
    }
    .product-link:hover .card-img-top { 
        transform: scale(1.02); 
    }
    
    .card h3 { 
        font-size: 14px; 
        font-weight: 600; 
        color: #333; 
        margin: 0 0 10px 0; 
        line-height: 1.4; 
        height: 40px; 
        display: -webkit-box; 
        -webkit-line-clamp: 2; 
        -webkit-box-orient: vertical; 
        overflow: hidden; 
        text-overflow: ellipsis; 
        transition: 0.3s; 
    }
    .price { 
        color: #2d5a27; 
        font-weight: 800; 
        font-size: 16px; 
        margin-bottom: 15px; 
        white-space: nowrap; 
    }
    
    button.add-to-cart { 
        background: #2d5a27; 
        color: white; border: none; 
        padding: 10px 20px; 
        cursor: pointer; 
        border-radius: 25px; 
        width: 90%; 
        font-weight: 600; 
        font-size: 13px; 
        transition: background 0.3s; 
    }
    button.add-to-cart:hover { 
        background: #1f401b; 
    }

    /* ----- PHÂN TRANG ----- */
    .pagination { 
        display: flex; 
        justify-content: center; 
        gap: 8px; 
        margin-top: 40px; 
    }
    .pagination a { 
        padding: 8px 16px; 
        border: 1px solid #ddd; 
        color: #333; 
        text-decoration: none; 
        border-radius: 4px; 
        font-size: 14px; 
        font-weight: 600; 
        transition: 0.3s; 
    }
    .pagination a.active { 
        background: #2d5a27; 
        color: white; 
        border-color: #2d5a27; 
    }
    .pagination a:hover:not(.active) { 
        background: #f1f1f1; 
        }
</style>

<div class="banner-wrapper">
    <div class="banner-slider">
        <div class="banner-slide">
            <img src="../Images/banner1.webp" alt="Banner 1">
            <div class="hero-text">
                <h1>KHÔNG GIAN XANH</h1>
                <p>Mang thiên nhiên vào ngôi nhà của bạn</p>
                <button class="btn-buy-now">Mua Ngay</button>
            </div>
        </div>
        <div class="banner-slide">
            <img src="../Images/banner2.avif" alt="Banner 2">
            <div class="hero-text">
                <h1>SỨC SỐNG MỚI</h1>
                <p>Thanh lọc không khí, nâng tầm không gian</p>
                <button class="btn-buy-now">Mua Ngay</button>
            </div>
        </div>
        <div class="banner-slide">
            <img src="../Images/banner3.jpg" alt="Banner 3">
            <div class="hero-text">
                <h1>QUÀ TẶNG Ý NGHĨA</h1>
                <p>Trao gửi yêu thương qua những mầm xanh</p>
                <button class="btn-buy-now">Mua Ngay</button>
            </div>
        </div>
    </div>
</div>

<div class="section-title">
    <h2>SẢN PHẨM NỔI BẬT</h2>
    <p>Bộ sưu tập những cây cảnh được săn đón nhiều nhất</p>
</div>

<div class="product-wrapper" style="padding: 0 30px;">
    <div class="product-slider">
        <?php
        while ($row = $stmt_featured->fetch(PDO::FETCH_ASSOC)) {
            $id = $row['ProductID']; $name = $row['ProductName']; $price = number_format($row['Price'], 0, ',', '.');
            $image_url = $row['ImageUrl']; $clean_path = ltrim($image_url, '/');
            $img_src = !empty($image_url) ? "../" . $clean_path : "../Images/default-plant.jpg";
            
            echo "<div class='card-spacing'><div class='card'>";
            echo "<a href='detail.php?id={$id}' class='product-link'>";
            echo "<img src='{$img_src}' class='card-img-top' alt='{$name}'>";
            echo "<h3>{$name}</h3>";
            echo "</a>";
            echo "<p class='price'>{$price}&nbsp;đ</p>";
            echo "<button class='add-to-cart' data-id='{$id}'>Thêm vào giỏ</button>";
            echo "</div></div>";
        }
        ?>
    </div>
</div>

<div id="danh-muc" class="section-title" style="margin-top: 80px;">
    <h2><?php echo $section_title; ?></h2>
    <p><?php echo $section_desc; ?></p>
</div>

<div class="filter-bar">
    <div class="category-tabs">
        <?php 
        if (empty($search_keyword) && isset($categories_list)): 
            foreach ($categories_list as $cat): ?>
                <a href="index.php?category_id=<?php echo $cat['CategoryID']; ?>&sort=<?php echo $sort; ?>#danh-muc" 
                   class="tab-btn <?php echo ($selected_cat_id == $cat['CategoryID']) ? 'active' : ''; ?>">
                    <?php echo mb_strtoupper($cat['CategoryName'], 'UTF-8'); ?>
                </a>
        <?php 
            endforeach; 
        endif; 
        ?>
        
        <?php if (!empty($search_keyword)): ?>
            <a href="index.php#danh-muc" class="tab-btn active"><i class="fa-solid fa-arrow-left"></i> Trở về Danh mục</a>
        <?php endif; ?>
    </div>
    
    <div class="sort-box">
        <?php if ($sort == 'price_asc'): ?>
            <a href="index.php?<?php echo $url_params; ?>#danh-muc" class="sort-btn active">Giá tăng dần ⇡ (Hủy)</a>
        <?php else: ?>
            <a href="index.php?<?php echo $url_params; ?>&sort=price_asc#danh-muc" class="sort-btn">Lọc theo giá tăng dần ⇡</a>
        <?php endif; ?>
    </div>
</div>

<div class="product-wrapper">
    <div class="product-grid">
        <?php
        if ($stmt_cat_products->rowCount() > 0) {
            while ($row = $stmt_cat_products->fetch(PDO::FETCH_ASSOC)) {
                $id = $row['ProductID']; $name = $row['ProductName']; $price = number_format($row['Price'], 0, ',', '.');
                $image_url = $row['ImageUrl']; $clean_path = ltrim($image_url, '/');
                $img_src = !empty($image_url) ? "../" . $clean_path : "../Images/default-plant.jpg";
                
                echo "<div class='card'>";
                echo "<a href='detail.php?id={$id}' class='product-link'>";
                echo "<img src='{$img_src}' class='card-img-top' alt='{$name}'>";
                echo "<h3>{$name}</h3>";
                echo "</a>";
                echo "<p class='price'>{$price}&nbsp;đ</p>";
                echo "<button class='add-to-cart' data-id='{$id}'>Thêm vào giỏ</button>";
                echo "</div>";
            }
        } else {
            echo "<p style='grid-column: span 4; text-align: center; color: #777;'>Không có sản phẩm nào phù hợp.</p>";
        }
        ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="index.php?<?php echo $url_params; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>#danh-muc" 
                   class="<?php echo ($current_page == $i) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
<script>
    jQuery(document).ready(function($) {
        $('.banner-slider').slick({
            dots: true, infinite: true, speed: 800, fade: true, cssEase: 'linear', autoplay: true, autoplaySpeed: 4000,
            prevArrow: '<button type="button" class="slick-prev">&#10094;</button>',
            nextArrow: '<button type="button" class="slick-next">&#10095;</button>'
        });

        $('.product-slider').slick({
            dots: false, infinite: true, speed: 500, slidesToShow: 4, slidesToScroll: 2, autoplay: true, autoplaySpeed: 3000,
            prevArrow: '<button type="button" class="slick-prev">&#10094;</button>',
            nextArrow: '<button type="button" class="slick-next">&#10095;</button>',
            responsive: [
                { breakpoint: 1024, settings: { slidesToShow: 3, slidesToScroll: 2 } },
                { breakpoint: 768, settings: { slidesToShow: 2, slidesToScroll: 2 } }
            ]
        });

        $('.add-to-cart').click(function(e) {
            e.preventDefault(); // Ngăn sự kiện click vào nút Thêm giỏ hàng làm nhảy trang nếu có xung đột link
            var productId = $(this).data('id');
            var btn = $(this);
            $.ajax({
                url: 'add_to_cart.php', type: 'POST', data: { id: productId },
                success: function(response) {
                    $('#cart-count').text(response);
                    btn.text('Đã thêm ✓').css('background-color', '#ff9800');
                    setTimeout(function(){ btn.text('Thêm vào giỏ').css('background-color', '#2d5a27'); }, 1500);
                }
            });
        });
    });
</script>

<?php include_once 'includes/footer.php'; ?>