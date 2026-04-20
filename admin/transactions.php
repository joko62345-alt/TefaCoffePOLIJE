<?php
require_once '../config/config.php';
checkRole('admin');
$success = '';
$error = '';
$print_mode = isset($_GET['print']) && $_GET['print'] == '1';

// 🔹Konfirmasi Pembayaran COD
if (isset($_GET['confirm_payment']) && !$print_mode) {
    $id = (int) $_GET['confirm_payment'];
    if (mysqli_query($conn, "UPDATE transactions SET status_pembayaran='dikonfirmasi' WHERE id='$id'")) {
        $success = 'Pembayaran COD berhasil dikonfirmasi!';
    } else {
        $error = 'Gagal konfirmasi pembayaran!';
    }
}

//  Konfirmasi Produk Sudah Diambil
if (isset($_GET['confirm_pickup']) && !$print_mode) {
    $id = (int) $_GET['confirm_pickup'];
    $admin_name = $_SESSION['nama'];
    
    // Ambil kode_transaksi dulu untuk keterangan
    $trx = mysqli_fetch_assoc(mysqli_query($conn, "SELECT kode_transaksi FROM transactions WHERE id = $id"));
    $kode_transaksi = $trx['kode_transaksi'];
    
    mysqli_begin_transaction($conn);
    try {
        $stmt = $conn->prepare("UPDATE transactions SET status_pengambilan='sudah_diambil', diambil_oleh=?, tanggal_diambil=NOW() WHERE id=?");
        $stmt->bind_param("si", $admin_name, $id);
        $stmt->execute();

        $details = $conn->prepare("SELECT td.product_id, td.quantity FROM transaction_details td WHERE td.transaction_id = ?");
        $details->bind_param("i", $id);
        $details->execute();
        $result = $details->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Kurangi stok di products
            $update = $conn->prepare("UPDATE products SET stok = stok - ? WHERE id = ?");
            $update->bind_param("ii", $row['quantity'], $row['product_id']);
            $update->execute();
            
            // Catat ke stock_movements
            $keterangan = "Penjualan $kode_transaksi";
            $insert_movement = $conn->prepare("INSERT INTO stock_movements (product_id, jenis, jumlah, keterangan) VALUES (?, 'keluar', ?, ?)");
            $insert_movement->bind_param("iis", $row['product_id'], $row['quantity'], $keterangan);
            $insert_movement->execute();
        }
        mysqli_commit($conn);
        $_SESSION['success'] = "Stok diperbarui & riwayat tercatat!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Gagal: " . $e->getMessage();
    }
    header("Location: transactions.php");
    exit;
}

// 🔹 Batal Konfirmasi Pengambilan (Undo)
if (isset($_GET['undo_pickup']) && !$print_mode) {
    $id = (int) $_GET['undo_pickup'];
    if (mysqli_query($conn, "UPDATE transactions SET status_pengambilan='belum_diambil', diambil_oleh=NULL, tanggal_diambil=NULL WHERE id='$id'")) {
        $success = 'Status pengambilan dibatalkan!';
    } else {
        $error = 'Gagal batalkan status!';
    }
}

// FILTER & RIWAYAT TRANSAKSI

$filter_date_from = $_GET['filter_date_from'] ?? '';
$filter_date_to = $_GET['filter_date_to'] ?? '';
$filter_pembayaran = $_GET['filter_pembayaran'] ?? '';
$filter_pengambilan = $_GET['filter_pengambilan'] ?? '';
$filter_customer = $_GET['filter_customer'] ?? '';

$where = "1=1";
if ($filter_date_from)
    $where .= " AND DATE(t.tanggal_transaksi) >= '" . mysqli_real_escape_string($conn, $filter_date_from) . "'";
if ($filter_date_to)
    $where .= " AND DATE(t.tanggal_transaksi) <= '" . mysqli_real_escape_string($conn, $filter_date_to) . "'";
if ($filter_pembayaran)
    $where .= " AND t.metode_pembayaran = '" . mysqli_real_escape_string($conn, $filter_pembayaran) . "'";
if ($filter_pengambilan)
    $where .= " AND t.status_pengambilan = '" . mysqli_real_escape_string($conn, $filter_pengambilan) . "'";
if ($filter_customer)
    $where .= " AND (u.nama_lengkap LIKE '%" . mysqli_real_escape_string($conn, $filter_customer) . "%' OR u.telepon LIKE '%" . mysqli_real_escape_string($conn, $filter_customer) . "%')";

