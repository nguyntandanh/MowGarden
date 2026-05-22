<?php
session_start();
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

if ($_POST['action'] == 'login') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $db->prepare("SELECT * FROM Account WHERE Username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && $password == $user['Password']) {
        $_SESSION['account_id'] = $user['AccountID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['fullname'] = $user['FullName'];
        echo "<script>alert('Đăng nhập thành công!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Sai thông tin!'); window.history.back();</script>";
    }
} elseif ($_POST['action'] == 'register') {
    $username = $_POST['username'];
    $password = $_POST['password']; 
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $stmt = $db->prepare("INSERT INTO Account (Username, Password, FullName, Email, Phone, Address) VALUES (?, ?, ?, ?, ?, ?)");
    if($stmt->execute([$username, $password, $fullname, $email, $phone, $address])) {
        echo "<script>alert('Đăng ký thành công!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Đăng ký thất bại!'); window.history.back();</script>";
    }
}
?>