<?php
session_start();
if(isset($_POST['id'])) {
    $product_id = $_POST['id'];
    if(!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    if(isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } 
    else {
        $_SESSION['cart'][$product_id] = 1;
    }
    $total_items = 0;
    foreach($_SESSION['cart'] as $quantity) {
        $total_items += $quantity;
    }
    echo $total_items;
}
?>