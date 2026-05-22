<?php
session_start();
if (!isset($_SESSION['account_id']) || $_SESSION['account_id'] != 1) {
    header("Location: ../../client/index.php");
    exit();
}

require_once '../../config/Database.php';
$db = (new Database())->getConnection();

// ==========================================
// 1. XỬ LÝ CÁC THAO TÁC (THÊM/SỬA/XÓA MỀM)
// ==========================================
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // --- QUẢN LÝ DANH MỤC ---
        if ($action == 'add_category') {
            $name = trim($_POST['category_name']);
            $stmt = $db->prepare("INSERT INTO category (CategoryName) VALUES (?)");
            $stmt->execute([$name]);
            $message = "Thêm danh mục thành công!";
        } 
        elseif ($action == 'edit_category') {
            $id = $_POST['category_id'];
            $name = trim($_POST['category_name']);
            $stmt = $db->prepare("UPDATE category SET CategoryName = ? WHERE CategoryID = ?");
            $stmt->execute([$name, $id]);
            $message = "Cập nhật danh mục thành công!";
        } 
        elseif ($action == 'delete_category') {
            $id = $_POST['category_id'];
            $stmt = $db->prepare("DELETE FROM category WHERE CategoryID = ?");
            $stmt->execute([$id]);
            $message = "Xóa danh mục thành công!";
        }
        
        // --- QUẢN LÝ SẢN PHẨM ---
        elseif ($action == 'add_product' || $action == 'edit_product') {
            $name = trim($_POST['product_name']);
            $cat_id = $_POST['category_id'];
            $price = str_replace(',', '', $_POST['price']);
            
            // Xử lý Upload Ảnh
            $imageUrl = $_POST['old_image'] ?? ''; 
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $fileName = time() . '_' . basename($_FILES['image']['name']);
                $targetPath = "../../Images/" . $fileName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imageUrl = "/Images/" . $fileName;
                }
            }

            if ($action == 'add_product') {
                $stmt = $db->prepare("INSERT INTO product (ProductName, CategoryID, Price, ImageUrl, IsDeleted) VALUES (?, ?, ?, ?, 0)");
                $stmt->execute([$name, $cat_id, $price, $imageUrl]);
                $message = "Thêm sản phẩm thành công!";
            } else {
                $id = $_POST['product_id'];
                $stmt = $db->prepare("UPDATE product SET ProductName=?, CategoryID=?, Price=?, ImageUrl=? WHERE ProductID=?");
                $stmt->execute([$name, $cat_id, $price, $imageUrl, $id]);
                $message = "Cập nhật sản phẩm thành công!";
            }
        } 
        // ==========================================
        // THAY ĐỔI TẠI ĐÂY: Chuyển sang XÓA MỀM (Soft Delete)
        // ==========================================
        elseif ($action == 'delete_product') {
            $id = $_POST['product_id'];
            // Thay vì DELETE FROM, ta chuyển trạng thái IsDeleted lên 1
            $stmt = $db->prepare("UPDATE product SET IsDeleted = 1 WHERE ProductID = ?");
            $stmt->execute([$id]);
            $message = "Xóa sản phẩm thành công!";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $message = "Không thể xóa danh mục này vì đang có sản phẩm thuộc danh mục!";
        } else {
            $message = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}

// ==========================================
// 2. LẤY DỮ LIỆU HIỂN THỊ (LỌC SẢN PHẨM CHƯA XÓA)
// ==========================================
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$category_filter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// THAY ĐỔI TẠI ĐÂY: Mặc định chỉ lấy những sản phẩm có IsDeleted = 0
$whereClause = "WHERE p.IsDeleted = 0";
$params = [];

if ($category_filter > 0) {
    $whereClause .= " AND p.CategoryID = ?";
    $params[] = $category_filter;
}

$stmt_cats = $db->query("SELECT * FROM category ORDER BY CategoryID DESC");
$categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT p.*, c.CategoryName 
          FROM product p 
          LEFT JOIN category c ON p.CategoryID = c.CategoryID 
          $whereClause 
          ORDER BY p.ProductID DESC 
          LIMIT $limit";
          
$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header_admin.php';
?>

