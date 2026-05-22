<?php
session_start();
if (!isset($_SESSION['account_id']) || $_SESSION['account_id'] != 1) {
    header("Location: ../client/index.php");
    exit();
}

require_once '../config/Database.php';
$db = (new Database())->getConnection();
$count_orders = $db->query("SELECT COUNT(*) FROM Orders")->fetchColumn();
$total_revenue = $db->query("SELECT SUM(TotalAmount) FROM Orders")->fetchColumn();
$count_customers = $db->query("SELECT COUNT(*) FROM Account")->fetchColumn();
$low_stock = $db->query("SELECT COUNT(*) FROM product WHERE StockQuantity <= 10 AND IsDeleted = 0")->fetchColumn();
$chart_data = $db->query("SELECT DAY(OrderDate) as day, SUM(TotalAmount) as daily_revenue 
                          FROM Orders 
                          WHERE MONTH(OrderDate) = MONTH(CURRENT_DATE()) 
                          AND YEAR(OrderDate) = YEAR(CURRENT_DATE()) 
                          GROUP BY DAY(OrderDate) 
                          ORDER BY day ASC");

$labels = [];
$values = [];
while($row = $chart_data->fetch(PDO::FETCH_ASSOC)) {
    $labels[] = "Ngày " . $row['day'];
    $values[] = $row['daily_revenue'];
}
$recent_orders = $db->query("SELECT o.*, a.FullName FROM Orders o JOIN Account a ON o.AccountID = a.AccountID ORDER BY OrderDate DESC LIMIT 6");

function getStatusInfo($status) {
    switch ($status) {
        case 0:  return ['label' => 'Chờ xử lý',   'class' => 'status-pending'];
        case 1:  return ['label' => 'Đã xác nhận', 'class' => 'status-confirmed'];
        case 2:  return ['label' => 'Đã giao',     'class' => 'status-delivered'];
        case -1: return ['label' => 'Đã hủy',      'class' => 'status-cancelled'];
        default: return ['label' => 'Không rõ',    'class' => ''];
    }
}

include 'includes/header_admin.php';
?>

<style>
    .dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .btn-detail { padding: 5px 10px; background: #2d5a27; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; }
    .btn-detail:hover { background: #1f401b; }
    
    .stats-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
    .stat-card { padding: 20px; border-radius: 12px; color: white; text-decoration: none; transition: transform 0.2s; display: block; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card h3 { margin: 0; font-size: 14px; opacity: 0.9; }
    .stat-card p { margin: 10px 0 0 0; font-size: 24px; font-weight: bold; }
    
    .bg-orders { background: linear-gradient(45deg, #007bff, #00c6ff); }
    .bg-revenue { background: linear-gradient(45deg, #28a745, #56ab2f); }
    .bg-customers { background: linear-gradient(45deg, #6f42c1, #a855f7); }
    .bg-stock { background: linear-gradient(45deg, #fd7e14, #ff9f43); }

    .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-confirmed { background: #d1ecf1; color: #0c5460; }
    .status-delivered { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
</style>

<h2>TỔNG QUAN HỆ THỐNG</h2>

<div class="stats-container">
    <a href="order/" class="stat-card bg-orders">
        <h3>Đơn hàng</h3>
        <p><?php echo $count_orders; ?></p>
    </a>
    <a href="order/" class="stat-card bg-revenue">
        <h3>Doanh thu</h3>
        <p><?php echo number_format($total_revenue ?? 0, 0, ',', '.'); ?>đ</p>
    </a>
    <a href="customer/" class="stat-card bg-customers">
        <h3>Khách hàng</h3>
        <p><?php echo $count_customers; ?></p>
    </a>
    <a href="inventory/" class="stat-card bg-stock">
        <h3>Cây sắp hết</h3>
        <p><?php echo $low_stock; ?></p>
    </a>
</div>

<div class="dashboard-grid">
    <div class="card">
        <h3>Biểu đồ doanh thu tháng hiện tại</h3>
        <canvas id="revenueChart" style="margin-top: 20px;"></canvas>
    </div>

    <div class="card">
        <h3>Đơn hàng mới nhất</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <tr style="text-align: left; border-bottom: 2px solid #eee;">
                <th style="padding: 10px;">ID</th>
                <th style="padding: 10px;">Khách hàng</th>
                <th style="padding: 10px;">Ngày đặt</th>
                <th style="padding: 10px; text-align: right;">Tổng tiền</th>
                <th style="padding: 10px;">Trạng thái</th>
                <th style="padding: 10px;">Thao tác</th>
            </tr>
            <?php while($row = $recent_orders->fetch(PDO::FETCH_ASSOC)): 
                $statusInfo = getStatusInfo($row['Status']);
            ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;">#<?php echo $row['OrderID']; ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($row['FullName']); ?></td>
                <td style="padding: 10px;"><?php echo date('d/m/Y', strtotime($row['OrderDate'])); ?></td>
                <td style="padding: 10px; text-align: right; font-weight: 600;"><?php echo number_format($row['TotalAmount'], 0, ',', '.'); ?>đ</td>
                <td style="padding: 10px;">
                    <span class="status-badge <?php echo $statusInfo['class']; ?>">
                        <?php echo $statusInfo['label']; ?>
                    </span>
                </td>
                <td style="padding: 10px;">
                    <a href="order/detail.php?id=<?php echo $row['OrderID']; ?>" class="btn-detail">Xem chi tiết</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: <?php echo json_encode($values); ?>,
                borderColor: '#2d5a27',
                backgroundColor: 'rgba(45, 90, 39, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
    });
</script>

<?php include 'includes/footer_admin.php'; ?>