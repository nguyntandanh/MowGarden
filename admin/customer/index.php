<?php
session_start();
if (!isset($_SESSION['account_id']) || $_SESSION['account_id'] != 1) {
    header("Location: ../../client/index.php");
    exit();
}

require_once '../../config/Database.php';
$db = (new Database())->getConnection();
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action == 'add_customer') {
            $username = trim($_POST['username']);
            $fullname = trim($_POST['fullname']);
            $phone = trim($_POST['phone']);
            $password = trim($_POST['password']);
            $check = $db->prepare("SELECT AccountID FROM Account WHERE Username = ?");
            $check->execute([$username]);
            if ($check->rowCount() > 0) {
                $message = "Tên đăng nhập đã tồn tại!";
                $message_type = "error";
            } else {
                $stmt = $db->prepare("INSERT INTO Account (Username, Password, FullName, Phone) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $password, $fullname, $phone]);
                $message = "Thêm khách hàng mới thành công!";
                $message_type = "success";
            }
        } 
        elseif ($action == 'edit_customer') {
            $id = $_POST['account_id'];
            $fullname = trim($_POST['fullname']);
            $phone = trim($_POST['phone']);
            $stmt = $db->prepare("UPDATE Account SET FullName = ?, Phone = ? WHERE AccountID = ?");
            $stmt->execute([$fullname, $phone, $id]);
            $message = "Cập nhật thông tin thành công!";
            $message_type = "success";
        } 
        elseif ($action == 'delete_customer') {
            $id = $_POST['account_id'];
            if ($id == 1) {
                $message = "Không thể xóa tài khoản Quản trị viên gốc!";
                $message_type = "error";
            } 
            else {
                $stmt = $db->prepare("DELETE FROM Account WHERE AccountID = ?");
                $stmt->execute([$id]);
                $message = "Xóa khách hàng thành công!";
                $message_type = "success";
            }
        }
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $message = "Không thể xóa! Khách hàng này đã có đơn hàng trong hệ thống.";
        } else {
            $message = "Lỗi hệ thống: " . $e->getMessage();
        }
        $message_type = "error";
    }
}

$query = "SELECT a.AccountID, a.Username, a.FullName, a.Phone, 
                 COUNT(o.OrderID) as TotalOrders, 
                 SUM(o.TotalAmount) as TotalSpent 
          FROM Account a 
          LEFT JOIN Orders o ON a.AccountID = o.AccountID 
          GROUP BY a.AccountID 
          ORDER BY a.AccountID DESC";
$stmt = $db->query($query);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header_admin.php';
?>