$transactions_query = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap, u.telepon, u.alamat
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    WHERE $where
    ORDER BY t.tanggal_transaksi DESC
    LIMIT 500
");

$all_transactions = [];
while ($row = mysqli_fetch_assoc($transactions_query)) {
    $all_transactions[] = $row;
}

// CALCULATE TOTALS FOR PRINT REPORT

$total_transaksi = count($all_transactions);
$total_pendapatan = 0;
$total_qris = 0;
$total_cod = 0;
$total_lunas = 0;
$total_pending = 0;

foreach ($all_transactions as $t) {
    if (in_array($t['status_pembayaran'], ['lunas', 'dikonfirmasi'])) {
        $total_pendapatan += $t['total_harga'];
        $total_lunas++;
    } else {
        $total_pending++;
    }
    if ($t['metode_pembayaran'] == 'qris')
        $total_qris++;
    elseif ($t['metode_pembayaran'] == 'cod')
        $total_cod++;
}

$period_text = '';
if ($filter_date_from && $filter_date_to) {
    $period_text = date('d M Y', strtotime($filter_date_from)) . ' - ' . date('d M Y', strtotime($filter_date_to));
} elseif ($filter_date_from) {
    $period_text = 'Dari ' . date('d M Y', strtotime($filter_date_from));
} elseif ($filter_date_to) {
    $period_text = 'Sampai ' . date('d M Y', strtotime($filter_date_to));
} else {
    $period_text = 'Semua Periode';
}

$pembayaran_text = '';
if ($filter_pembayaran) {
    $pembayaran_text = ' | Pembayaran: ' . strtoupper($filter_pembayaran);
}
$pengambilan_text = '';
if ($filter_pengambilan) {
    $pengambilan_text = ' | Pengambilan: ' . ucfirst(str_replace('_', ' ', $filter_pengambilan));
}
$customer_text = '';
if ($filter_customer) {
    $customer_text = ' | Customer: ' . ucfirst($filter_customer);
}

$stats_transaksi = [
    'total' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transactions"))['total'] ?? 0,
    'pendapatan' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_harga) as total FROM transactions WHERE status_pembayaran IN ('lunas','dikonfirmasi')"))['total'] ?? 0,
    'belum_diambil' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transactions WHERE status_pengambilan='belum_diambil' AND status_pembayaran IN ('lunas','dikonfirmasi')"))['total'] ?? 0,
    'sudah_diambil' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transactions WHERE status_pengambilan='sudah_diambil'"))['total'] ?? 0,
];

if ($print_mode):
    ?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Laporan Transaksi - TEFA COFFEE</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="css/receipt.css">
    </head>
    <!--  CSS Khusus Thermal Printer -->
