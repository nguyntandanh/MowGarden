<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | MowGarden</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { 
            box-sizing: border-box; 
        }
        body { 
            margin: 0; 
            padding: 0; 
            background-color: #f4f6f9; 
            font-family: 'Segoe UI', sans-serif; 
        }

        .admin-wrapper { 
            display: flex; 
            min-height: 100vh; 
        }
        
        .sidebar { 
            width: 250px; 
            background: #2d5a27; 
            color: white; 
            display: flex; 
            flex-direction: column; 
            flex-shrink: 0; 
        }
        .sidebar-top { 
            flex: 1; 
        }
        .sidebar-bottom { 
            padding-bottom: 20px; 
            border-top: 1px solid #3e7a37; 
            padding-top: 10px; 
        }
        
        .sidebar a { 
            display: block; 
            color: white; 
            padding: 15px 20px; 
            text-decoration: none; 
            font-size: 15px; 
            transition: 0.3s; 
        }
        .sidebar a:hover { 
            background: #1f401b; 
            padding-left: 25px; 
        }
        .sidebar a i { 
            margin-right: 10px; 
            width: 20px; 
        }
        
        .logo { 
            text-align: center; 
            padding: 25px 0; 
            font-size: 20px; 
            font-weight: bold; 
            background: #1f401b; 
        }
        
        .main-content { 
            flex: 1; 
            padding: 30px; 
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <nav class="sidebar">
        <div class="logo">MOWGARDEN ADMIN</div>
        
        <div class="sidebar-top">
            <a href="/BanCayCanh/admin/"><i class="fa-solid fa-gauge"></i> Tổng quan</a> 
            <a href="/BanCayCanh/admin/product/"><i class="fa-solid fa-seedling"></i> Sản phẩm</a>
            <a href="/BanCayCanh/admin/inventory/"><i class="fa-solid fa-boxes-stacked"></i> Kho hàng</a>
            <a href="/BanCayCanh/admin/order/"><i class="fa-solid fa-cart-shopping"></i> Đơn hàng</a>
            <a href="/BanCayCanh/admin/promotion/"><i class="fa-solid fa-tag"></i> Khuyến mãi</a>
            <a href="/BanCayCanh/admin/customer/"><i class="fa-solid fa-users"></i> Khách hàng</a>
        </div>

        <div class="sidebar-bottom">
            <a href="/BanCayCanh/client/"><i class="fa-solid fa-globe"></i> Xem trang web</a>
            <a href="/BanCayCanh/admin/account/"><i class="fa-solid fa-user-gear"></i> Tài khoản</a>
        </div>
    </nav>
    
    <main class="main-content">