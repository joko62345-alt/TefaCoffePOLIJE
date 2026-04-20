<?php
require_once '../config/config.php';
checkRole('customer');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$order_id = mysqli_real_escape_string($conn, $_POST['order_id'] ?? '');
$status = mysqli_real_escape_string($conn, $_POST['status'] ?? '');
$user_id = $_SESSION['user_id'];

if (empty($order_id) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

// Verify transaction belongs to this user
$check = mysqli_query($conn, "SELECT id FROM transactions WHERE kode_transaksi='$order_id' AND user_id='$user_id'");
if (mysqli_num_rows($check) == 0) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit();
}

// Update payment status
if ($status == 'lunas') {
    $query = "UPDATE transactions SET status_pembayaran='lunas' WHERE kode_transaksi='$order_id'";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Payment status updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
}
?>