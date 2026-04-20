<?php
require_once '../config/config.php';
checkRole('customer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('customer/dashboard.php');
}

$user_id = $_SESSION['user_id'];
$cart_data = json_decode($_POST['cart_data'], true);
$payment_method = $_POST['payment_method'];

if (empty($cart_data)) {
    redirect('customer/dashboard.php');
}

// Generate transaction code
$kode_transaksi = 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

// Calculate total
$total_harga = 0;
foreach ($cart_data as $item) {
    $total_harga += $item['price'] * $item['qty'];
}

// Insert transaction
$status = ($payment_method == 'qris') ? 'lunas' : 'pending';

$query = "INSERT INTO transactions (user_id, kode_transaksi, total_harga, metode_pembayaran, status_pembayaran, status_pengambilan) 
          VALUES ('$user_id', '$kode_transaksi', '$total_harga', '$payment_method', '$status', 'belum_diambil')";

if (mysqli_query($conn, $query)) {
    $transaction_id = mysqli_insert_id($conn);

    // Insert details ONLY - JANGAN UPDATE STOK DI SINI
    foreach ($cart_data as $item) {
        mysqli_query($conn, "
            INSERT INTO transaction_details (transaction_id, product_id, quantity, harga_satuan, subtotal) 
            VALUES ('$transaction_id', {$item['id']}, {$item['qty']}, {$item['price']}, " . ($item['price'] * $item['qty']) . ")
        ");

        // STOK TETAP - Tidak dikurangi saat checkout
    }

    unset($_SESSION['cart']);

    // Redirect
    if ($payment_method == 'qris') {
        redirect("customer/qris_payment.php?kode=$kode_transaksi&amount=$total_harga");
    } else {
        redirect("customer/cod_confirm.php?kode=$kode_transaksi");
    }
} else {
    die("Error: " . mysqli_error($conn));
}
?>