<style>
    .toolbar { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 20px; 
        background: #fff; 
        padding: 15px; 
        border-radius: 8px; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
    }
    .toolbar-left, .toolbar-right { 
        flex: 1; 
    }
    .toolbar-center { 
        flex: 2; 
        display: flex; 
        justify-content: center; 
        gap: 10px; 
    }
    .toolbar-right { 
        display: flex; 
        justify-content: flex-end; 
    }
    
    .filter-select, .form-input { 
        padding: 8px 12px; 
        border: 1px solid #ddd; 
        border-radius: 4px; 
        font-size: 14px; 
        outline: none; 
        width: 100%; 
        box-sizing: border-box; 
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
    }
    .btn-add:hover { 
        background: #218838; 
    }
    .btn-modal { 
        background: #007bff; 
        color: white; 
        padding: 10px 15px; 
        border: none; 
        border-radius: 4px; 
        cursor: pointer; 
        font-size: 14px; 
        font-weight: bold; 
        transition: 0.2s; 
    }
    .btn-modal:hover { 
        background: #0069d9; 
    }
    
    table { 
        width: 100%; 
        border-collapse: collapse; 
        background: #fff; 
        border-radius: 8px; 
        overflow: hidden; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
    }
    th { 
        background: #f8f9fa; 
        padding: 15px; 
        text-align: left; 
        border-bottom: 2px solid #eee; 
    }
    td { 
        padding: 15px; 
        border-bottom: 1px solid #eee; 
        vertical-align: middle; 
    }
    
    .img-preview { 
        width: 70px; 
        height: 70px; 
        object-fit: cover; 
        border-radius: 6px; 
        border: 1px solid #ddd; 
        box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    
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
        cursor: pointer; 
        font-size: 13px; 
    }

    .modal { 
        display: none; 
        position: fixed; 
        z-index: 1000; 
        left: 0; 
        top: 0; 
        width: 100%; 
        height: 100%; 
        background-color: rgba(0,0,0,0.5); 
        align-items: center; 
        justify-content: center; 
    }
    .modal-content { 
        background-color: #fff; 
        padding: 25px; 
        border-radius: 8px; 
        width: 500px; 
        max-width: 90%; 
        position: relative; 
    }
    .close-modal { 
        position: absolute; 
        top: 15px; 
        right: 20px; 
        font-size: 24px; 
        font-weight: bold; 
        cursor: pointer; 
        color: #888; 
    }
    .close-modal:hover { 
        color: #000; 
    }
    
    .form-group { 
        margin-bottom: 15px; 
    }
    .form-group label { 
        display: block; 
        margin-bottom: 5px; 
        font-weight: bold; 
        font-size: 14px; 
    }
    
    .cat-list { 
        margin-top: 15px; 
        max-height: 300px; 
        overflow-y: auto; 
    }
    .cat-item { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 10px; 
        border-bottom: 1px solid #eee; 
        }
</style>

<?php if($message != ""): ?>
    <script>alert("<?php echo $message; ?>");</script>
<?php endif; ?>

<h1>Quản lý sản phẩm</h1>

<form method="GET" action="" id="filterForm">
    <div class="toolbar">
        <div class="toolbar-left">
            <select name="limit" class="filter-select" onchange="document.getElementById('filterForm').submit();" style="width: auto;">
                <option value="10" <?php if($limit == 10) echo 'selected'; ?>>Hiển thị 10 sản phẩm</option>
                <option value="20" <?php if($limit == 20) echo 'selected'; ?>>Hiển thị 20 sản phẩm</option>
            </select>
        </div>

        <div class="toolbar-center">
            <button type="button" class="btn-add" onclick="openProductModal('add')"><i class="fa-solid fa-plus"></i> Thêm sản phẩm</button>
            <button type="button" class="btn-modal" onclick="openCategoryModal()"><i class="fa-solid fa-list"></i> Quản lý danh mục</button>
        </div>

        <div class="toolbar-right">
            <select name="category_id" class="filter-select" onchange="document.getElementById('filterForm').submit();" style="width: auto;">
                <option value="0">Tất cả danh mục</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?php echo $cat['CategoryID']; ?>" <?php if($category_filter == $cat['CategoryID']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['CategoryName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>

<table>
    <tr>
        <th>ID</th>
        <th>Ảnh</th>
        <th>Tên sản phẩm</th>
        <th>Danh mục</th>
        <th>Giá</th>
        <th>Thao tác</th>
    </tr>
    <?php foreach($products as $row): 
        $fullPath = "../.." . $row['ImageUrl'];
    ?>
    <tr>
        <td>#<?php echo $row['ProductID']; ?></td>
        <td>
            <img src="<?php echo $fullPath; ?>" class="img-preview" alt="SP" onerror="this.src='../../Images/default.jpg'">
        </td>
        <td style="font-weight: 500;"><?php echo htmlspecialchars($row['ProductName']); ?></td>
        <td>
            <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-size: 13px;"><?php echo htmlspecialchars($row['CategoryName'] ?? 'Không có'); ?></span>
        </td>
        <td style="color: #d9534f; font-weight: bold;"><?php echo number_format($row['Price'], 0, ',', '.'); ?>đ</td>
        <td>
            <button class="btn-edit" onclick="openProductModal('edit', <?php echo $row['ProductID']; ?>, '<?php echo addslashes($row['ProductName']); ?>', <?php echo $row['CategoryID']; ?>, <?php echo $row['Price']; ?>, '<?php echo $row['ImageUrl']; ?>')"><i class="fa-solid fa-pen-to-square"></i></button>
            
            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">
                <input type="hidden" name="action" value="delete_product">
                <input type="hidden" name="product_id" value="<?php echo $row['ProductID']; ?>">
                <button type="submit" class="btn-delete">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($products)): ?>
    <tr><td colspan="6" style="text-align: center; color: #888; padding: 20px;">Không có sản phẩm nào.</td></tr>
    <?php endif; ?>
