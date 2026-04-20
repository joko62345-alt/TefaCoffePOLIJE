<?php
require_once '../config/config.php';
checkRole('admin');
$success = '';
$print_mode = isset($_GET['print']) && $_GET['print'] == '1';

// Handle Stock Movement Biji Kopi
if (isset($_POST['add_stock']) && !$print_mode) {
    $nama_biji_kopi = mysqli_real_escape_string($conn, $_POST['nama_biji_kopi']);
    $jenis = $_POST['jenis'];
    $jumlah = $_POST['jumlah'];
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

    // Cari atau buat coffee bean berdasarkan nama
    $check_bean = mysqli_query($conn, "SELECT id FROM coffee_beans WHERE LOWER(nama_biji_kopi) = LOWER('$nama_biji_kopi')");
    if (mysqli_num_rows($check_bean) > 0) {
        $bean = mysqli_fetch_assoc($check_bean);
        $bean_id = $bean['id'];
    } else {
        mysqli_query($conn, "INSERT INTO coffee_beans (nama_biji_kopi, stok) VALUES ('$nama_biji_kopi', 0)");
        $bean_id = mysqli_insert_id($conn);
    }

    // Insert movement ke tabel KHUSUS stock_movements_beans
    $query = "INSERT INTO stock_movements_beans (bean_id, jenis, jumlah, keterangan)
              VALUES ('$bean_id', '$jenis', '$jumlah', '$keterangan')";
    mysqli_query($conn, $query);

    // Update stok
    $bean = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stok FROM coffee_beans WHERE id='$bean_id'"));
    $new_stock = ($jenis == 'masuk') ? $bean['stok'] + $jumlah : $bean['stok'] - $jumlah;
    mysqli_query($conn, "UPDATE coffee_beans SET stok='$new_stock' WHERE id='$bean_id'");

    $success = 'Stok biji kopi berhasil diupdate!';
}


// FILTER & RIWAYAT STOCK

$filter_bean = $_GET['filter_bean'] ?? '';
$filter_jenis = $_GET['filter_jenis'] ?? '';
$filter_date_from = $_GET['filter_date_from'] ?? '';
$filter_date_to = $_GET['filter_date_to'] ?? '';

$where = "1=1";
if ($filter_bean)
    $where .= " AND sm.bean_id = " . (int) $filter_bean;
if ($filter_jenis)
    $where .= " AND sm.jenis = '" . mysqli_real_escape_string($conn, $filter_jenis) . "'";
if ($filter_date_from)
    $where .= " AND DATE(sm.tanggal) >= '" . mysqli_real_escape_string($conn, $filter_date_from) . "'";
if ($filter_date_to)
    $where .= " AND DATE(sm.tanggal) <= '" . mysqli_real_escape_string($conn, $filter_date_to) . "'";

