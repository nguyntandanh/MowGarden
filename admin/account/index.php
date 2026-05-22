<?php
session_start();
if (!isset($_SESSION['account_id']) || $_SESSION['account_id'] != 1) {
    header("Location: ../../client/index.php");
    exit();
}

require_once '../../config/Database.php';
$db = (new Database())->getConnection();
$account_id = $_SESSION['account_id'];
$message = "";
$message_type = "";
$stmt = $db->prepare("SELECT Username, FullName, Phone, Password FROM Account WHERE AccountID = ? LIMIT 1");
$stmt->execute([$account_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($old_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $message = "Vui lòng nhập đầy đủ: mật khẩu hiện tại, mật khẩu mới và xác nhận mật khẩu!";
            $message_type = "error";
        } 
        elseif ($old_password !== $admin['Password']) {
            $message = "Mật khẩu hiện tại không chính xác!";
            $message_type = "error";
        } 
        elseif ($new_password !== $confirm_password) {
            $message = "Mật khẩu mới và xác nhận mật khẩu không khớp nhau!";
            $message_type = "error";
        } 
        else {
            try {
                $stmt = $db->prepare("UPDATE Account SET FullName = ?, Phone = ?, Password = ? WHERE AccountID = ?");
                $stmt->execute([$fullname, $phone, $new_password, $account_id]);
                $message = "Cập nhật thông tin và đổi mật khẩu thành công!";
                $message_type = "success";
                $admin['FullName'] = $fullname;
                $admin['Phone'] = $phone;
                $admin['Password'] = $new_password;
            } 
            catch (PDOException $e) {
                $message = "Có lỗi xảy ra: " . $e->getMessage();
                $message_type = "error";
            }
        }
    } else {
        try {
            $stmt = $db->prepare("UPDATE Account SET FullName = ?, Phone = ? WHERE AccountID = ?");
            $stmt->execute([$fullname, $phone, $account_id]);
            $message = "Cập nhật thông tin cá nhân thành công!";
            $message_type = "success";
            $admin['FullName'] = $fullname;
            $admin['Phone'] = $phone;
        } 
        catch (PDOException $e) {
            $message = "Có lỗi xảy ra: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

$initial = !empty($admin['FullName']) ? mb_substr($admin['FullName'], 0, 1, 'UTF-8') : mb_substr($admin['Username'], 0, 1, 'UTF-8');

include '../includes/header_admin.php';
?>

<style>
    .account-header { 
        margin-bottom: 25px; 
    }
    .account-header h2 { 
        color: #1b4332; 
        margin: 0; 
        font-weight: 800; 
        text-transform: uppercase; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
    }
    
    .account-container { 
        display: flex; 
        gap: 30px; 
        align-items: flex-start; 
    }
    
    .profile-card { 
        flex: 1; 
        background: #fff; 
        padding: 30px 20px; 
        border-radius: 12px; 
        text-align: center; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        position: sticky; 
        top: 20px; 
    }
    .avatar-large { 
        width: 100px; 
        height: 100px; 
        background: linear-gradient(135deg, #2d5a27, #6cbf73); 
        color: white; 
        border-radius: 50%; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-size: 40px; 
        font-weight: bold; 
        margin: 0 auto 15px; 
        box-shadow: 0 4px 10px rgba(45, 90, 39, 0.3); 
    }
    .profile-card h3 { 
        margin: 0 0 5px; 
        color: #333; 
        font-size: 20px; 
    }
    .profile-card p { 
        margin: 0 0 25px; 
        color: #777; 
        font-size: 14px; 
    }
    .badge-role { 
        background: #e9ecef; 
        color: #495057; 
        padding: 5px 12px; 
        border-radius: 20px; 
        font-size: 12px; 
        font-weight: bold; 
        display: inline-block; 
        margin-bottom: 20px; 
    }
    
    .btn-logout { 
        display: block; 
        width: 100%; 
        background: #dc3545; 
        color: white; 
        padding: 12px; 
        border-radius: 6px; 
        text-decoration: none; 
        font-weight: bold; 
        font-size: 15px; 
        transition: 0.3s; 
        border: none; 
        cursor: pointer; 
    }
    .btn-logout:hover { 
        background: #c82333; 
    }
    
    .edit-section { 
        flex: 2; 
        background: #fff; 
        padding: 30px; 
        border-radius: 12px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
    }
    .edit-title { 
        font-size: 18px; 
        font-weight: 700; 
        color: #333; 
        margin-bottom: 20px; 
        border-bottom: 2px solid #eee; 
        padding-bottom: 10px; 
    }
    
    .form-row { 
        display: flex; 
        gap: 20px; 
        margin-bottom: 20px; 
    }
    .form-group { 
        flex: 1; 
        margin-bottom: 20px; 
    }
    .form-group label { 
        display: block; 
        font-weight: 600; 
        color: #555; 
        margin-bottom: 8px; 
        font-size: 14px; 
    }
    .form-input { 
        width: 100%; 
        padding: 12px; 
        border: 1px solid #ddd; 
        border-radius: 6px; 
        box-sizing: border-box; 
        font-size: 14px; 
        transition: 0.3s; 
        background: #fdfdfd; 
    }
    .form-input:focus { 
        border-color: #2d5a27; 
        outline: none; 
        box-shadow: 0 0 5px rgba(45, 90, 39, 0.1); 
        background: #fff; 
    }
    .input-readonly { 
        background: #e9ecef; 
        color: #666; 
        cursor: not-allowed; 
    }
    
    .password-notice { 
        font-size: 13px; 
        color: #856404; 
        background: #fff3cd; 
        padding: 10px 15px; 
        border-radius: 6px; 
        margin-bottom: 20px; 
        display: flex; 
        align-items: center; 
        gap: 8px; 
        border: 1px solid #ffeeba; 
    }
    
    .btn-save { 
        background: #2d5a27; 
        color: white; 
        border: none; 
        padding: 12px 30px; 
        border-radius: 6px; 
        font-size: 15px; 
        font-weight: bold; 
        cursor: pointer; 
        transition: 0.3s; 
        float: right; 
    }
    .btn-save:hover { 
        background: #1f401b; 
    }
    
    .alert { 
        padding: 15px 20px; 
        border-radius: 6px; 
        margin-bottom: 25px; 
        font-weight: bold; 
    }
    .alert-success { 
        background: #d4edda; 
        color: #155724; 
        border: 1px solid #c3e6cb; 
    }
    .alert-error { 
        background: #f8d7da; 
        color: #721c24; 
        border: 1px solid #f5c6cb; 
    }
    
    .clearfix::after { 
        content: ""; 
        clear: both; 
        display: table; 
        }
</style>

<div class="account-header">
    <h2>
        <i class="fa-solid fa-user-gear"></i> Tài khoản Quản trị
    </h2>
</div>

<?php if($message != ""): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo $message_type == 'success' ? '<i class="fa-solid fa-check-circle"></i>' : '<i class="fa-solid fa-triangle-exclamation"></i>'; ?> 
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="account-container">
    <div class="profile-card">
        <div class="avatar-large"><?php echo strtoupper($initial); ?></div>
        <h3><?php echo htmlspecialchars($admin['FullName'] ?? 'Quản trị viên'); ?></h3>
        <p>@<?php echo htmlspecialchars($admin['Username']); ?></p>
        <span class="badge-role">
            <i class="fa-solid fa-shield-halved"></i> Super Admin
        </span>
        
        <hr style="border: 0; border-top: 1px dashed #eee; margin: 20px 0;">
        
        <a href="../logout.php" class="btn-logout" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất khỏi phiên làm việc này?');">
            <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất ngay
        </a>
    </div>

    <div class="edit-section">
        <div class="edit-title">
            <i class="fa-solid fa-pen-to-square"></i> Cập nhật thông tin cá nhân
        </div>
        
        <form action="" method="POST" class="clearfix" autocomplete="off">
            <div class="form-row">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Tên đăng nhập (Username)</label>
                    <input type="text" class="form-input input-readonly" value="<?php echo htmlspecialchars($admin['Username']); ?>" readonly>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Họ và tên hiển thị</label>
                    <input type="text" name="fullname" class="form-input" value="<?php echo htmlspecialchars($admin['FullName'] ?? ''); ?>" placeholder="Nhập họ và tên..." required>
                </div>
            </div>

            <div class="form-group">
                <label>Số điện thoại liên hệ</label>
                <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($admin['Phone'] ?? ''); ?>" placeholder="Nhập số điện thoại...">
            </div>

            <div class="edit-title" style="margin-top: 40px; border-top: 2px solid #eee; padding-top: 20px;">
                <i class="fa-solid fa-key"></i> Đổi mật khẩu
            </div>
            
            <div class="password-notice">
                <i class="fa-solid fa-circle-info"></i> Bỏ trống 3 ô dưới đây nếu bạn chỉ muốn cập nhật thông tin cá nhân mà không đổi mật khẩu.
            </div>

            <div class="form-group">
                <label>Mật khẩu hiện tại</label>
                <input type="password" name="old_password" class="form-input" placeholder="Nhập mật khẩu cũ của bạn..." autocomplete="new-password">
            </div>

            <div class="form-row">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Mật khẩu mới</label>
                    <input type="password" name="new_password" class="form-input" placeholder="Nhập mật khẩu mới..." autocomplete="new-password">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Xác nhận mật khẩu mới</label>
                    <input type="password" name="confirm_password" class="form-input" placeholder="Nhập lại mật khẩu mới..." autocomplete="new-password">
                </div>
            </div>

            <button type="submit" name="update_profile" class="btn-save">
                <i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi
            </button>
        </form>
    </div>
</div>

<?php include '../includes/footer_admin.php'; ?>