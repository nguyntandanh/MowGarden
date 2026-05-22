<?php
session_start();
if (!isset($_SESSION['account_id']) || $_SESSION['account_id'] != 1) {
    header("Location: ../../client/index.php");
    exit();
}

require_once '../../config/Database.php';
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $db->prepare("UPDATE Orders SET Status = ? WHERE OrderID = ?");
    $stmt->execute([$status, $order_id]);
    
    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>