$movements_query = mysqli_query($conn, "
    SELECT sm.*, cb.nama_biji_kopi
    FROM stock_movements_beans sm
    JOIN coffee_beans cb ON sm.bean_id = cb.id
    WHERE $where
    ORDER BY sm.tanggal DESC
    LIMIT 500
");

$all_movements = [];
while ($row = mysqli_fetch_assoc($movements_query)) {
    $all_movements[] = $row;
}

// CALCULATE TOTALS FOR PRINT REPORT

$total_masuk = 0;
$total_keluar = 0;
foreach ($all_movements as $m) {
    if ($m['jenis'] == 'masuk') {
        $total_masuk += $m['jumlah'];
    } else {
        $total_keluar += $m['jumlah'];
    }
}

$bean_name = 'Semua Biji Kopi';
if ($filter_bean) {
    $b = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_biji_kopi FROM coffee_beans WHERE id = " . (int) $filter_bean));
    if ($b) {
        $bean_name = $b['nama_biji_kopi'];
    }
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

$jenis_text = '';
if ($filter_jenis) {
    $jenis_text = ' | Jenis: ' . ucfirst($filter_jenis);
}

$coffee_beans = mysqli_query($conn, "SELECT * FROM coffee_beans ORDER BY nama_biji_kopi ASC");
$coffee_beans_list = mysqli_query($conn, "SELECT id, nama_biji_kopi FROM coffee_beans ORDER BY nama_biji_kopi ASC");

if ($print_mode):
    ?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Laporan Stok Biji Kopi - TEFA COFFEE</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="css/receipt.css">
    </head>

    <body>
        <div class="print-actions">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Cetak Laporan
            </button>
            <a href="stock.php" class="btn-close">
                <i class="fas fa-times"></i> Kembali
            </a>
        </div>
        <div class="container">
            <div class="report-header">
                <img src="../assets/images/logopolije.png" alt="Logo" class="logo" onerror="this.style.display='none'">
                <h1>TEFA COFFEE</h1>
                <h2>LAPORAN STOK BIJI KOPI</h2>
                <div class="report-info">
                    <table>
                        <tr>
                            <td>Biji Kopi</td>
                            <td>: <?= htmlspecialchars($bean_name) ?></td>
                        </tr>
                        <tr>
                            <td>Periode</td>
                            <td>: <?= htmlspecialchars($period_text) ?><?= htmlspecialchars($jenis_text) ?></td>
                        </tr>
                        <tr>
                            <td>Total Data</td>
                            <td>: <?= count($all_movements) ?> movement</td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php if (count($all_movements) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 18%;">Tanggal</th>
                            <th style="width: 30%;">Biji Kopi</th>
                            <th style="width: 12%;">Jenis</th>
                            <th style="width: 15%;">Jumlah</th>
                            <th style="width: 20%;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($all_movements as $m): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= date('d M Y H:i', strtotime($m['tanggal'])) ?></td>
                                <td>
                                    <?= htmlspecialchars($m['nama_biji_kopi']) ?>
                                    <?php if (!empty($m['varietas']) || !empty($m['asal'])): ?>
                                        <br><small style="font-size: 9pt; color: #666;">
                                            <?= !empty($m['varietas']) ? htmlspecialchars($m['varietas']) : '' ?>
                                            <?= !empty($m['asal']) ? ' • ' . htmlspecialchars($m['asal']) : '' ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= ucfirst($m['jenis']) ?></td>
                                <td class="text-right">
                                    <?= $m['jenis'] == 'masuk' ? '+' : '-' ?>            <?= number_format($m['jumlah'], 1, ',', '.') ?> kg
                                </td>
                                <td><?= htmlspecialchars($m['keterangan'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="summary-box">
                    <h3><i class="fas fa-chart-bar"></i> Ringkasan Stok Biji Kopi</h3>
                    <table class="summary-table">
                        <tr>
                            <td>Total Stok Masuk</td>
                            <td>+<?= number_format($total_masuk, 1, ',', '.') ?> kg</td>
                        </tr>
                        <tr>
                            <td>Total Stok Keluar</td>
                            <td>-<?= number_format($total_keluar, 1, ',', '.') ?> kg</td>
                        </tr>
                        <tr class="total-row">
                            <td>Netto Perubahan</td>
                            <td><?= ($total_masuk - $total_keluar) >= 0 ? '+' : '' ?><?= number_format($total_masuk - $total_keluar, 1, ',', '.') ?>
                                kg</td>
                        </tr>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Tidak Ada Data</h3>
                    <p>Tidak ada riwayat movement yang sesuai dengan filter yang dipilih</p>
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
            window.onload = function () {
                window.print();
                setTimeout(function () { window.location.href = 'stock.php'; }, 1000);
            };
            window.onafterprint = function () { window.location.href = 'stock.php'; };
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
    <title>Stok Biji Kopi - TEFA Coffee</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/stock.css">
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
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle Menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="products.php"><i class="fas fa-box"></i><span>Kelola Produk</span></a></li>
            <li><a href="stock.php" class="active"><i class="fa-solid fa-pen-to-square"></i><span>Data Stok biji kopi</span></a>
            </li>
            <li><a href="inventory.php"><i class="fas fa-clipboard-list"></i><span>Inventaris</span></a></li>
            <li><a href="transactions.php"><i class="fas fa-shopping-cart"></i><span>Transaksi</span></a></li>
            <div class="sidebar-divider"></div>
            <li><a href="../logout.php" style="color: #ef9a9a;"><i
                        class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-wrapper">
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Manajemen Stok Biji Kopi
                </h1>
            </div>

            <!-- Alert -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Form Input Stok Biji Kopi -->
                <div class="col-md-4 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <span>Input Stok Biji Kopi</span>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Jenis Biji Kopi</label>
                                    <input type="text" name="nama_biji_kopi" class="form-control" required
                                        placeholder="Contoh: Arabica Gayo">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jenis Movement</label>
                                    <select name="jenis" class="form-control" required>
                                        <option value="masuk">Stok Masuk</option>
                                        <option value="keluar">Stok Keluar</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jumlah (kg)</label>
                                    <input type="number" name="jumlah" class="form-control" step="0.1" min="0.1"
                                        required placeholder="0.0">
                                    <small class="form-text">Masukkan jumlah dalam kilogram (kg)</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Keterangan</label>
                                    <textarea name="keterangan" class="form-control" rows="3"
                                        placeholder="Keterangan tambahan..."></textarea>
                                </div>
                                <button type="submit" name="add_stock" class="btn-custom btn-primary w-100">
                                    <i class="fas fa-save"></i>Simpan Movement Stok
                                </button>
                            </form>
                        </div>
                    </div>
                    <!-- Info Stok Saat Ini -->
                    <div class="card-custom mt-3">
                        <div class="card-header-custom">
                            <span>Info Stok Tersedia</span>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0" style="max-height: 200px; overflow-y: auto;">
                                <?php
                                if ($coffee_beans && mysqli_num_rows($coffee_beans) > 0):
                                    mysqli_data_seek($coffee_beans, 0);
                                    while ($bean = mysqli_fetch_assoc($coffee_beans)):
                                        ?>
                                        <li class="d-flex justify-content-between py-2 border-bottom">
                                            <span class="fw-semibold"><?= htmlspecialchars($bean['nama_biji_kopi']) ?></span>
                                            <span
                                                class="badge-custom <?= $bean['stok'] < 5 ? 'badge-danger' : 'badge-success' ?>">
                                                <?= number_format($bean['stok'], 1) ?> kg
                                            </span>
                                        </li>
                                        <?php
                                    endwhile;
                                else:
                                    ?>
                                    <li class="text-muted py-2">Belum ada data biji kopi</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Stok Biji Kopi -->
                <div class="col-md-8">
                    <div class="card-custom">
                        <div
                            class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span>Riwayat Stok Biji Kopi</span>
                            <div class="d-flex gap-2">
                                <button class="btn-custom btn-coffee btn-sm" onclick="openPrintReport()">
                                    <i class="fas fa-file-pdf"></i> Cetak Laporan
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <!-- Active Filters Indicator -->
                            <div id="activeFilters" class="filter-active-indicator mx-3 mt-3 mb-0">
                                <i class="fas fa-filter text-primary"></i>
                                <span class="flex-grow-1" id="activeFiltersText"></span>
                                <button class="btn btn-sm btn-secondary" onclick="resetFilters()">
                                    <i class="fas fa-times"></i> Reset
                                </button>
                            </div>
                            <!-- Filter Form -->
                            <form id="filterForm" method="GET" class="filter-section mx-3 mt-3 mb-0">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small"><i
                                                class="fas fa-seedling me-1 text-muted"></i>Filter Biji Kopi</label>
                                        <select name="filter_bean" id="filter_bean" class="form-control form-control-sm"
                                            onchange="applyFilter()">
                                            <option value="">Semua Biji Kopi</option>
                                            <?php
                                            if ($coffee_beans_list) {
                                                mysqli_data_seek($coffee_beans_list, 0);
                                                while ($b = mysqli_fetch_assoc($coffee_beans_list)):
                                                    ?>
                                                    <option value="<?= $b['id'] ?>" <?= $filter_bean == $b['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($b['nama_biji_kopi']) ?>
                                                    </option>
                                                    <?php
                                                endwhile;
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small"><i
                                                class="fas fa-exchange-alt me-1 text-muted"></i>Jenis</label>
                                        <select name="filter_jenis" id="filter_jenis"
                                            class="form-control form-control-sm" onchange="applyFilter()">
                                            <option value="">Semua</option>
                                            <option value="masuk" <?= $filter_jenis == 'masuk' ? 'selected' : '' ?>>Masuk
                                            </option>
                                            <option value="keluar" <?= $filter_jenis == 'keluar' ? 'selected' : '' ?>>
                                                Keluar</option>
                                        </select>
                                    </div>
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
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Biji Kopi</th>
                                            <th>Jenis</th>
                                            <th>Jumlah</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historyTableBody">
                                        <?php
                                        if (count($all_movements) > 0):
                                            foreach (array_slice($all_movements, 0, 10) as $m):
                                                ?>
                                                <tr>
                                                    <td data-label="Tanggal"><?= date('d/m/Y H:i', strtotime($m['tanggal'])) ?>
                                                    </td>
                                                    <td data-label="Biji Kopi">
                                                        <div class="fw-semibold"><?= htmlspecialchars($m['nama_biji_kopi']) ?>
                                                        </div>
                                                        <?php if (!empty($m['varietas']) || !empty($m['asal'])): ?>
                                                            <small class="bean-info">
                                                                <?= !empty($m['varietas']) ? htmlspecialchars($m['varietas']) : '' ?>
                                                                <?= !empty($m['asal']) ? '• ' . htmlspecialchars($m['asal']) : '' ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td data-label="Jenis">
                                                        <span
                                                            class="badge-custom <?= $m['jenis'] == 'masuk' ? 'badge-success' : 'badge-danger' ?>">
                                                            <i
                                                                class="fas fa-<?= $m['jenis'] == 'masuk' ? 'arrow-down' : 'arrow-up' ?>"></i>
                                                            <?= strtoupper($m['jenis']) ?>
                                                        </span>
                                                    </td>
                                                    <td data-label="Jumlah" class="fw-semibold">
                                                        <?= number_format($m['jumlah'], 1) ?> kg</td>
                                                    <td data-label="Keterangan" class="text-muted">
                                                        <?= htmlspecialchars($m['keterangan']) ?: '-' ?></td>
                                                </tr>
                                                <?php
                                            endforeach;
                                        else:
                                            ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">
                                                    </i>Belum ada riwayat movement stok
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($all_movements) > 10): ?>
                                <div class="text-center mt-3">
                                    <small class="text-muted">Menampilkan 10 dari <?= count($all_movements) ?> data</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/stock.js"></script>
</body>
</html>