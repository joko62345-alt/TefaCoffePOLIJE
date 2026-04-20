<?php
require_once '../config/config.php';
checkRole('admin');

if (isset($_POST['confirm_transaction'])) {
    $trans_id = (int)$_POST['transaction_id'];
    $action = $_POST['action']; // 'konfirmasi' atau 'ambil'

    // 1. Update status transaksi
    if ($action == 'ambil') {
        // Admin klik "Sudah Diambil & Lunas"
        $stmt = $conn->prepare("
            UPDATE transactions 
            SET status_pembayaran = 'lunas', 
                status_pengambilan = 'sudah_diambil', 
                tanggal_diambil = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $trans_id);
        $stmt->execute();

        // 2. BARU KURANGI STOK DI SINI
        $details = $conn->prepare("SELECT product_id, quantity FROM transaction_details WHERE transaction_id = ?");
        $details->bind_param("i", $trans_id);
        $details->execute();
        $result = $details->get_result();

        while ($row = $result->fetch_assoc()) {
            $pid = $row['product_id'];
            $qty = $row['quantity'];
            
            // Kurangi stok dengan prepared statement
            $update = $conn->prepare("UPDATE products SET stok = stok - ? WHERE id = ? AND stok >= ?");
            $update->bind_param("iii", $qty, $pid, $qty);
            $update->execute();
        }

        $_SESSION['msg'] = "Transaksi dikonfirmasi & stok diperbarui!";
        
    } elseif ($action == 'konfirmasi') {
        // Admin hanya konfirmasi pembayaran (belum diambil)
        $stmt = $conn->prepare("UPDATE transactions SET status_pembayaran = 'lunas' WHERE id = ?");
        $stmt->bind_param("i", $trans_id);
        $stmt->execute();
        $_SESSION['msg'] = "Pembayaran dikonfirmasi!";
    }

    redirect('transactions.php');
}
?>