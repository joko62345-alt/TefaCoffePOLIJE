<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$db   = "tefa_coffee";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Base URL
define('BASE_URL', 'http://localhost/tefa_coffee/');

// Fungsi redirect
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

// Cek login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Cek role
function checkRole($roles) {
    if (!isLoggedIn()) {
        redirect('customer/login.php');
    }
    if (!in_array($_SESSION['role'], (array)$roles)) {
        redirect('index.php');
    }
}
?>