<style>
    .customer-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 20px; 
    }
    .customer-header h2 { 
        color: #1b4332; 
        margin: 0; 
        font-weight: 800; 
        text-transform: uppercase; 
    }
    .btn-add { 
        background: #28a745; 
        color: white; 
        padding: 10px 15px; 
        border: none; 
        border-radius: 4px; 
        font-size: 14px; 
        font-weight: bold; 
        cursor: pointer; 
        transition: 0.2s; 
        display: flex; 
        align-items: center; 
        gap: 8px; 
    }
    .btn-add:hover { 
        background: #218838; 
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
    
    .user-info { 
        display: flex; 
        align-items: center; 
        gap: 12px; 
    }
    .user-avatar { 
        width: 40px; 
        height: 40px; 
        background: #e9ecef; 
        border-radius: 50%; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        color: #555; 
        font-weight: bold; 
        font-size: 18px; 
    }
    .user-name { 
        font-weight: 600; 
        color: #333; 
        font-size: 15px; 
        display: block; 
    }
    .user-username { 
        font-size: 13px; 
        color: #888; 
    }
    
    .badge-admin { 
        background: #dc3545; 
        color: white; 
        padding: 2px 6px; 
        border-radius: 4px; 
        font-size: 11px; 
        margin-left: 5px; 
    }
    
    .stat-number { 
        font-weight: bold; 
        color: #d9534f; 
    }
    .stat-orders { 
        font-weight: bold; 
        color: #007bff; 
        background: #e6f2ff; 
        padding: 3px 8px; 
        border-radius: 12px; 
    }

    .btn-edit { 
        background: #ffc107; 
        color: #000; 
        padding: 6px 12px; 
        border: none; 
        border-radius: 4px; 
        font-size: 13px; 
        cursor: pointer; 
    }
    .btn-delete { 
        background: #dc3545; 
        color: white; 
        padding: 6px 12px; 
        border: none; 
        border-radius: 4px; 
        font-size: 13px; 
        cursor: pointer; 
    }

    .alert { 
        padding: 12px 20px; 
        border-radius: 6px; 
        margin-bottom: 20px; 
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
        width: 400px; 
        border-radius: 12px; 
        padding: 25px; 
        position: relative; 
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
        margin-bottom: 20px; 
        border-bottom: 2px solid #eee; 
        padding-bottom: 10px;
    }
    .form-group { 
        margin-bottom: 15px; 
    }
    .form-group label { 
        display: block; 
        font-weight: 600; 
        color: #555; 
        margin-bottom: 5px; 
        font-size: 14px; 
    }
    .form-input { 
        width: 100%; 
        padding: 10px; 
        border: 1px solid #ddd; 
        border-radius: 6px; 
        box-sizing: border-box; 
        font-size: 14px; 
    }
    .form-input:focus { 
        border-color: #28a745; 
        outline: none; 
    }
    
    .btn-save { 
        width: 100%; 
        background: #28a745; 
        color: white; 
        border: none; 
        padding: 12px; 
        border-radius: 6px; 
        cursor: pointer; 
        font-weight: bold; 
        margin-top: 10px; 
        }
</style>

<div class="customer-header">
    <h2><i class="fa-solid fa-users"></i> Quản lý Khách hàng</h2>
    <button class="btn-add" onclick="openModal('add')"><i class="fa-solid fa-user-plus"></i> Thêm Khách hàng</button>
</div>

<?php if($message != ""): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo $message_type == 'success' ? '<i class="fa-solid fa-check-circle"></i>' : '<i class="fa-solid fa-triangle-exclamation"></i>'; ?> 
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Khách hàng</th>
            <th>Số điện thoại</th>
            <th style="text-align: center;">Số đơn hàng</th>
            <th>Tổng chi tiêu</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($customers as $row): 
            $initial = !empty($row['FullName']) ? mb_substr($row['FullName'], 0, 1, 'UTF-8') : mb_substr($row['Username'], 0, 1, 'UTF-8');
            $isAdmin = ($row['AccountID'] == 1);
        ?>
        <tr>
            <td>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper($initial); ?></div>
                    <div>
                        <span class="user-name">
                            <?php echo htmlspecialchars($row['FullName'] ?? 'Chưa cập nhật'); ?>
                            <?php if($isAdmin) echo '<span class="badge-admin">Admin</span>'; ?>
                        </span>
                        <span class="user-username">@<?php echo htmlspecialchars($row['Username']); ?></span>
                    </div>
                </div>
            </td>
            <td><?php echo htmlspecialchars($row['Phone'] ?? '---'); ?></td>
            <td style="text-align: center;">
                <span class="stat-orders"><?php echo $row['TotalOrders']; ?> đơn</span>
            </td>
            <td>
                <span class="stat-number"><?php echo number_format($row['TotalSpent'] ?? 0, 0, ',', '.'); ?> đ</span>
            </td>
            <td>
                <button class="btn-edit" onclick="openModal('edit', <?php echo $row['AccountID']; ?>, '<?php echo addslashes($row['Username']); ?>', '<?php echo addslashes($row['FullName'] ?? ''); ?>', '<?php echo addslashes($row['Phone'] ?? ''); ?>')">
                    <i class="fa-solid fa-pen"></i> Sửa
                </button>
                
                <?php if(!$isAdmin): ?>
                <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách hàng này?');">
                    <input type="hidden" name="action" value="delete_customer">
                    <input type="hidden" name="account_id" value="<?php echo $row['AccountID']; ?>">
                    <button type="submit" class="btn-delete">
                        <i class="fa-solid fa-trash"></i> Xóa
                    </button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        
        <?php if(empty($customers)): ?>
        <tr>
            <td colspan="5" style="text-align: center; color: #888; padding: 20px;">Không có dữ liệu khách hàng.</td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<div id="customerModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div class="modal-title" id="modal_title">Thêm Khách hàng</div>

        <form action="" method="POST">
            <input type="hidden" name="action" id="c_action" value="add_customer">
            <input type="hidden" name="account_id" id="c_id">

            <div class="form-group" id="group_username">
                <label>Tên đăng nhập (Username)*</label>
                <input type="text" name="username" id="c_username" class="form-input" required>
            </div>
            
            <div class="form-group" id="group_password">
                <label>Mật khẩu*</label>
                <input type="password" name="password" id="c_password" class="form-input" required>
            </div>

            <div class="form-group">
                <label>Họ và tên (Tên hiển thị)</label>
                <input type="text" name="fullname" id="c_fullname" class="form-input">
            </div>

            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="text" name="phone" id="c_phone" class="form-input">
            </div>

            <button type="submit" class="btn-save" id="btn_submit_modal">
                <i class="fa-solid fa-save"></i> LƯU THÔNG TIN
            </button>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('customerModal');

    function openModal(action, id = '', username = '', fullname = '', phone = '') {
        modal.style.display = 'flex';
        if (action === 'edit') {
            document.getElementById('modal_title').innerHTML = 'Cập nhật Khách hàng';
            document.getElementById('c_action').value = 'edit_customer';
            document.getElementById('c_id').value = id;
            document.getElementById('c_username').value = username;
            document.getElementById('c_username').readOnly = true;
            document.getElementById('c_username').style.backgroundColor = '#f4f4f4';
            document.getElementById('group_password').style.display = 'none';
            document.getElementById('c_password').removeAttribute('required');
            document.getElementById('c_fullname').value = fullname;
            document.getElementById('c_phone').value = phone;
        } 
        else {
            document.getElementById('modal_title').innerHTML = 'Thêm Khách hàng mới';
            document.getElementById('c_action').value = 'add_customer';
            document.getElementById('c_id').value = '';
            document.getElementById('c_username').value = '';
            document.getElementById('c_username').readOnly = false;
            document.getElementById('c_username').style.backgroundColor = '#fff';
            document.getElementById('group_password').style.display = 'block';
            document.getElementById('c_password').setAttribute('required', 'required');
            document.getElementById('c_password').value = '';
            document.getElementById('c_fullname').value = '';
            document.getElementById('c_phone').value = '';
        }
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) closeModal();
    }
</script>

<?php include '../includes/footer_admin.php'; ?>