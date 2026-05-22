<?php
session_start();

if (isset($_SESSION['account_id']) && $_SESSION['account_id'] == 1) {
    header("Location: index.php");
    exit();
}

require_once '../config/Database.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = (new Database())->getConnection();
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        $stmt = $db->prepare("SELECT AccountID, Password FROM Account WHERE Username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $password === $user['Password']) {
            if ($user['AccountID'] == 1) {
                $_SESSION['account_id'] = $user['AccountID'];
                header("Location: index.php");
                exit();
            } else {
                $error = 'Tài khoản này không có quyền truy cập trang quản trị!';
            }
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không chính xác!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Quản trị | MowGarden</title>
    <style>
        body { 
            margin: 0; 
            padding: 0; 
            font-family: 'Segoe UI', sans-serif; 
            background-color: #f4f6f9; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
        }
        .login-container { 
            background: white; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 400px; 
        }
        .login-header { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        .login-header h2 { 
            color: #2d5a27; 
            margin: 0 0 10px 0; 
            font-size: 28px; 
        }
        .login-header p { 
            color: #666; 
            margin: 0; 
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            color: #333; 
            font-weight: 500; 
        }
        .form-group input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 14px; 
        }
        .form-group input:focus { 
            border-color: #2d5a27; 
            outline: none; 
            box-shadow: 0 0 5px rgba(45, 90, 39, 0.2); 
        }
        .btn-login { 
            width: 100%; 
            padding: 12px; 
            background: #2d5a27; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
            transition: 0.3s; 
        }
        .btn-login:hover { 
            background: #1f401b; 
        }
        .error-message { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 10px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            font-size: 14px; 
            text-align: center; 
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h2>MOWGARDEN</h2>
        <p>Đăng nhập hệ thống quản trị</p>
    </div>

    <?php if ($error): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Tên đăng nhập</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn-login">Đăng nhập</button>
    </form>
</div>

</body>
</html>