</table>

<div id="productModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeProductModal()">&times;</span>
        <h2 id="p_modal_title">Thêm sản phẩm mới</h2>
        
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="p_action" value="add_product">
            <input type="hidden" name="product_id" id="p_id" value="">
            <input type="hidden" name="old_image" id="p_old_image" value="">

            <div class="form-group">
                <label>Tên sản phẩm</label>
                <input type="text" name="product_name" id="p_name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label>Danh mục</label>
                <select name="category_id" id="p_cat" class="form-input" required>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['CategoryID']; ?>"><?php echo htmlspecialchars($cat['CategoryName']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Giá bán (VNĐ)</label>
                <input type="number" name="price" id="p_price" class="form-input" required>
            </div>

            <div class="form-group">
                <label>Tải ảnh lên (Bỏ trống nếu giữ ảnh cũ)</label>
                <input type="file" name="image" class="form-input" accept="image/*">
            </div>

            <button type="submit" class="btn-add" style="width: 100%;">
                <i class="fa-solid fa-save"></i> Lưu sản phẩm
            </button>
        </form>
    </div>
</div>

<div id="categoryModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeCategoryModal()">&times;</span>
        <h2>Quản lý danh mục</h2>
        
        <form action="" method="POST" style="display: flex; gap: 10px; margin-bottom: 15px;">
            <input type="hidden" name="action" value="add_category">
            <input type="text" name="category_name" class="form-input" placeholder="Nhập tên danh mục..." required>
            <button type="submit" class="btn-add">Thêm</button>
        </form>

        <div class="cat-list">
            <?php foreach($categories as $cat): ?>
            <div class="cat-item">
                <span><?php echo htmlspecialchars($cat['CategoryName']); ?></span>
                <div style="display: flex; gap: 5px;">
                    <button type="button" class="btn-edit" onclick="editCategory(<?php echo $cat['CategoryID']; ?>, '<?php echo addslashes($cat['CategoryName']); ?>')">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Xóa danh mục này?');">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_id" value="<?php echo $cat['CategoryID']; ?>">
                        <button type="submit" class="btn-delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<form id="editCategoryForm" action="" method="POST" style="display:none;">
    <input type="hidden" name="action" value="edit_category">
    <input type="hidden" name="category_id" id="edit_cat_id">
    <input type="hidden" name="category_name" id="edit_cat_name">
</form>

<script>
    var pModal = document.getElementById("productModal");
    
    function openProductModal(action, id = '', name = '', catId = '', price = '', oldImg = '') {
        pModal.style.display = "flex";
        
        if (action === 'edit') {
            document.getElementById('p_modal_title').innerText = "Cập nhật sản phẩm";
            document.getElementById('p_action').value = "edit_product";
            document.getElementById('p_id').value = id;
            document.getElementById('p_name').value = name;
            document.getElementById('p_cat').value = catId;
            document.getElementById('p_price').value = price;
            document.getElementById('p_old_image').value = oldImg;
        } else {
            document.getElementById('p_modal_title').innerText = "Thêm sản phẩm mới";
            document.getElementById('p_action').value = "add_product";
            document.getElementById('p_id').value = '';
            document.getElementById('p_name').value = '';
            document.getElementById('p_cat').value = '';
            document.getElementById('p_price').value = '';
            document.getElementById('p_old_image').value = '';
        }
    }
    
    function closeProductModal() { pModal.style.display = "none"; }
    var cModal = document.getElementById("categoryModal");
    function openCategoryModal() { cModal.style.display = "flex"; }
    function closeCategoryModal() { cModal.style.display = "none"; }
    function editCategory(id, oldName) {
        let newName = prompt("Nhập tên danh mục mới:", oldName);
        if (newName != null && newName.trim() !== "") {
            document.getElementById('edit_cat_id').value = id;
            document.getElementById('edit_cat_name').value = newName.trim();
            document.getElementById('editCategoryForm').submit();
        }
    }

    window.onclick = function(event) {
        if (event.target == pModal) closeProductModal();
        if (event.target == cModal) closeCategoryModal();
    }
</script>

<?php include '../includes/footer_admin.php'; ?>