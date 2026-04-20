<?php
// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
checkRole('manager');

// 🔹 TENTUKAN PERIODE ANALYTICS (7 Hari Terakhir) - KONSISTEN UNTUK SEMUA QUERY
$analytics_start = date('Y-m-d', strtotime('-7 days'));
$analytics_end = date('Y-m-d');

// 🔹 Sales Trend (Last 7 Days)
$sales_trend = mysqli_query($conn, "
    SELECT 
        DATE(tanggal_transaksi) as tanggal,
        COUNT(*) as total_transaksi,
        SUM(total_harga) as total_penjualan
    FROM transactions 
    WHERE status_pembayaran IN ('lunas', 'dikonfirmasi')
    AND DATE(tanggal_transaksi) BETWEEN '$analytics_start' AND '$analytics_end'
    GROUP BY DATE(tanggal_transaksi)
    ORDER BY tanggal ASC
");

// 🔹 Top Products - FILTER TANGGAL SAMA ✅
$top_products = mysqli_query($conn, "
    SELECT 
        p.nama_produk,
        p.kategori,
        SUM(td.quantity) as total_terjual,
        SUM(td.subtotal) as total_pendapatan,
        p.stok
    FROM transaction_details td
    JOIN products p ON td.product_id = p.id
    JOIN transactions t ON td.transaction_id = t.id
    WHERE t.status_pembayaran IN ('lunas', 'dikonfirmasi')
    AND DATE(t.tanggal_transaksi) BETWEEN '$analytics_start' AND '$analytics_end'
    GROUP BY p.id
    ORDER BY total_terjual DESC
    LIMIT 5
");

// 🔹 Payment Method Stats - FILTER TANGGAL SAMA 
$payment_stats = mysqli_query($conn, "
    SELECT 
        metode_pembayaran,
        COUNT(*) as jumlah,
        SUM(total_harga) as total
    FROM transactions 
    WHERE status_pembayaran IN ('lunas', 'dikonfirmasi')
    AND DATE(tanggal_transaksi) BETWEEN '$analytics_start' AND '$analytics_end'
    GROUP BY metode_pembayaran
");

// 🔹 Stock Alert (real-time, tidak perlu filter tanggal)
$stock_alert = mysqli_query($conn, "
    SELECT nama_produk, stok, kategori
    FROM products 
    WHERE stok < 20
    ORDER BY stok ASC
    LIMIT 10
");

// 🔹 Revenue by Category - FILTER TANGGAL SAMA ✅
$revenue_category = mysqli_query($conn, "
    SELECT 
        p.kategori,
        COUNT(DISTINCT td.transaction_id) as total_transaksi,
        SUM(td.quantity) as total_item,
        SUM(td.subtotal) as total_pendapatan
    FROM transaction_details td
    JOIN products p ON td.product_id = p.id
    JOIN transactions t ON td.transaction_id = t.id
    WHERE t.status_pembayaran IN ('lunas', 'dikonfirmasi')
    AND DATE(t.tanggal_transaksi) BETWEEN '$analytics_start' AND '$analytics_end'
    GROUP BY p.kategori
    ORDER BY total_pendapatan DESC
");

// 🔹 Prepare data untuk JavaScript
mysqli_data_seek($sales_trend, 0);
$sales_labels = [];
$sales_transaksi = [];
$sales_penjualan = [];
while($row = mysqli_fetch_assoc($sales_trend)) {
    $sales_labels[] = date('d/m', strtotime($row['tanggal']));
    $sales_transaksi[] = (int)$row['total_transaksi'];
    $sales_penjualan[] = (float)$row['total_penjualan'];
}

mysqli_data_seek($top_products, 0);
$top_products_data = mysqli_fetch_all($top_products, MYSQLI_ASSOC);
$product_names = array_column($top_products_data, 'nama_produk');
$product_sold = array_column($top_products_data, 'total_terjual');

mysqli_data_seek($payment_stats, 0);
$payment_data = mysqli_fetch_all($payment_stats, MYSQLI_ASSOC);
$payment_methods = array_column($payment_data, 'metode_pembayaran');
$payment_totals = array_column($payment_data, 'total');

// 🔹 Hitung total revenue untuk persentase kategori
$total_revenue_all = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(td.subtotal) as total
    FROM transaction_details td
    JOIN transactions t ON td.transaction_id = t.id
    WHERE t.status_pembayaran IN ('lunas', 'dikonfirmasi')
    AND DATE(t.tanggal_transaksi) BETWEEN '$analytics_start' AND '$analytics_end'
"))['total'] ?? 1;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis & Statistik - TEFA Coffee</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/analytics.css">
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
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="reports.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Laporan</span>
                </a>
            </li>
            <li>
                <a href="analytics.php" class="active">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analisis</span>
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
                    <i class=></i>Analisis & Statistik Bisnis
                </h1>
                <span class="badge-custom badge-info">
                    <i class="fas fa-calendar"></i> Periode: <?= date('d/m/Y', strtotime($analytics_start)) ?> - <?= date('d/m/Y', strtotime($analytics_end)) ?>
                </span>
            </div>

            <!-- Sales Trend Chart -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <span><i class=></i>Tren Penjualan (7 Hari Terakhir)</span>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                    <div id="salesChartError" class="error-message">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Gagal memuat grafik. Silakan refresh halaman.
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Top Products -->
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <span><i class=></i>Produk Terlaris (7 Hari)</span>
                        </div>
                        <div class="card-body">
                            <?php if(count($top_products_data) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Produk</th>
                                            <th>Kategori</th>
                                            <th>Terjual</th>
                                            <th>Pendapatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($top_products_data as $index => $prod): ?>
                                        <tr>
                                            <td data-label="#"><span class="badge-custom <?= $index == 0 ? 'badge-warning' : 'badge-secondary' ?>">#<?= $index + 1 ?></span></td>
                                            <td data-label="Produk" class="fw-semibold"><?= htmlspecialchars($prod['nama_produk']) ?></td>
                                            <td data-label="Kategori"><span class="badge-custom badge-info"><?= ucfirst($prod['kategori']) ?></span></td>
                                            <td data-label="Terjual" class="fw-bold"><?= (int)$prod['total_terjual'] ?></td>
                                            <td data-label="Pendapatan" class="text-rupiah">Rp <?= number_format($prod['total_pendapatan'], 0, ',', '.') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <p>Belum ada produk terjual pada periode ini</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Revenue by Category -->
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <span><i class=></i>Pendapatan per Kategori (7 Hari)</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>Kategori</th>
                                            <th>Transaksi</th>
                                            <th>Item</th>
                                            <th>Pendapatan</th>
                                            <th>%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($revenue_category) > 0): ?>
                                            <?php while($cat = mysqli_fetch_assoc($revenue_category)): 
                                                $persen = $total_revenue_all > 0 ? ($cat['total_pendapatan'] / $total_revenue_all) * 100 : 0;
                                            ?>
                                            <tr>
                                                <td data-label="Kategori" class="fw-semibold">
                                                    <span class="badge-custom badge-info"><?= ucfirst($cat['kategori']) ?></span>
                                                </td>
                                                <td data-label="Transaksi"><?= (int)$cat['total_transaksi'] ?></td>
                                                <td data-label="Item"><?= (int)$cat['total_item'] ?></td>
                                                <td data-label="Pendapatan" class="text-rupiah fw-semibold">
                                                    Rp <?= number_format($cat['total_pendapatan'], 0, ',', '.') ?>
                                                </td>
                                                <td data-label="Persentase">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div style="width:60px;height:6px;background:#f3f4f6;border-radius:3px;overflow:hidden;">
                                                            <div style="width:<?= min($persen, 100) ?>%;height:100%;background:var(--coffee-gold);"></div>
                                                        </div>
                                                        <small><?= number_format($persen, 1) ?>%</small>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center py-4"><div class="empty-state"><i class="fas fa-inbox"></i><p>Belum ada data</p></div></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Alert -->
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <span><i class=></i>Alert Stok Menipis</span>
                        </div>
                        <div class="card-body">
                            <?php if(mysqli_num_rows($stock_alert) > 0): ?>
                            <div class="alert-custom alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Perhatian!</strong> <?= mysqli_num_rows($stock_alert) ?> produk stoknya di bawah 20 unit
                            </div>
                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Kategori</th>
                                            <th>Stok</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($alert = mysqli_fetch_assoc($stock_alert)): 
                                            $is_critical = $alert['stok'] < 10;
                                        ?>
                                        <tr>
                                            <td data-label="Produk" class="fw-semibold"><?= htmlspecialchars($alert['nama_produk']) ?></td>
                                            <td data-label="Kategori"><?= ucfirst(htmlspecialchars($alert['kategori'])) ?></td>
                                            <td data-label="Stok" class="fw-semibold"><?= (int)$alert['stok'] ?></td>
                                            <td data-label="Status">
                                                <span class="badge-custom <?= $is_critical ? 'badge-danger' : 'badge-warning' ?>">
                                                    <?= $is_critical ? 'Kritis' : 'Rendah' ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <p>Semua stok produk aman! </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // 🆕 Hamburger Menu Toggle
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

    // Close sidebar when clicking a menu link on mobile
    document.querySelectorAll('.sidebar-menu a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                toggleSidebar();
            }
        });
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // Debug: Check if Chart.js loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js tidak terload!');
        document.querySelectorAll('[id$="ChartError"]').forEach(el => el.style.display = 'block');
    }

    // Data dari PHP - sudah difilter 7 hari terakhir
    const salesLabels = <?= json_encode($sales_labels) ?>;
    const salesTransaksi = <?= json_encode($sales_transaksi) ?>;
    const salesPenjualan = <?= json_encode($sales_penjualan) ?>;
    const productNames = <?= json_encode($product_names) ?>;
    const productSold = <?= json_encode($product_sold) ?>;
    const paymentMethods = <?= json_encode($payment_methods) ?>;
    const paymentTotals = <?= json_encode($payment_totals) ?>;

    console.log('Analytics Period: <?= $analytics_start ?> to <?= $analytics_end ?>');
    console.log('Sales Labels:', salesLabels);
    console.log('Product Names:', productNames);

    // 🔹 Sales Trend Chart
    try {
        const salesCtx = document.getElementById('salesTrendChart');
        if (salesCtx && salesLabels.length > 0) {
            new Chart(salesCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: salesLabels,
                    datasets: [{
                        label: 'Total Transaksi',
                        data: salesTransaksi,
                        borderColor: '#B8956A',
                        backgroundColor: 'rgba(184, 149, 106, 0.15)',
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 4,
                        yAxisID: 'y'
                    }, {
                        label: 'Total Penjualan (Rp)',
                        data: salesPenjualan,
                        borderColor: '#3E2723',
                        backgroundColor: 'rgba(62, 39, 35, 0.1)',
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 4,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: { display: true, text: 'Jumlah Transaksi' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: { display: true, text: 'Pendapatan (Rp)' },
                            grid: { drawOnChartArea: false },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + (value/1000) + 'k';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });
        } else if (salesCtx) {
            document.getElementById('salesChartError').style.display = 'block';
            salesCtx.parentElement.innerHTML = '<div class="empty-state"><i class="fas fa-chart-line"></i><p>Belum ada data penjualan 7 hari terakhir</p></div>';
        }
    } catch (error) {
        console.error('Error sales chart:', error);
        document.getElementById('salesChartError').style.display = 'block';
    }

    // Print optimization
    window.addEventListener('beforeprint', () => {
        document.querySelectorAll('.card-custom').forEach(card => {
            card.style.boxShadow = 'none';
            card.style.transform = 'none';
        });
    });
    </script>
</body>
</html>