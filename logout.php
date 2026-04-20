<?php
session_start();

// Simpan role sebelum destroy
$role = $_SESSION['role'] ?? '';

// Hancurkan session
session_unset();
session_destroy();

// Hapus cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect berdasarkan role
if ($role == 'admin' || $role == 'manager') {
    header("Location: admin/login.php");
} else {
    header("Location: index.php");
}
exit();
?>