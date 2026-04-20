<?php
require_once '../config/config.php';
checkRole('admin');

// ✅ Status lunas: 'lunas' DAN 'dikonfirmasi' (agar sinkron dengan transactions.php)
$status_lunas_clause = "IN ('lunas', 'dikonfirmasi')";

// Total Produk
$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"))['total'] ?? 0;

// Total Transaksi (semua status)
$total_transaksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transactions"))['total'] ?? 0;

// ✅ Total Pendapatan: Hanya transaksi lunas/dikonfirmasi
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(total_harga) as total 
    FROM transactions 
    WHERE status_pembayaran $status_lunas_clause
"))['total'] ?? 0;

// Stok Rendah (< 20)
$stok_rendah = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM products 
    WHERE stok < 20
"))['total'] ?? 0;

// Transaksi Pending (belum lunas)
$transaksi_pending = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM transactions 
    WHERE status_pembayaran = 'pending'
"))['total'] ?? 0;

// Transaksi Belum Diambil (sudah lunas tapi belum diambil)
$belum_diambil = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM transactions 
    WHERE status_pengambilan = 'belum_diambil' 
    AND status_pembayaran $status_lunas_clause
"))['total'] ?? 0;

// TRANSAKSI TERBARU (5 DATA)
$recent_transactions = [];
$query = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap, u.telepon 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.tanggal_transaksi DESC 
    LIMIT 5
");
while ($row = mysqli_fetch_assoc($query)) {
    $recent_transactions[] = $row;
}

