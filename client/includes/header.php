<?php
if (!class_exists('Database')) {
    require_once '../config/Database.php';
}
if (!class_exists('Category')) {
    require_once '../classes/Category.php';
}

$db_nav = (new Database())->getConnection();
$cat_nav = new Category($db_nav);
$nav_categories = $cat_nav->getAllCategories();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MowGarden - Cửa hàng Cây Cảnh</title>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap');
        body { 
            font-family: 'Montserrat', sans-serif; 
            background-color: #f8f9fa; 
            margin: 0; 
            padding: 0; 
            color: #333; 
        }
        
        .navbar { 
            background-color: #fff; 
            border-bottom: 1px solid #eaeaea; 
            position: sticky; 
            top: 0; 
            z-index: 1000; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.02); 
            width: 100%; 
        }
        .navbar-container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 15px 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .navbar .logo a { 
            font-size: 26px; 
            font-weight: 800; 
            color: #1a1a1a; 
            text-decoration: none; 
            letter-spacing: 1px; 
        }
        .navbar .menu { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            gap: 35px; 
            flex-grow: 1; 
        }
        .navbar .menu > a, .navbar .menu .dropdown > a { 
            text-decoration: none; 
            color: #333; 
            font-weight: 600; 
            font-size: 13px; 
            text-transform: uppercase; 
            transition: color 0.3s; 
            padding: 10px 0; 
            margin: 0; 
        }
        .navbar .menu a:hover { 
            color: #2d5a27; 
        }
        
        .dropdown { 
            position: relative; 
            display: inline-block; 
        }
        .dropdown-content { 
            visibility: hidden; 
            opacity: 0; 
            position: absolute; 
            background-color: #fff; 
            min-width: 220px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.08); 
            z-index: 1001; 
            border-radius: 8px; 
            top: 130%; 
            left: 50%; 
            transform: translateX(-50%); 
            transition: all 0.3s ease; 
            border: 1px solid #eee; 
            overflow: hidden; 
        }
        .dropdown:hover .dropdown-content { 
            visibility: visible; 
            opacity: 1; 
            top: 100%; 
        }
        .dropdown-content a { 
            color: #555; 
            padding: 12px 20px; 
            text-decoration: none; 
            display: block; 
            text-align: left; 
            font-size: 13px; 
            border-bottom: 1px solid #f5f5f5; 
            margin: 0; 
            transition: all 0.3s; 
        }
        .dropdown-content a:hover { 
            background-color: #f9f9f9; 
            color: #2d5a27; 
            padding-left: 25px; 
        }

        .navbar .icons { 
            display: flex; 
            gap: 20px; 
            align-items: center; 
        }
        .navbar .icons a { 
            color: #333; 
            font-size: 18px; 
            text-decoration: none; 
            cursor: pointer; 
            transition: 0.3s; 
            position: relative; 
        }
        .navbar .icons a:hover { 
            color: #2d5a27; 
        }
        .cart-count-badge { 
            position: absolute; 
            top: -8px; 
            right: -12px; 
            background-color: #2d5a27; 
            color: white; 
            font-size: 11px; 
            font-weight: bold; 
            width: 18px; 
            height: 18px; 
            border-radius: 50%; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
        }
        .search-box { 
            display: flex; 
            align-items: center; 
            background: #f1f1f1; 
            border-radius: 20px; 
            padding: 4px 12px; 
            margin-right: 10px; 
        }
        .search-box input { 
            border: none; 
            background: transparent; 
            outline: none; 
            padding: 5px; 
            font-family: inherit; 
            font-size: 13px; 
            width: 140px; 
        }
        .search-box button { 
            background: transparent; 
            border: none; 
            cursor: pointer; 
        }

        .modal { 
            display: none; 
            position: fixed; 
            z-index: 2000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content { 
            background-color: #fff; 
            margin: 8% auto; 
            padding: 30px; 
            border-radius: 12px; 
            width: 380px; 
            position: relative; 
            box-shadow: 0 5px 25px rgba(0,0,0,0.2); 
        }
        .close-btn { 
            position: absolute; 
            right: 20px; 
            top: 15px; 
            font-size: 24px; 
            cursor: pointer; 
            color: #aaa; 
        }
        .auth-tabs { 
            display: flex; 
            gap: 20px; 
            margin-bottom: 25px; 
            border-bottom: 2px solid #eee; 
            padding-bottom: 10px; 
        }
        .auth-tabs button { 
            border: none; 
            background: none; 
            font-weight: 800; 
            cursor: pointer; 
            color: #999; 
            font-size: 16px; 
        }
        .auth-tabs .tab-active { 
            color: #2d5a27; 
        }
        .form-control { 
            width: 100%; 
            margin-bottom: 15px; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            box-sizing: border-box; 
        }
        .btn-submit { 
            width: 100%; 
            background: #2d5a27; 
            color: white; 
            padding: 12px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-weight: bold; 
        }
        
        .user-dropdown { 
            position: relative; 
            display: inline-block; 
        }
        .user-dropdown-content { 
            visibility: hidden; 
            opacity: 0; 
            position: absolute; 
            background-color: #fff; 
            min-width: 180px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            z-index: 1002; 
            border-radius: 8px; 
            top: 150%; 
            right: 0; 
            transition: all 0.3s ease; 
            border: 1px solid #eee;
        }
        .user-dropdown:hover .user-dropdown-content { 
            visibility: visible; 
            opacity: 1; 
            top: 120%; 
        }
        .user-dropdown-content a { 
            color: #555; 
            padding: 10px 15px; 
            text-decoration: none; 
            display: block; 
            font-size: 13px; 
            border-bottom: 1px solid #f5f5f5; 
        }
        .user-dropdown-content a:hover { 
            background-color: #f9f9f9; 
            color: #2d5a27; 
        }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="navbar-container">
            <div class="logo"><a href="index.php">MOWGARDEN</a></div>
            
            <div class="menu">
                <a href="index.php">TRANG CHỦ</a>
                <div class="dropdown">
                    <a href="index.php#danh-muc" style="cursor: pointer;">DANH MỤC 
                        <i class="fa-solid fa-caret-down"></i>
                    </a>
                    <div class="dropdown-content">
                        <?php
                        while ($nav_row = $nav_categories->fetch(PDO::FETCH_ASSOC)) {
                            $nav_cat_id = $nav_row['CategoryID'];
                            $nav_cat_name = mb_strtoupper($nav_row['CategoryName'], 'UTF-8');
                            echo "<a href='index.php?category_id={$nav_cat_id}#danh-muc'>{$nav_cat_name}</a>";
                        }
                        ?>
                    </div>
                </div>
                <a href="#">DỊCH VỤ</a>
                <a href="#">BLOG</a>
                <a href="#">LIÊN HỆ</a>
            </div>
            
            <div class="icons">
                <form action="index.php#danh-muc" method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Tìm kiếm..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i>
                </button>
                </form>
                
                <?php if (isset($_SESSION['username'])): ?>
        <div class="user-dropdown">
            <a href="#" style="font-size: 14px; text-transform: none; color: #2d5a27; font-weight: bold;">
                <i class="fa-regular fa-user"></i> Xin chào, <?php echo htmlspecialchars($_SESSION['fullname']); ?>
            </a>
            <div class="user-dropdown-content">
                <a href="profile.php" style="font-size: 14px;">
                    <i class="fa-solid fa-user-edit"></i>Thông tin cá nhân
                </a>
                <a href="logout.php" style="font-size: 14px; color: #d32f2f;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
            </div>
        </div>
    <?php else: ?>
        <a href="javascript:void(0)" onclick="openAuthModal()">
            <i class="fa-regular fa-user"></i>
        </a>
    <?php endif; ?>
                
                <a href="cart.php">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-count-badge" id="cart-count"><?php echo isset($cart_count) ? $cart_count : 0; ?></span>
                </a>
            </div>
        </div>
    </div>

    <div id="authModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeAuthModal()">&times;</span>
            <div class="auth-tabs">
                <button id="loginTab" class="tab-active" onclick="switchAuth('login')">ĐĂNG NHẬP</button>
                <button id="registerTab" onclick="switchAuth('register')">ĐĂNG KÝ</button>
            </div>
            <form id="loginForm" method="POST" action="../client/auth_process.php">
                <input type="hidden" name="action" value="login">
                <input type="text" name="username" placeholder="Tên đăng nhập" required class="form-control">
                <input type="password" name="password" placeholder="Mật khẩu" required class="form-control">
                <button type="submit" class="btn-submit">ĐĂNG NHẬP</button>
            </form>
            <form id="registerForm" method="POST" action="../client/auth_process.php" style="display:none;">
                <input type="hidden" name="action" value="register">
                <input type="text" name="username" placeholder="Tên đăng nhập" required class="form-control">
                <input type="password" name="password" placeholder="Mật khẩu" required class="form-control">
                <input type="text" name="fullname" placeholder="Họ và tên" required class="form-control">
                <input type="email" name="email" placeholder="Email" required class="form-control">
                <input type="tel" name="phone" placeholder="Số điện thoại" required class="form-control">
                <input type="text" name="address" placeholder="Địa chỉ" required class="form-control">
                
                <button type="submit" class="btn-submit">ĐĂNG KÝ</button>
            </form>
        </div>
    </div>

    <script>
        function openAuthModal() { document.getElementById('authModal').style.display = 'block'; }
        function closeAuthModal() { document.getElementById('authModal').style.display = 'none'; }
        function switchAuth(type) {
            document.getElementById('loginForm').style.display = (type === 'login') ? 'block' : 'none';
            document.getElementById('registerForm').style.display = (type === 'register') ? 'block' : 'none';
            document.getElementById('loginTab').className = (type === 'login') ? 'tab-active' : '';
            document.getElementById('registerTab').className = (type === 'register') ? 'tab-active' : '';
        }
        window.onclick = function(event) {
            if (event.target == document.getElementById('authModal')) closeAuthModal();
        }
    </script>

    <div class="main-content">