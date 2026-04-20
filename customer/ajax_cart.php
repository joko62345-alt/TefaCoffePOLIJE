<?php
session_start();

if (isset($_POST['cart'])) {
    $_SESSION['cart'] = json_decode($_POST['cart'], true);
    echo json_encode(['status' => 'success']);
}
?>