//  PRODUK STOK MENIPIS (5 DATA)
$low_stock_products = [];
$query = mysqli_query($conn, "
    SELECT * FROM products 
    WHERE stok < 20 
    ORDER BY stok ASC 
    LIMIT 5
");
while ($row = mysqli_fetch_assoc($query)) {
    $low_stock_products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - TEFA Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
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
                <!-- 🆕 Hamburger Menu - RIGHT SIDE -->
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle Menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" class="active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="products.php">
                    <i class="fas fa-box"></i>
                    <span>Kelola Produk</span>
                </a>
            </li>
            <li>
                <a href="stock.php">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Data Stok biji kopi</span>
                </a>
            </li>
            <li>
                <a href="inventory.php">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Inventaris</span>
                </a>
            </li>
            <li>
                <a href="transactions.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Transaksi</span>
                </a>
            </li>
            <div class="sidebar-divider"></div>
            <li>
                <a href="../logout.php" style="color: #ef9a9a;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    Panel Dashboard Admin
                </h1>
            </div>
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <!-- Total Produk -->
                <div class="stat-card" onclick="window.location.href='products.php'">
                    <div class="stat-icon"><i class="fas fa-box"></i></div>
                    <div class="stat-content">
                        <div class="stat-number"><?= (int) $total_produk ?></div>
                        <div class="stat-label">Total Produk</div>
                    </div>
                </div>
                <!-- Total Transaksi -->
                <div class="stat-card" onclick="window.location.href='transactions.php'">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-content">
                        <div class="stat-number"><?= (int) $total_transaksi ?></div>
                        <div class="stat-label">Total Transaksi</div>
                    </div>
                </div>
                <!-- Total Pendapatan -->
                <div class="stat-card" onclick="window.location.href='transactions.php'">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-content">
                        <div class="stat-number text-rupiah">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?>
                        </div>
                        <div class="stat-label">Total Pendapatan</div>
                    </div>
                </div>
                <!-- Stok Rendah -->
                <div class="stat-card" onclick="window.location.href='products.php'">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-content">
                        <div class="stat-number" style="color: var(--danger-text);"><?= (int) $stok_rendah ?></div>
                        <div class="stat-label">Stok Rendah</div>
                    </div>
                </div>
                <!-- Transaksi Pending -->
                <div class="stat-card" onclick="window.location.href='transactions.php?filter_pembayaran=pending'">
                    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="stat-content">
                        <div class="stat-number" style="color: #c2410c;"><?= (int) $transaksi_pending ?></div>
                        <div class="stat-label">Pending Payment</div>
                    </div>
                </div>
                <!-- Belum Diambil -->
                <div class="stat-card"
                    onclick="window.location.href='transactions.php?filter_pengambilan=belum_diambil'">
                    <div class="stat-icon"><i class="fas fa-box-open"></i></div>
                    <div class="stat-content">
                        <div class="stat-number" style="color: #9333ea;"><?= (int) $belum_diambil ?></div>
                        <div class="stat-label">Belum Diambil</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- Recent Transactions -->
                <div class="col-lg-8 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <span>Transaksi Terbaru</span>
                            <a href="transactions.php" class="btn-custom btn-secondary btn-sm"
                                style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.625rem 1.25rem;border-radius:8px;font-weight:500;font-size:0.9rem;cursor:pointer;transition:all 0.15s ease;border:none;text-decoration:none;background:#f3f4f6;color:var(--text-primary);">
                                Lihat Semua <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Customer</th>
                                            <th>Total</th>
                                            <th>Metode</th>
                                            <th>Status</th>
                                            <th>Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($recent_transactions) > 0): ?>
                                            <?php foreach ($recent_transactions as $t): ?>
                                                <tr>
                                                    <td data-label="Kode" class="fw-semibold">
                                                        <?= htmlspecialchars($t['kode_transaksi']) ?>
                                                    </td>
                                                    <td data-label="Customer">
                                                        <span
                                                            class="fw-semibold"><?= htmlspecialchars($t['nama_lengkap']) ?></span>
                                                        <?php if (!empty($t['telepon'])): ?>
                                                            <br><small class="text-muted"
                                                                style="font-size:0.78rem;"><?= htmlspecialchars($t['telepon']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td data-label="Total" class="text-rupiah">
                                                        <strong>Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></strong>
                                                    </td>
                                                    <td data-label="Metode">
                                                        <?php if ($t['metode_pembayaran'] == 'qris'): ?>
                                                            <span class="badge-custom badge-primary">
                                                                <i class="fas fa-qrcode"></i> QRIS
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge-custom badge-warning">
                                                                <i class="fas fa-money-bill"></i> COD
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td data-label="Status">
                                                        <?php if (in_array($t['status_pembayaran'], ['lunas', 'dikonfirmasi'])): ?>
                                                            <span class="badge-custom badge-success">
                                                                <i class="fas fa-check"></i> Lunas
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge-custom badge-warning">
                                                                <i class="fas fa-hourglass"></i> Pending
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td data-label="Tanggal">
                                                        <?= date('d/m H:i', strtotime($t['tanggal_transaksi'])) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <div class="empty-state">
                                                        <i class="fas fa-inbox"></i>
                                                        <p class="mb-0">Belum ada transaksi</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Low Stock Alert + Quick Actions -->
                <div class="col-lg-4">
                    <!-- Stok Menipis -->
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <span>Stok Menipis</span>
                            <a href="products.php?filter=low_stock" class="btn-custom btn-secondary btn-sm"
                                style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.625rem 1.25rem;border-radius:8px;font-weight:500;font-size:0.9rem;cursor:pointer;transition:all 0.15s ease;border:none;text-decoration:none;background:#f3f4f6;color:var(--text-primary);">
                                Kelola <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (count($low_stock_products) > 0): ?>
                                <?php foreach ($low_stock_products as $p): ?>
                                    <div class="stock-alert <?= $p['stok'] <= 5 ? 'critical' : '' ?>">
                                        <i class="fas fa-box <?= $p['stok'] <= 5 ? 'text-danger' : 'text-warning' ?>"></i>
                                        <div style="flex: 1;">
                                            <div class="product-name"><?= htmlspecialchars($p['nama_produk']) ?></div>
                                            <small class="text-muted"
                                                style="font-size:0.78rem;"><?= htmlspecialchars($p['kategori']) ?></small>
                                        </div>
                                        <div class="stock-value"><?= (int) $p['stok'] ?> unit</div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-circle"></i>
                                    <p class="mb-0">Semua stok aman </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <div class="quick-actions-title">
                            <span>Aksi Cepat</span>
                        </div>
                        <div class="quick-actions-list">
                            <a href="products.php?action=add" class="quick-action-btn primary">
                                <i class="fas fa-plus-circle"></i>
                                <span>Tambah Produk Baru</span>
                            </a>
                            <a href="stock.php" class="quick-action-btn secondary">
                                <i class="fa-solid fa-pen-to-square"></i>
                                <span>Input Stok Masuk/keluar</span>
                            </a>
                            <a href="transactions.php?filter_pembayaran=pending" class="quick-action-btn warning">
                                <i class="fas fa-money-check-alt"></i>
                                <span>Konfirmasi pesanan</span>
                                <?php if ($transaksi_pending > 0): ?>
                                    <span class="badge"><?= $transaksi_pending ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard.js"></script>
</body>

</html>