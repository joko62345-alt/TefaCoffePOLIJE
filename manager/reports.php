<?php
require_once '../config/config.php';
checkRole('manager');
$success = '';
// ============================================
// 🖨️ DETEKSI MODE CETAK (PRINT MODE)
// ============================================
$print_mode = isset($_GET['print']) && $_GET['print'] == '1';
// Date filter
$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-d');
// Report type filter - 4 types (beans removed)
$report_type = $_GET['type'] ?? 'sales';

// ========== SALES REPORT QUERIES ==========
$transactions = mysqli_query($conn, "
SELECT t.*, u.nama_lengkap
FROM transactions t
JOIN users u ON t.user_id = u.id
WHERE DATE(t.tanggal_transaksi) BETWEEN '$start_date' AND '$end_date'
AND t.status_pembayaran IN ('lunas','dikonfirmasi')
ORDER BY t.tanggal_transaksi DESC
");
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT SUM(total_harga) as total
FROM transactions
WHERE DATE(tanggal_transaksi) BETWEEN '$start_date' AND '$end_date'
AND status_pembayaran IN ('lunas','dikonfirmasi')
"))['total'] ?? 0;
$total_transactions = mysqli_num_rows($transactions);

// ========== STOCK REPORT QUERIES (Products) ==========
$products_stock = mysqli_query($conn, "
SELECT p.*,
COALESCE((SELECT SUM(jumlah) FROM stock_movements WHERE product_id = p.id AND jenis = 'masuk' " . 
    ($start_date && $end_date ? "AND DATE(tanggal) BETWEEN '$start_date' AND '$end_date'" : "") . "), 0) as total_masuk,
COALESCE((SELECT SUM(jumlah) FROM stock_movements WHERE product_id = p.id AND jenis = 'keluar' " . 
    ($start_date && $end_date ? "AND DATE(tanggal) BETWEEN '$start_date' AND '$end_date'" : "") . "), 0) as total_keluar
FROM products p
ORDER BY p.nama_produk
");
$stock_summary = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT
COUNT(*) as total_items,
SUM(stok) as total_stock,
SUM(CASE WHEN stok < 20 THEN 1 ELSE 0 END) as low_stock
FROM products
")) ?? ['total_items' => 0, 'total_stock' => 0, 'low_stock' => 0];
$category_stock = mysqli_query($conn, "
SELECT kategori, COUNT(*) as items, SUM(stok) as total_qty
FROM products
GROUP BY kategori
ORDER BY total_qty DESC
");

// ========== INVENTORY REPORT QUERIES ==========
$inventory_items = mysqli_query($conn, "SELECT * FROM inventory ORDER BY created_at DESC");
$inventory_stats = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT
COUNT(*) as total_items,
SUM(jumlah) as total_qty,
SUM(CASE WHEN kondisi='Baik' THEN 1 ELSE 0 END) as kondisi_baik,
SUM(CASE WHEN kondisi='Dalam Perbaikan' THEN 1 ELSE 0 END) as kondisi_perbaikan,
SUM(CASE WHEN kondisi IN ('Rusak Ringan','Rusak Berat') THEN 1 ELSE 0 END) as kondisi_rusak
FROM inventory
")) ?? ['total_items' => 0, 'total_qty' => 0, 'kondisi_baik' => 0, 'kondisi_perbaikan' => 0, 'kondisi_rusak' => 0];
$inventory_by_category = mysqli_query($conn, "
SELECT kategori, COUNT(*) as items, SUM(jumlah) as total_qty
FROM inventory
GROUP BY kategori
ORDER BY items DESC
");

// Filter untuk inventory
$inv_kategori_filter = $_GET['inv_kategori'] ?? '';
$inv_kondisi_filter = $_GET['inv_kondisi'] ?? '';
$inventory_filtered = mysqli_query($conn, "
SELECT * FROM inventory
WHERE 1=1
" . ($inv_kategori_filter ? "AND kategori = '" . mysqli_real_escape_string($conn, $inv_kategori_filter) . "'" : "") . "
" . ($inv_kondisi_filter ? "AND kondisi = '" . mysqli_real_escape_string($conn, $inv_kondisi_filter) . "'" : "") . "
ORDER BY created_at DESC
");

// Filter untuk Coffee Beans History
$filter_bean = $_GET['filter_bean'] ?? '';
$filter_bean_jenis = $_GET['filter_bean_jenis'] ?? '';
$filter_bean_date_from = $_GET['filter_bean_date_from'] ?? '';
$filter_bean_date_to = $_GET['filter_bean_date_to'] ?? '';

$beans_history_where = "1=1";
if ($filter_bean)
    $beans_history_where .= " AND smb.bean_id = " . (int) $filter_bean;
if ($filter_bean_jenis)
    $beans_history_where .= " AND smb.jenis = '" . mysqli_real_escape_string($conn, $filter_bean_jenis) . "'";
if ($filter_bean_date_from)
    $beans_history_where .= " AND DATE(smb.tanggal) >= '" . mysqli_real_escape_string($conn, $filter_bean_date_from) . "'";
if ($filter_bean_date_to)
    $beans_history_where .= " AND DATE(smb.tanggal) <= '" . mysqli_real_escape_string($conn, $filter_bean_date_to) . "'";

// FIX: Query beans_history tanpa kolom varietas & asal
$beans_history = mysqli_query($conn, "
SELECT smb.*, cb.nama_biji_kopi
FROM stock_movements_beans smb
JOIN coffee_beans cb ON smb.bean_id = cb.id
WHERE $beans_history_where
ORDER BY smb.tanggal DESC
LIMIT 500
");

$beans_history_data = [];
while ($row = mysqli_fetch_assoc($beans_history)) {
    $beans_history_data[] = $row;
}

// Calculate totals for beans report
$beans_total_masuk = 0;
$beans_total_keluar = 0;
foreach ($beans_history_data as $hist) {
    if ($hist['jenis'] == 'masuk') {
        $beans_total_masuk += $hist['jumlah'];
    } else {
        $beans_total_keluar += $hist['jumlah'];
    }
}

// Get bean name if filtered
$bean_name_filter = 'Semua Biji Kopi';
if ($filter_bean) {
    $bean = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_biji_kopi FROM coffee_beans WHERE id = " . (int) $filter_bean));
    if ($bean) {
        $bean_name_filter = $bean['nama_biji_kopi'];
    }
}

// Format period text for print
$beans_period_text = '';
if ($filter_bean_date_from && $filter_bean_date_to) {
    $beans_period_text = date('d M Y', strtotime($filter_bean_date_from)) . ' - ' . date('d M Y', strtotime($filter_bean_date_to));
} elseif ($filter_bean_date_from) {
    $beans_period_text = 'Dari ' . date('d M Y', strtotime($filter_bean_date_from));
} elseif ($filter_bean_date_to) {
    $beans_period_text = 'Sampai ' . date('d M Y', strtotime($filter_bean_date_to));
} else {
    $beans_period_text = 'Semua Periode';
}

// List semua biji kopi untuk filter dropdown
$beans_list = mysqli_query($conn, "SELECT id, nama_biji_kopi FROM coffee_beans WHERE stok > 0 ORDER BY nama_biji_kopi");

// Format period text for sales/stock
$period_text = '';
if ($start_date && $end_date) {
    $period_text = date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
}

// Get product name for stock report if needed
$product_name_filter = 'Semua Produk';

// print
if ($print_mode):
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Laporan - TEFA COFFEE</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Arial', 'Times New Roman', serif; font-size: 12pt; line-height: 1.5; color: #000; background: #fff; padding: 30px; }
            .container { max-width: 210mm; margin: 0 auto; }
            .report-header { text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; }
            .report-header .logo { width: 60px; height: 60px; margin-bottom: 10px; }
            .report-header h1 { font-size: 18pt; font-weight: bold; margin: 10px 0 5px 0; text-transform: uppercase; }
            .report-header h2 { font-size: 14pt; font-weight: normal; margin: 5px 0; }
            .report-info { margin-top: 15px; text-align: left; font-size: 11pt; }
            .report-info table { width: 100%; border-collapse: collapse; }
            .report-info td { padding: 3px 0; }
            .report-info td:first-child { width: 140px; font-weight: bold; }
            .data-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 11pt; }
            .data-table th, .data-table td { border: 1px solid #000; padding: 8px 10px; text-align: left; }
            .data-table th { background: #f0f0f0; font-weight: bold; text-transform: uppercase; font-size: 10pt; }
            .data-table td.text-center { text-align: center; }
            .data-table td.text-right { text-align: right; }
            .summary-box { border: 2px solid #000; padding: 15px; margin: 20px 0; background: #f9f9f9; }
            .summary-box h3 { font-size: 13pt; margin-bottom: 10px; text-transform: uppercase; }
            .summary-table { width: 100%; border-collapse: collapse; }
            .summary-table td { padding: 8px 10px; border-bottom: 1px solid #ccc; }
            .summary-table td:last-child { text-align: right; font-weight: bold; }
            .summary-table tr.total-row { border-top: 2px solid #000; font-weight: bold; }
            .summary-table tr.total-row td { border-bottom: none; padding-top: 12px; }
            .report-footer { margin-top: 40px; display: flex; justify-content: space-between; page-break-inside: avoid; }
            .footer-section { width: 45%; }
            .footer-section h4 { font-size: 11pt; margin-bottom: 60px; text-align: center; }
            .footer-section p { text-align: center; font-size: 11pt; }
            .signature-line { border-top: 1px solid #000; margin-top: 50px; padding-top: 5px; text-align: center; }
            .print-date { text-align: right; font-size: 10pt; margin-top: 20px; font-style: italic; }
            .print-actions { text-align: center; margin-bottom: 30px; padding: 20px; background: #f5f5f5; border-radius: 8px; display: flex; gap: 10px; justify-content: center; }
            .btn-print { background: #3E2723; color: #fff; border: none; padding: 12px 30px; font-size: 14pt; border-radius: 5px; cursor: pointer; }
            .btn-print:hover { background: #2e1e1b; }
            .btn-close { background: #6b7280; color: #fff; border: none; padding: 12px 30px; font-size: 14pt; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
            .btn-close:hover { background: #4b5563; }
            .empty-state { text-align: center; padding: 50px 20px; color: #666; }
            .empty-state i { font-size: 48pt; margin-bottom: 15px; opacity: 0.3; }
            @media print {
                .print-actions { display: none !important; }
                body { padding: 0; }
                .data-table tr:nth-child(even) { background: #fff !important; }
                .summary-box { background: #fff !important; }
                @page { margin: 1.5cm; size: A4; }
            }
        </style>
    </head>
    <body>
        <div class="print-actions">
            <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> Cetak Laporan</button>
            <a href="reports.php?type=<?= $report_type ?>&start=<?= $start_date ?>&end=<?= $end_date ?>" class="btn-close"><i class="fas fa-times"></i> Kembali</a>
        </div>
        <div class="container">
            <?php if ($report_type == 'sales'): ?>
                <div class="report-header">
                    <img src="../assets/images/logopolije.png" alt="Logo" class="logo" onerror="this.style.display='none'">
                    <h1>TEFA COFFEE</h1>
                    <h2>LAPORAN PENJUALAN</h2>
                    <div class="report-info">
                        <table>
                            <tr><td>Periode</td><td>: <?= htmlspecialchars($period_text) ?></td></tr>
                            <tr><td>Total Data</td><td>: <?= $total_transactions ?> transaksi</td></tr>
                        </table>
                    </div>
                </div>
                <?php if ($total_transactions > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 15%;">Tanggal</th>
                                <th style="width: 20%;">Kode</th>
                                <th style="width: 25%;">Customer</th>
                                <th style="width: 15%;">Metode</th>
                                <th style="width: 20%;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; mysqli_data_seek($transactions, 0);
                            while ($t = mysqli_fetch_assoc($transactions)): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= date('d M Y H:i', strtotime($t['tanggal_transaksi'])) ?></td>
                                    <td><?= htmlspecialchars($t['kode_transaksi']) ?></td>
                                    <td><?= htmlspecialchars($t['nama_lengkap']) ?></td>
                                    <td class="text-center"><?= strtoupper(htmlspecialchars($t['metode_pembayaran'])) ?></td>
                                    <td class="text-right">Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="summary-box">
                        <h3>Ringkasan Penjualan</h3>
                        <table class="summary-table">
                            <tr><td>Total Transaksi</td><td><?= $total_transactions ?> transaksi</td></tr>
                            <tr><td>Total Pendapatan</td><td>Rp <?= number_format($total_revenue, 0, ',', '.') ?></td></tr>
                            <tr class="total-row"><td>Rata-rata/Transaksi</td><td>Rp <?= number_format($total_transactions > 0 ? $total_revenue / $total_transactions : 0, 0, ',', '.') ?></td></tr>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><i class="fas fa-inbox"></i><h3>Tidak Ada Data</h3><p>Tidak ada transaksi pada periode yang dipilih</p></div>
                <?php endif; ?>
                
            <?php elseif ($report_type == 'stock'): ?>
                <div class="report-header">
                    <img src="../assets/images/logopolije.png" alt="Logo" class="logo" onerror="this.style.display='none'">
                    <h1>TEFA COFFEE</h1>
                    <h2>LAPORAN STOK PRODUK</h2>
                    <div class="report-info">
                        <table>
                            <tr><td>Periode</td><td>: <?= htmlspecialchars($period_text) ?></td></tr>
                            <tr><td>Total Data</td><td>: <?= (int) $stock_summary['total_items'] ?> produk</td></tr>
                        </table>
                    </div>
                </div>
                <?php if (mysqli_num_rows($products_stock) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 30%;">Produk</th>
                                <th style="width: 15%;">Kategori</th>
                                <th style="width: 15%;">Stok</th>
                                <th style="width: 15%;">Masuk</th>
                                <th style="width: 15%;">Keluar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; mysqli_data_seek($products_stock, 0);
                            while ($p = mysqli_fetch_assoc($products_stock)): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($p['nama_produk']) ?></td>
                                    <td><?= ucfirst($p['kategori']) ?></td>
                                    <td class="text-right"><?= (int) $p['stok'] ?> unit</td>
                                    <td class="text-right">+<?= (int) $p['total_masuk'] ?></td>
                                    <td class="text-right">-<?= (int) $p['total_keluar'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="summary-box">
                        <h3> Ringkasan Stok</h3>
                        <table class="summary-table">
                            <tr><td>Total Item</td><td><?= (int) $stock_summary['total_items'] ?> produk</td></tr>
                            <tr><td>Total Stok</td><td><?= (int) $stock_summary['total_stock'] ?> unit</td></tr>
                            <tr class="total-row"><td>Stok Kritis (&lt;20)</td><td><?= (int) $stock_summary['low_stock'] ?> produk</td></tr>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><i class="fas fa-box-open"></i><h3>Tidak Ada Data</h3><p>Belum ada data produk</p></div>
                <?php endif; ?>
                
            <?php elseif ($report_type == 'beans_history'): ?>
                <div class="report-header">
                    <img src="../assets/images/logopolije.png" alt="Logo" class="logo" onerror="this.style.display='none'">
                    <h1>TEFA COFFEE</h1>
                    <h2>LAPORAN RIWAYAT STOK BIJI KOPI</h2>
                    <div class="report-info">
                        <table>
                            <tr><td>Produk</td><td>: <?= htmlspecialchars($bean_name_filter) ?></td></tr>
                            <tr><td>Periode</td><td>: <?= htmlspecialchars($beans_period_text) ?></td></tr>
                            <tr><td>Total Data</td><td>: <?= count($beans_history_data) ?> riwayat</td></tr>
                        </table>
                    </div>
                </div>
                <?php if (count($beans_history_data) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 15%;">Tanggal</th>
                                <th style="width: 30%;">Nama Biji Kopi</th>
                                <th style="width: 12%;">Jenis</th>
                                <th style="width: 13%;">Jumlah</th>
                                <th style="width: 25%;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($beans_history_data as $hist): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= date('d M Y H:i', strtotime($hist['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($hist['nama_biji_kopi']) ?></td>
                                    <td class="text-center"><?= ucfirst($hist['jenis']) ?></td>
                                    <td class="text-right"><?= $hist['jenis'] == 'masuk' ? '+' : '-' ?> <?= number_format($hist['jumlah'], 1, ',', '.') ?> kg</td>
                                    <td><?= htmlspecialchars($hist['keterangan'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="summary-box">
                        <h3>Ringkasan Stok</h3>
                        <table class="summary-table">
                            <tr><td>Total Stok Masuk</td><td>+<?= number_format($beans_total_masuk, 1, ',', '.') ?> kg</td></tr>
                            <tr><td>Total Stok Keluar</td><td>-<?= number_format($beans_total_keluar, 1, ',', '.') ?> kg</td></tr>
                            <tr class="total-row"><td>Netto Perubahan</td><td><?= ($beans_total_masuk - $beans_total_keluar) >= 0 ? '+' : '' ?><?= number_format($beans_total_masuk - $beans_total_keluar, 1, ',', '.') ?> kg</td></tr>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><i class="fas fa-inbox"></i><h3>Tidak Ada Data</h3><p>Tidak ada riwayat stok biji kopi yang sesuai dengan filter yang dipilih</p></div>
                <?php endif; ?>
                
            <?php elseif ($report_type == 'inventory'): ?>
                <div class="report-header">
                    <img src="../assets/images/logopolije.png" alt="Logo" class="logo" onerror="this.style.display='none'">
                    <h1>TEFA COFFEE</h1>
                    <h2>LAPORAN INVENTARIS BARANG</h2>
                    <div class="report-info">
                        <table>
                            <tr><td>Total Data</td><td>: <?= (int) $inventory_stats['total_items'] ?> item</td></tr>
                            <?php if ($inv_kategori_filter): ?><tr><td>Filter Kategori</td><td>: <?= htmlspecialchars($inv_kategori_filter) ?></td></tr><?php endif; ?>
                            <?php if ($inv_kondisi_filter): ?><tr><td>Filter Kondisi</td><td>: <?= htmlspecialchars($inv_kondisi_filter) ?></td></tr><?php endif; ?>
                        </table>
                    </div>
                </div>
                <?php if (mysqli_num_rows($inventory_filtered) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 25%;">Nama Barang</th>
                                <th style="width: 15%;">Kategori</th>
                                <th style="width: 10%;">Jumlah</th>
                                <th style="width: 15%;">Kondisi</th>
                                <th style="width: 15%;">Tanggal Beli</th>
                                <th style="width: 15%;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; mysqli_data_seek($inventory_filtered, 0);
                            while ($item = mysqli_fetch_assoc($inventory_filtered)): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($item['nama_barang']) ?></td>
                                    <td><?= htmlspecialchars($item['kategori']) ?></td>
                                    <td class="text-right"><?= (int) $item['jumlah'] ?></td>
                                    <td class="text-center"><?= htmlspecialchars($item['kondisi']) ?></td>
                                    <td class="text-center"><?= date('d M Y', strtotime($item['tanggal_pembelian'])) ?></td>
                                    <td><?= htmlspecialchars($item['keterangan'] ?? '-') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="summary-box">
                        <h3>Ringkasan Inventaris</h3>
                        <table class="summary-table">
                            <tr><td>Total Item</td><td><?= (int) $inventory_stats['total_items'] ?> item</td></tr>
                            <tr><td>Total Qty</td><td><?= (int) $inventory_stats['total_qty'] ?> unit</td></tr>
                            <tr><td>Kondisi Baik</td><td><?= (int) $inventory_stats['kondisi_baik'] ?> item</td></tr>
                            <tr><td>Dalam Perbaikan</td><td><?= (int) $inventory_stats['kondisi_perbaikan'] ?> item</td></tr>
                            <tr class="total-row"><td>Rusak</td><td><?= (int) $inventory_stats['kondisi_rusak'] ?> item</td></tr>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><i class="fas fa-clipboard-list"></i><h3>Tidak Ada Data</h3><p>Tidak ada data inventaris</p></div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="report-footer">
                <div class="footer-section">
                    <h4>Mengetahui,</h4><p>Kepala TEFA Coffee</p>
                    <div class="signature-line"><strong>( ___________________ )</strong></div>
                </div>
                <div class="footer-section">
                    <h4>Dibuat Oleh,</h4><p>Manager TEFA Coffee</p>
                    <div class="signature-line"><strong>( <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Manager') ?> )</strong></div>
                </div>
            </div>
            <div class="print-date"> Dicetak pada: <?= date('d M Y, H:i:s') ?> WIB</div>
        </div>
        <script>
            window.onload = function () {
                window.print();
                setTimeout(function () { window.location.href = 'reports.php?type=<?= $report_type ?>&start=<?= $start_date ?>&end=<?= $end_date ?>'; }, 1000);
            };
            window.onafterprint = function () { window.location.href = 'reports.php?type=<?= $report_type ?>&start=<?= $start_date ?>&end=<?= $end_date ?>'; };
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
    <title>Laporan - TEFA Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/reports.css">
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-container">
                    <img src="../assets/images/logopolije.png" alt="Polije" class="logo-polije" onerror="this.src='https://via.placeholder.com/42x42/2C1810/FFFFFF?text=P'">
                    <div class="logo-divider"></div>
                    <img src="../assets/images/sip.png" alt="TEFA" class="logo-tefa" onerror="this.src='https://via.placeholder.com/42x42/A67C52/FFFFFF?text=T'">
                    <span class="brand-text">TEFA COFFEE</span>
                </div>
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle Menu"><i class="fas fa-bars"></i></button>
            </div>
        </div>
    </div>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="reports.php" class="active"><i class="fas fa-file-alt"></i><span>Laporan</span></a></li>
            <li><a href="analytics.php"><i class="fas fa-chart-bar"></i><span>Analisis</span></a></li>
            <div class="sidebar-divider"></div>
            <li><a href="../logout.php" style="color: #ef9a9a;"><i class="fas fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </div>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Laporan TEFA Coffee</h1>
            </div>
            <!-- Report Type Tabs - 4 TYPES (-->
            <div class="report-tabs no-print">
                <a href="?type=sales&start=<?= $start_date ?>&end=<?= $end_date ?>" class="tab-btn <?= $report_type == 'sales' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i>Penjualan</a>
                <a href="?type=stock&start=<?= $start_date ?>&end=<?= $end_date ?>" class="tab-btn <?= $report_type == 'stock' ? 'active' : '' ?>"><i class="fas fa-warehouse"></i>Stok Produk</a>
                <a href="?type=beans_history&start=<?= $start_date ?>&end=<?= $end_date ?>" class="tab-btn <?= $report_type == 'beans_history' ? 'active' : '' ?>"><i class="fas fa-history"></i>Riwayat Biji Kopi</a>
                <a href="?type=inventory&start=<?= $start_date ?>&end=<?= $end_date ?>" class="tab-btn <?= $report_type == 'inventory' ? 'active' : '' ?>"><i class="fas fa-clipboard-list"></i>Inventaris</a>
            </div>
            <!-- Date Filter -->
            <?php if ($report_type != 'inventory' && $report_type != 'beans_history'): ?>
                <div class="filter-card no-print">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="type" value="<?= htmlspecialchars($report_type) ?>">
                        <div class="col-md-4"><label class="form-label">Tanggal Mulai</label><input type="date" name="start" value="<?= htmlspecialchars($start_date) ?>" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Tanggal Akhir</label><input type="date" name="end" value="<?= htmlspecialchars($end_date) ?>" class="form-control"></div>
                        
                    </form>
                </div>
            <?php endif; ?>
            
            <!-- ========== SALES REPORT ========== -->
            <?php if ($report_type == 'sales'): ?>
                <div class="card-custom">
                    <div class="card-header-custom"><span>Laporan Penjualan <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></span><button class="btn-custom btn-coffee btn-sm no-print" onclick="openPrintReport()"><i class="fas fa-file-pdf"></i> Cetak Laporan</button></div>
                    <div class="card-body">
                        <div class="stats-row">
                            <div class="stat-box"><div class="label">Total Pendapatan</div><div class="value success text-rupiah">Rp <?= number_format($total_revenue, 0, ',', '.') ?></div></div>
                            <div class="stat-box"><div class="label">Total Transaksi</div><div class="value"><?= $total_transactions ?></div></div>
                            <div class="stat-box"><div class="label">Rata-rata/Transaksi</div><div class="value text-rupiah">Rp <?= number_format($total_transactions > 0 ? $total_revenue / $total_transactions : 0, 0, ',', '.') ?></div></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead><tr><th>Tanggal</th><th>Kode</th><th>Customer</th><th>Metode</th><th>Total</th></tr></thead>
                                <tbody>
                                    <?php if ($total_transactions > 0): ?>
                                        <?php mysqli_data_seek($transactions, 0); while ($t = mysqli_fetch_assoc($transactions)): ?>
                                            <tr>
                                                <td data-label="Tanggal"><?= date('d/m/Y H:i', strtotime($t['tanggal_transaksi'])) ?></td>
                                                <td data-label="Kode" class="fw-semibold"><?= htmlspecialchars($t['kode_transaksi']) ?></td>
                                                <td data-label="Customer"><?= htmlspecialchars($t['nama_lengkap']) ?></td>
                                                <td data-label="Metode"><span class="badge-custom badge-info"><?= strtoupper(htmlspecialchars($t['metode_pembayaran'])) ?></span></td>
                                                <td data-label="Total" class="text-rupiah fw-semibold">Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-4"><div class="empty-state"><i class="fas fa-inbox"></i><p>Tidak ada transaksi pada periode ini</p></div></td></tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot><tr><td colspan="4" class="text-end"><strong>TOTAL</strong></td><td class="text-rupiah"><strong>Rp <?= number_format($total_revenue, 0, ',', '.') ?></strong></td></tr></tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- ========== STOCK REPORT ========== -->
            <?php if ($report_type == 'stock'): ?>
                <div class="card-custom">
                    <div class="card-header-custom"><span>Laporan Stok Produk <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></span><button class="btn-custom btn-coffee btn-sm no-print" onclick="openPrintReport()"><i class="fas fa-file-pdf"></i> Cetak Laporan</button></div>
                    <div class="card-body">
                        <div class="stats-row">
                            <div class="stat-box"><div class="label">Total Item</div><div class="value"><?= (int) $stock_summary['total_items'] ?> produk</div></div>
                            <div class="stat-box"><div class="label">Total Stok</div><div class="value"><?= (int) $stock_summary['total_stock'] ?> unit</div></div>
                            <div class="stat-box"><div class="label">Stok Kritis (&lt;20)</div><div class="value danger"><?= (int) $stock_summary['low_stock'] ?> produk</div></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead><tr><th>No</th><th>Produk</th><th>Kategori</th><th>Stok</th><th>Masuk</th><th>Keluar</th></tr></thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($products_stock) > 0): ?>
                                        <?php $no = 1; mysqli_data_seek($products_stock, 0); while ($p = mysqli_fetch_assoc($products_stock)): ?>
                                            <tr>
                                                <td data-label="No"><?= $no++ ?></td>
                                                <td data-label="Produk" class="fw-semibold"><?= htmlspecialchars($p['nama_produk']) ?></td>
                                                <td data-label="Kategori"><?= ucfirst($p['kategori']) ?></td>
                                                <td data-label="Stok"><span class="badge-custom <?= $p['stok'] < 20 ? 'badge-danger' : 'badge-success' ?>"><?= (int) $p['stok'] ?> unit</span></td>
                                                <td data-label="Masuk" class="text-success">+<?= (int) $p['total_masuk'] ?></td>
                                                <td data-label="Keluar" class="text-danger">-<?= (int) $p['total_keluar'] ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center py-4"><div class="empty-state"><i class="fas fa-box-open"></i><p>Belum ada data produk</p></div></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- ========== COFFEE BEANS HISTORY REPORT ========== -->
            <?php if ($report_type == 'beans_history'): ?>
                <div class="card-custom">
                    <div class="card-header-custom">
                        <span>Laporan Stok Biji Kopi</span>
                        <div class="d-flex gap-2 no-print flex-wrap">
                            <form method="GET" class="d-flex gap-2 flex-wrap">
                                <input type="hidden" name="type" value="beans_history">
                                <input type="hidden" name="start" value="<?= htmlspecialchars($start_date) ?>">
                                <input type="hidden" name="end" value="<?= htmlspecialchars($end_date) ?>">
                                <select name="filter_bean" class="form-select form-control-sm" style="width:auto" onchange="this.form.submit()">
                                    <option value="">Semua Biji Kopi</option>
                                    <?php mysqli_data_seek($beans_list, 0); while ($b = mysqli_fetch_assoc($beans_list)): ?>
                                        <option value="<?= $b['id'] ?>" <?= $filter_bean == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nama_biji_kopi']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <select name="filter_bean_jenis" class="form-select form-control-sm" style="width:auto" onchange="this.form.submit()">
                                    <option value="">Semua Jenis</option>
                                    <option value="masuk" <?= $filter_bean_jenis == 'masuk' ? 'selected' : '' ?>> Masuk</option>
                                    <option value="keluar" <?= $filter_bean_jenis == 'keluar' ? 'selected' : '' ?>> Keluar</option>
                                </select>
                                <input type="date" name="filter_bean_date_from" value="<?= htmlspecialchars($filter_bean_date_from) ?>" class="form-control form-control-sm" style="width:auto" title="Dari Tanggal" onchange="this.form.submit()">
                                <input type="date" name="filter_bean_date_to" value="<?= htmlspecialchars($filter_bean_date_to) ?>" class="form-control form-control-sm" style="width:auto" title="Sampai Tanggal" onchange="this.form.submit()">
                                <button type="button" class="btn-custom btn-coffee btn-sm" onclick="openPrintReport()"><i class="fas fa-file-pdf"></i> Cetak Laporan</button>
                                <?php if ($filter_bean || $filter_bean_jenis || $filter_bean_date_from || $filter_bean_date_to): ?>
                                    <a href="?type=beans_history&start=<?= $start_date ?>&end=<?= $end_date ?>" class="btn-custom btn-secondary btn-sm">Reset</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="stats-row">
                            <div class="stat-box"><div class="label">Total Riwayat</div><div class="value"><?= count($beans_history_data) ?></div></div>
                            <div class="stat-box"><div class="label">Total Masuk</div><div class="value success">+<?= number_format($beans_total_masuk, 1) ?> kg</div></div>
                            <div class="stat-box"><div class="label">Total Keluar</div><div class="value danger">-<?= number_format($beans_total_keluar, 1) ?> kg</div></div>
                            <div class="stat-box"><div class="label">Netto</div><div class="value"><?= ($beans_total_masuk - $beans_total_keluar) >= 0 ? '+' : '' ?><?= number_format($beans_total_masuk - $beans_total_keluar, 1) ?> kg</div></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Biji Kopi</th>
                                        <th>Jenis</th>
                                        <th>Jumlah</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($beans_history_data) > 0): ?>
                                        <?php $no = 1; foreach ($beans_history_data as $hist): ?>
                                            <tr>
                                                <td data-label="No"><?= $no++ ?></td>
                                                <td data-label="Tanggal"><?= date('d/m/Y H:i', strtotime($hist['tanggal'])) ?></td>
                                                <td data-label="Biji Kopi" class="fw-semibold"><?= htmlspecialchars($hist['nama_biji_kopi']) ?></td>
                                                <td data-label="Jenis"><span class="badge-custom <?= $hist['jenis'] == 'masuk' ? 'badge-success' :'badge-danger' ?>"><i class="fas fa-<?= $hist['jenis'] == 'masuk' ? 'arrow-down' : 'arrow-up' ?>"></i> <?= ucfirst($hist['jenis']) ?></span></td>
                                                <td data-label="Jumlah" class="fw-bold <?= $hist['jenis'] == 'masuk' ? 'text-success' : 'text-danger' ?>"><?= $hist['jenis'] == 'masuk' ? '+' : '-' ?> <?= number_format($hist['jumlah'], 1) ?> kg</td>
                                                <td data-label="Keterangan"><?= htmlspecialchars($hist['keterangan'] ?? '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center py-4"><div class="empty-state"><i class="fas fa-inbox"></i><p>Tidak ada riwayat stok biji kopi pada periode/filter ini</p></div></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- ========== INVENTORY REPORT ========== -->
            <?php if ($report_type == 'inventory'): ?>
                <div class="card-custom">
                    <div class="card-header-custom">
                        <span>Laporan Inventaris Barang</span>
                        <div class="d-flex gap-2 no-print">
                            <button class="btn-custom btn-coffee btn-sm" onclick="openPrintReport()"><i class="fas fa-file-pdf"></i> Cetak Laporan</button>
                            <form method="GET" class="d-flex gap-2">
                                <input type="hidden" name="type" value="inventory">
                                <select name="inv_kategori" class="form-select form-control-sm" style="width:auto" onchange="this.form.submit()">
                                    <option value="">Semua Kategori</option>
                                    <option value="Mesin Roasting" <?= $inv_kategori_filter == 'Mesin Roasting' ? 'selected' : '' ?>> Roasting</option>
                                    <option value="Peralatan Pendinginan" <?= $inv_kategori_filter == 'Peralatan Pendinginan' ? 'selected' : '' ?>>Storage</option>
                                    <option value="Mesin Grinding" <?= $inv_kategori_filter == 'Mesin Grinding' ? 'selected' : '' ?>> Grinding</option>
                                    <option value="Alat Ukur" <?= $inv_kategori_filter == 'Alat Ukur' ? 'selected' : '' ?>>Aksesoris</option>
                                    <option value="Quality Control" <?= $inv_kategori_filter == 'Quality Control' ? 'selected' : '' ?>> QC</option>
                                </select>
                                <select name="inv_kondisi" class="form-select form-control-sm" style="width:auto" onchange="this.form.submit()">
                                    <option value="">Semua Kondisi</option>
                                    <option value="Baik" <?= $inv_kondisi_filter == 'Baik' ? 'selected' : '' ?>> Baik</option>
                                    <option value="Dalam Perbaikan" <?= $inv_kondisi_filter == 'Dalam Perbaikan' ? 'selected' : '' ?>> Perbaikan</option>
                                    <option value="Rusak Ringan" <?= $inv_kondisi_filter == 'Rusak Ringan' ? 'selected' : '' ?>> Rusak</option>
                                </select>
                                <?php if ($inv_kategori_filter || $inv_kondisi_filter): ?>
                                    <a href="?type=inventory" class="btn-custom btn-secondary btn-sm">Reset</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="stats-row">
                            <div class="stat-box"><div class="label">Total Item</div><div class="value"><?= (int) $inventory_stats['total_items'] ?></div></div>
                            <div class="stat-box"><div class="label">Total Qty</div><div class="value"><?= (int) $inventory_stats['total_qty'] ?> unit</div></div>
                            <div class="stat-box"><div class="label">Kondisi Baik</div><div class="value success"><?= (int) $inventory_stats['kondisi_baik'] ?></div></div>
                            <div class="stat-box"><div class="label">Perbaikan</div><div class="value warning"><?= (int) $inventory_stats['kondisi_perbaikan'] ?></div></div>
                            <div class="stat-box"><div class="label">Rusak</div><div class="value danger"><?= (int) $inventory_stats['kondisi_rusak'] ?></div></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead><tr><th>No</th><th>Nama Barang</th><th>Kategori</th><th>Jumlah</th><th>Kondisi</th><th>Tanggal Beli</th><th>Keterangan</th></tr></thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($inventory_filtered) > 0): ?>
                                        <?php $no = 1; mysqli_data_seek($inventory_filtered, 0); while ($item = mysqli_fetch_assoc($inventory_filtered)): ?>
                                            <tr>
                                                <td data-label="No"><?= $no++ ?></td>
                                                <td data-label="Nama Barang" class="fw-semibold"><?= htmlspecialchars($item['nama_barang']) ?></td>
                                                <td data-label="Kategori"><?= htmlspecialchars($item['kategori']) ?></td>
                                                <td data-label="Jumlah" class="fw-semibold"><?= (int) $item['jumlah'] ?></td>
                                                <td data-label="Kondisi"><span class="badge-custom <?= $item['kondisi'] == 'Baik' ? 'badge-success' : ($item['kondisi'] == 'Dalam Perbaikan' ? 'badge-warning' : 'badge-danger') ?>"><?= htmlspecialchars($item['kondisi']) ?></span></td>
                                                <td data-label="Tanggal Beli"><?= date('d/m/Y', strtotime($item['tanggal_pembelian'])) ?></td>
                                                <td data-label="Keterangan" class="text-muted"><?= htmlspecialchars($item['keterangan']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center py-4"><div class="empty-state"><i class="fas fa-clipboard-list"></i><p>Tidak ada data inventaris</p></div></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($inventory_stats['kondisi_perbaikan'] > 0 || $inventory_stats['kondisi_rusak'] > 0): ?>
                            <div class="alert-custom alert-warning mt-4"><i class="fas fa-wrench"></i><strong>Perhatian:</strong> <?= $inventory_stats['kondisi_perbaikan'] ?> item dalam perbaikan, <?= $inventory_stats['kondisi_rusak'] ?> item rusak. Segera jadwalkan maintenance.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        }
        hamburgerBtn.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', () => { if (window.innerWidth <= 768) toggleSidebar(); });
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) { sidebar.classList.remove('active'); sidebarOverlay.classList.remove('active'); document.body.style.overflow = ''; }
        });
        function openPrintReport() {
            const type = '<?= $report_type ?>';
            const start = '<?= $start_date ?>';
            const end = '<?= $end_date ?>';
            const params = new URLSearchParams();
            params.append('type', type); params.append('print', '1'); params.append('start', start); params.append('end', end);
            <?php if ($report_type == 'beans_history'): ?>
                const bean = document.querySelector('select[name="filter_bean"]')?.value || '';
                const jenis = document.querySelector('select[name="filter_bean_jenis"]')?.value || '';
                const dateFrom = document.querySelector('input[name="filter_bean_date_from"]')?.value || '';
                const dateTo = document.querySelector('input[name="filter_bean_date_to"]')?.value || '';
                if (bean) params.append('filter_bean', bean);
                if (jenis) params.append('filter_bean_jenis', jenis);
                if (dateFrom) params.append('filter_bean_date_from', dateFrom);
                if (dateTo) params.append('filter_bean_date_to', dateTo);
            <?php endif; ?>
            <?php if ($report_type == 'inventory'): ?>
                const invKategori = document.querySelector('select[name="inv_kategori"]')?.value || '';
                const invKondisi = document.querySelector('select[name="inv_kondisi"]')?.value || '';
                if (invKategori) params.append('inv_kategori', invKategori);
                if (invKondisi) params.append('inv_kondisi', invKondisi);
            <?php endif; ?>
            window.location.href = 'reports.php?' + params.toString();
        }
        document.querySelectorAll('.filter-card input[type="date"]').forEach(input => {
            input.addEventListener('change', function () { clearTimeout(this.submitTimer); this.submitTimer = setTimeout(() => this.closest('form').submit(), 500); });
        });
        window.addEventListener('beforeprint', () => {
            document.querySelectorAll('.card-custom').forEach(card => { card.style.boxShadow = 'none'; card.style.transform = 'none'; });
        });
    </script>
</body>
</html>