<style>
    @media print {
        /* Sembunyikan semua elemen kecuali receipt */
        body * {
            visibility: hidden;
        }
        #receiptModal, #receiptModal * {
            visibility: visible;
        }
        #receiptModal {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
        }
        .receipt-modal-content {
            box-shadow: none !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        .receipt-modal-body {
            padding: 5px !important;
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }
        /* Ukuran kertas thermal */
        @page {
            size: 80mm auto; /* Ubah ke 58mm untuk printer 58mm */
            margin: 0;
            padding: 0;
        }
        /* Sembunyikan footer modal saat print */
        .receipt-modal-footer {
            display: none !important;
        }
        .modal-header {
            border-bottom: 1px dashed #000 !important;
            padding: 5px 0 !important;
        }
        .btn-close {
            display: none !important;
        }
        /* Pastikan teks hitam pekat */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
    
    /* Style receipt untuk tampilan layar */
    .receipt-modal-body {
        font-family: 'Courier New', monospace;
        font-size: 11px;
        line-height: 1.4;
        color: #000;
        background: #fff;
        max-width: 320px;
        margin: 0 auto;
        padding: 10px;
    }
    .receipt-modal-content {
        border-radius: 0;
        border: none;
        box-shadow: none;
    }
    .receipt-modal-header {
        border-bottom: 1px dashed #ddd;
        padding: 10px;
    }
    .receipt-modal-footer {
        border-top: 1px dashed #ddd;
        padding: 10px;
        justify-content: center;
    }
</style>

    <body>
        <div class="print-actions">
            <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> Cetak Laporan</button>
            <a href="transactions.php" class="btn-close"><i class="fas fa-times"></i> Kembali</a>
        </div>
        <div class="container">
            <div class="report-header">
                <img src="../assets/images/logopolije.png" alt="Logo" class="logo" onerror="this.style.display='none'">
                <h1>TEFA COFFEE</h1>
                <h2>LAPORAN TRANSAKSI CUSTOMER</h2>
                <div class="report-info">
                    <table>
                        <tr>
                            <td>Periode</td>
                            <td>: <?= htmlspecialchars($period_text) ?></td>
                        </tr>
                        <tr>
                            <td>Filter</td>
                            <td>: <?= htmlspecialchars($pembayaran_text . $pengambilan_text . $customer_text) ?: 'Semua' ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Total Data</td>
                            <td>: <?= $total_transaksi ?> transaksi</td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php if ($total_transaksi > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 12%;">Kode</th>
                            <th style="width: 18%;">Customer</th>
                            <th style="width: 10%;">Pembayaran</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 10%;">Diambil</th>
                            <th style="width: 15%;">Tanggal</th>
                            <th style="width: 15%;" class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($all_transactions as $t): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($t['kode_transaksi']) ?></td>
                                <td><?= htmlspecialchars($t['nama_lengkap']) ?><?php if (!empty($t['telepon'])): ?><br><small
                                            style="font-size: 9pt; color: #666;"><?= htmlspecialchars($t['telepon']) ?></small><?php endif; ?>
                                </td>
                                <td class="text-center"><?= strtoupper($t['metode_pembayaran']) ?></td>
                                <td class="text-center">
                                    <?= in_array($t['status_pembayaran'], ['lunas', 'dikonfirmasi']) ? 'Lunas' : 'Pending' ?>
                                </td>
                                <td class="text-center"><?= $t['status_pengambilan'] == 'sudah_diambil' ? 'Ya' : '-' ?></td>
                                <td><?= date('d M Y H:i', strtotime($t['tanggal_transaksi'])) ?></td>
                                <td class="text-right">Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="summary-box">
                    <h3>Ringkasan Transaksi</h3>
                    <table class="summary-table">
                        <tr>
                            <td>Total Transaksi</td>
                            <td><?= $total_transaksi ?> transaksi</td>
                        </tr>
                        <tr>
                            <td>Metode QRIS</td>
                            <td><?= $total_qris ?> transaksi</td>
                        </tr>
                        <tr>
                            <td>Metode COD</td>
                            <td><?= $total_cod ?> transaksi</td>
                        </tr>
                        <tr>
                            <td>Status Lunas</td>
                            <td><?= $total_lunas ?> transaksi</td>
                        </tr>
                        <tr>
                            <td>Status Pending</td>
                            <td><?= $total_pending ?> transaksi</td>
                        </tr>
                        <tr class="total-row">
                            <td>Total Pendapatan</td>
                            <td>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                        </tr>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Tidak Ada Data</h3>
                    <p>Tidak ada transaksi yang sesuai dengan filter yang dipilih</p>
                </div>
            <?php endif; ?>
            <div class="report-footer">
                <div class="footer-section">
                    <h4>Mengetahui,</h4>
                    <p>Kepala TEFA Coffee</p>
                    <div class="signature-line"><strong>( ___________________ )</strong></div>
                </div>
                <div class="footer-section">
                    <h4>Dibuat Oleh,</h4>
                    <p>Administrator</p>
                    <div class="signature-line"><strong>(
                            <?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['username'] ?? 'Admin') ?> )</strong></div>
                </div>
            </div>
            <div class="print-date">
                Dicetak pada: <?= date('d M Y, H:i:s') ?> WIB
            </div>
        </div>
        <script>
            window.onload = function () { window.print(); setTimeout(function () { window.location.href = 'transactions.php'; }, 1000); };
            window.onafterprint = function () { window.location.href = 'transactions.php'; };
        </script>
    </body>

    </html>
    <?php exit; ?>
<?php endif; ?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Customer - TEFA Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/transactions.css">
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-container">
                    <img src="../assets/images/logopolije.png" alt="Polije" class="logo-polije"
                        onerror="this.src='https://via.placeholder.com/42x42/2C1810/FFFFFF?text=P'">
                    <div class="logo-divider"></div>
                    <img src="../assets/images/sip.png" alt="TEFA" class="logo-tefa"
                        onerror="this.src='https://via.placeholder.com/42x42/A67C52/FFFFFF?text=T'">
                    <span class="brand-text">TEFA COFFEE</span>
                </div>
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle Menu"><i
                        class="fas fa-bars"></i></button>
            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="products.php"><i class="fas fa-box"></i><span>Kelola Produk</span></a></li>
            <li><a href="stock.php"><i class="fa-solid fa-pen-to-square"></i><span>Data Stok biji kopi</span></a></li>
            <li><a href="inventory.php"><i class="fas fa-clipboard-list"></i><span>Inventaris</span></a></li>
            <li><a href="transactions.php" class="active"><i class="fas fa-shopping-cart"></i><span>Transaksi</span></a>
            </li>
            <div class="sidebar-divider"></div>
            <li><a href="../logout.php" style="color: #ef9a9a;"><i
                        class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Monitor Transaksi Customer</h1>
            </div>

            <!-- Alert Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats_transaksi['total'] ?></div>
                        <div class="stat-label">Total Transaksi</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--leaf-dark);background:var(--leaf-pale)"><i
                            class="fas fa-dollar-sign"></i></div>
                    <div class="stat-content">
                        <div class="stat-number text-rupiah" style="color:var(--leaf-dark)">Rp
                            <?= number_format($stats_transaksi['pendapatan'], 0, ',', '.') ?></div>
                        <div class="stat-label">Total Pendapatan</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:#d97706;background:#fef3c7"><i class="fas fa-box-open"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" style="color:#d97706"><?= $stats_transaksi['belum_diambil'] ?></div>
                        <div class="stat-label">Belum Diambil</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--leaf-dark);background:var(--leaf-pale)"><i
                            class="fas fa-check-double"></i></div>
                    <div class="stat-content">
                        <div class="stat-number" style="color:var(--leaf-dark)"><?= $stats_transaksi['sudah_diambil'] ?>
                        </div>
                        <div class="stat-label">Sudah Diambil</div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="card-custom">
                <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Daftar Transaksi</span>
                    <div class="d-flex gap-2">
                        <button class="btn-custom btn-coffee btn-sm" onclick="openPrintReport()"><i
                                class="fas fa-file-pdf"></i> Cetak Laporan</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="activeFilters" class="filter-active-indicator mx-3 mt-3 mb-0">
                        <i class="fas fa-filter text-primary"></i>
                        <span class="flex-grow-1" id="activeFiltersText"></span>
                        <button class="btn btn-sm btn-secondary" onclick="resetFilters()"><i class="fas fa-times"></i>
                            Reset</button>
                    </div>
                    <form id="filterForm" method="GET" class="filter-section mx-3 mt-3 mb-0">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label small"><i
                                        class="fas fa-calendar-alt me-1 text-muted"></i>Dari</label>
                                <input type="date" name="filter_date_from" id="filter_date_from"
                                    class="form-control form-control-sm"
                                    value="<?= htmlspecialchars($filter_date_from) ?>" onchange="applyFilter()">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small"><i
                                        class="fas fa-calendar-check me-1 text-muted"></i>Sampai</label>
                                <input type="date" name="filter_date_to" id="filter_date_to"
                                    class="form-control form-control-sm"
                                    value="<?= htmlspecialchars($filter_date_to) ?>" onchange="applyFilter()">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small"><i
                                        class="fas fa-credit-card me-1 text-muted"></i>Pembayaran</label>
                                <select name="filter_pembayaran" id="filter_pembayaran"
                                    class="form-control form-control-sm" onchange="applyFilter()">
                                    <option value="">Semua</option>
                                    <option value="qris" <?= $filter_pembayaran == 'qris' ? 'selected' : '' ?>>QRIS
                                    </option>
                                    <option value="cod" <?= $filter_pembayaran == 'cod' ? 'selected' : '' ?>>COD</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small"><i
                                        class="fas fa-box me-1 text-muted"></i>Pengambilan</label>
                                <select name="filter_pengambilan" id="filter_pengambilan"
                                    class="form-control form-control-sm" onchange="applyFilter()">
                                    <option value="">Semua</option>
                                    <option value="belum_diambil" <?= $filter_pengambilan == 'belum_diambil' ? 'selected' : '' ?>>Belum Diambil</option>
                                    <option value="sudah_diambil" <?= $filter_pengambilan == 'sudah_diambil' ? 'selected' : '' ?>>Sudah Diambil</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small"><i
                                        class="fas fa-user me-1 text-muted"></i>Customer</label>
                                <input type="text" name="filter_customer" id="filter_customer"
                                    class="form-control form-control-sm"
                                    value="<?= htmlspecialchars($filter_customer) ?>" placeholder="Nama/Telepon"
                                    onchange="applyFilter()">
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Pembayaran</th>
                                    <th>Status Bayar</th>
                                    <th>Status Ambil</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($all_transactions) > 0): ?>
                                    <?php foreach (array_slice($all_transactions, 0, 10) as $t): ?>
                                        <tr
                                            class="<?= $t['status_pengambilan'] == 'belum_diambil' && $t['status_pembayaran'] != 'pending' ? 'pending-pickup' : '' ?>">
                                            <td data-label="Kode"><strong><?= htmlspecialchars($t['kode_transaksi']) ?></strong>
                                            </td>
                                            <td data-label="Customer"><span
                                                    class="fw-semibold"><?= htmlspecialchars($t['nama_lengkap']) ?></span><br><small
                                                    class="text-muted"><?= htmlspecialchars($t['telepon']) ?></small></td>
                                            <td data-label="Total" class="text-rupiah"><strong>Rp
                                                    <?= number_format($t['total_harga'], 0, ',', '.') ?></strong></td>
                                            <td data-label="Pembayaran">
                                                <?php if ($t['metode_pembayaran'] == 'qris'): ?>
                                                    <span class="badge-custom badge-primary"><i
                                                            class="fas fa-qrcode"></i>QRIS</span>
                                                <?php else: ?>
                                                    <span class="badge-custom badge-cod"><i class="fas fa-money-bill"></i>COD</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Status Bayar">
                                                <?php if ($t['status_pembayaran'] == 'lunas'): ?>
                                                    <span class="badge-custom badge-success"><i
                                                            class="fas fa-check"></i>Lunas</span>
                                                <?php elseif ($t['status_pembayaran'] == 'dikonfirmasi'): ?>
                                                    <span class="badge-custom badge-info"><i class="fas fa-check"></i>Lunas</span>
                                                <?php else: ?>
                                                    <span class="badge-custom badge-warning"><i
                                                            class="fas fa-hourglass"></i>Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Status Ambil">
                                                <?php if ($t['status_pengambilan'] == 'sudah_diambil'): ?>
                                                    <span class="badge-custom badge-success"><i
                                                            class="fas fa-check-double"></i>Sudah</span><br><small
                                                        class="text-muted"><?= date('d/m H:i', strtotime($t['tanggal_diambil'])) ?></small>
                                                <?php else: ?>
                                                    <span class="badge-custom badge-warning"><i
                                                            class="fas fa-clock"></i>Belum</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Tanggal">
                                                <?= date('d/m/Y H:i', strtotime($t['tanggal_transaksi'])) ?></td>
                                            <td data-label="Aksi">
                                                <!-- lihat struk -->
                                                <button class="action-btn receipt"
                                                    onclick="viewReceipt('<?= htmlspecialchars($t['kode_transaksi']) ?>')"
                                                    title="Lihat Struk"><i class="fas fa-receipt"></i></button>
                                                <button class="action-btn view" onclick="viewDetail(<?= $t['id'] ?>)"
                                                    title="Detail"><i class="fas fa-eye"></i></button>
                                                <?php if ($t['metode_pembayaran'] == 'cod' && $t['status_pembayaran'] == 'pending'): ?>
                                                    <a href="?confirm_payment=<?= $t['id'] ?>" class="action-btn confirm"
                                                        onclick="return confirm('Konfirmasi pembayaran COD sudah diterima?')"
                                                        title="Konfirmasi Pembayaran"><i class="fas fa-money-check-alt"></i></a>
                                                <?php endif; ?>
                                                <?php if ($t['status_pengambilan'] == 'belum_diambil' && in_array($t['status_pembayaran'], ['lunas', 'dikonfirmasi'])): ?>
                                                    <a href="?confirm_pickup=<?= $t['id'] ?>" class="action-btn pickup"
                                                        onclick="return confirm('Konfirmasi produk sudah diambil customer?')"
                                                        title="Konfirmasi Diambil"><i class="fas fa-box-open"></i></a>
                                                <?php endif; ?>
                                                <?php if ($t['status_pengambilan'] == 'sudah_diambil'): ?>
                                                    <a href="?undo_pickup=<?= $t['id'] ?>" class="action-btn undo"
                                                        onclick="return confirm('Batalkan status pengambilan?')" title="Undo"><i
                                                            class="fas fa-undo"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted"><i
                                                class="fas fa-inbox me-2"></i>Belum ada transaksi</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($all_transactions) > 10): ?>
                        <div class="text-center mt-3"><small class="text-muted">Menampilkan 10 dari
                                <?= count($all_transactions) ?> data</small></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<!-- Detail Modal - Flat Clean -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-receipt"></i>
                    <span id="modalTitle">Detail Transaksi</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="modal-loading">
                    <i class="fas fa-circle-notch fa-spin"></i>
                    <p>Memuat detail...</p>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content receipt-modal-content">
                <div class="modal-header receipt-modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        style="position: absolute; right: 10px; top: 10px; z-index: 1000;"></button>
                </div>
                <div class="modal-body receipt-modal-body p-0">
                    <div
                        style="text-align: center; margin-bottom: 15px; border-bottom: 1px dashed #000; padding-bottom: 15px;">
                        <img src="../assets/images/logopolije.png" alt="Polije"
                            style="width: 60px; height: 60px; margin-bottom: 10px;" onerror="this.style.display='none'">
                        <div style="font-weight: bold; font-size: 14px; margin: 5px 0;">TEFA COFFEE</div>
                        <div style="font-size: 9px; line-height: 1.4;">
                            Politeknik Negeri Jember<br>
                            Jl. Mastrip, Kotak Pos 164<br>
                            Jember 68101, Jawa Timur<br>
                            <i class="fas fa-phone"></i> 0812-3456-7890
                        </div>
                    </div>
                    <div style="border: 1px solid #000; padding: 5px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 10px; text-transform: uppercase;"
                        id="receiptStatus">PENDING</div>
                    <div style="font-size: 10px; margin-bottom: 15px; line-height: 1.6;">
                        <div style="display: flex; justify-content: space-between;"><span
                                style="font-weight: bold;">No:</span><span style="font-family: monospace;"
                                id="receiptKode"></span></div>
                        <div style="display: flex; justify-content: space-between;"><span
                                style="font-weight: bold;">Tgl:</span><span id="receiptTanggal"></span></div>
                        <div style="display: flex; justify-content: space-between;"><span
                                style="font-weight: bold;">Nama:</span><span id="receiptNama"></span></div>
                        <div style="display: flex; justify-content: space-between;"><span
                                style="font-weight: bold;">Telp:</span><span id="receiptTelepon"></span></div>
                    </div>
                    <div style="border: 1px solid #000; padding: 8px; margin-bottom: 15px; font-size: 9px;">
                        <div style="text-align: center; font-weight: bold; margin-bottom: 5px;"> PENGAMBILAN PRODUK</div>
                        <div id="receiptPickupStatus" style="text-align: center; font-weight: bold;"></div>
                        <div style="text-align: center; margin-top: 3px; font-size: 8px;" id="receiptPickupInfo"></div>
                    </div>
                    <div
                        style="border-top: 2px solid #000; border-bottom: 1px dashed #000; padding: 5px 0; text-align: center; font-weight: bold; font-size: 10px; margin-bottom: 10px;">
                        ITEM PESANAN</div>
                    <div id="receiptItems" style="font-size: 9px; margin-bottom: 15px; line-height: 1.8;"></div>
                    <div style="border-top: 2px solid #000; padding-top: 8px; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 12px;">
                            <span>TOTAL</span><span id="receiptTotal"></span></div>
                    </div>
                    
                    <div
                        style="text-align: center; border-top: 2px solid #000; padding-top: 10px; font-size: 9px; line-height: 1.6;">
                        <div style="font-weight: bold; margin-bottom: 5px;">*** TERIMA KASIH ***</div>
                        <div>Dukungan Anda membantu</div>
                        <div>mahasiswa Politeknik Jember</div>
                        <div style="font-weight: bold; margin-top: 5px;">Struk ini sah tanpa tanda tangan</div>
                        <div style="margin-top: 5px; font-size: 8px;" id="receiptTimestamp"></div>
                    </div>
                </div>
                
            <div class="modal-footer receipt-modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="margin-right: 5px;">
                    <i class="fas fa-times"></i> Tutup
                </button>
                <button type="button" class="btn btn-sm btn-thermal" onclick="printModalReceipt()" 
                        style="background: #2C1810; color: #fff; border: none; padding: 8px 15px;">
                    <i class="fas fa-print"></i> Cetak Thermal
                </button>
            </div>
                        </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/transactions.js"></script>
</body>

</html>