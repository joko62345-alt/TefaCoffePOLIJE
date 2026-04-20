<?php
require_once '../config/config.php';
checkRole('manager');

// Statistics - Transactions & Revenue
$total_transaksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transactions WHERE status_pembayaran IN ('lunas','dikonfirmasi')"))['total'];
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_harga) as total FROM transactions WHERE status_pembayaran IN ('lunas','dikonfirmasi')"))['total'] ?? 0;
$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stok) as total FROM products"))['total'];
$stok_kritis = mysqli_query($conn, "SELECT * FROM products WHERE stok < 20");

// 🆕 Coffee Beans Statistics
$coffee_beans = mysqli_query($conn, "SELECT * FROM coffee_beans WHERE stok > 0 ORDER BY nama_biji_kopi ASC");
$total_biji_kopi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stok) as total FROM coffee_beans WHERE stok > 0"))['total'] ?? 0;
$beans_kritis = mysqli_query($conn, "SELECT * FROM coffee_beans WHERE stok < 10 AND stok > 0");

// Sales per product
$sales_per_product = mysqli_query($conn, "
    SELECT p.nama_produk, SUM(td.quantity) as total_terjual, SUM(td.subtotal) as total_pendapatan
    FROM transaction_details td
    JOIN products p ON td.product_id = p.id
    JOIN transactions t ON td.transaction_id = t.id
    WHERE t.status_pembayaran IN ('lunas','dikonfirmasi')
    GROUP BY p.id
    ORDER BY total_terjual DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Manager - TEFA Coffee</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
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
                <a href="reports.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Laporan</span>
                </a>
            </li>
            <li>
                <a href="analytics.php">
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
                    <i class=></i>Dashboard Manager
                </h1>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-content">
                        <div class="stat-number"><?= (int) $total_transaksi ?></div>
                        <div class="stat-label">Total Transaksi</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-rupiah-sign"></i></div>
                    <div class="stat-content">
                        <div class="stat-number text-rupiah">Rp
                            <?= number_format($total_pendapatan ?? 0, 0, ',', '.') ?></div>
                        <div class="stat-label">Pendapatan</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-boxes-stacked"></i></div>
                    <div class="stat-content">
                        <div class="stat-number"><?= (int) $total_produk ?></div>
                        <div class="stat-label">Stok Produk</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-seedling"></i></div>
                    <div class="stat-content">
                        <div class="stat-number"><?= number_format($total_biji_kopi ?? 0, 1) ?> kg</div>
                        <div class="stat-label">Stok Biji Kopi</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-triangle-exclamation"></i></div>
                    <div class="stat-content">
                        <div class="stat-number" style="color: var(--danger-text);">
                            <?= (mysqli_num_rows($stok_kritis) ?? 0) + (mysqli_num_rows($beans_kritis) ?? 0) ?>
                        </div>
                        <div class="stat-label">Stok Kritis</div>
                    </div>
                </div>
            </div>

            <!-- Charts & Stock Row -->
            <div class="row">
                <!-- Sales Chart -->
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <span><i class=></i> Penjualan per Produk</span>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="salesChart"></canvas>
                            </div>
                            <div class="chart-legend" id="chartLegend"></div>
                        </div>
                    </div>
                </div>

                <!-- Coffee Beans Stock -->
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <span><i class=></i> Stok Biji Kopi</span>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($coffee_beans) > 0): ?>
                                <ul class="stock-list">
                                    <?php
                                    mysqli_data_seek($coffee_beans, 0);
                                    while ($bean = mysqli_fetch_assoc($coffee_beans)):
                                        $is_kritis = $bean['stok'] < 10;
                                        $badge_class = $is_kritis ? 'stock-badge' : 'stock-badge success';
                                        ?>
                                        <li class="stock-item">
                                            <div>
                                                <span class="stock-name"><?= htmlspecialchars($bean['nama_biji_kopi']) ?></span>
                                                <?php if (!empty($bean['varietas']) || !empty($bean['asal'])): ?>
                                                    <small class="text-muted d-block" style="font-size: 0.78rem;">
                                                        <?= !empty($bean['varietas']) ? htmlspecialchars($bean['varietas']) : '' ?>
                                                        <?= !empty($bean['varietas']) && !empty($bean['asal']) ? ' • ' : '' ?>
                                                        <?= !empty($bean['asal']) ? htmlspecialchars($bean['asal']) : '' ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <span class="<?= $badge_class ?>">
                                                <i class="fas fa-circle" style="font-size: 5px;"></i>
                                                <?= number_format($bean['stok'], 1) ?> kg
                                            </span>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-seedling"></i>
                                    <p>Belum ada data biji kopi</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Detail Table -->
            <div class="card-custom">
                <div class="card-header-custom">
                    <span><i class=></i> Detail Penjualan Produk</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Terjual</th>
                                    <th>Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($sales_per_product, 0);
                                $sales_data = mysqli_fetch_all($sales_per_product, MYSQLI_ASSOC);
                                $counter = 0;

                                if (empty($sales_data)):
                                    ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4">
                                            <div class="empty-state">
                                                <i class="fas fa-inbox"></i>
                                                <p>Belum ada data penjualan</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sales_data as $sales):
                                        $counter++;
                                        ?>
                                        <tr>
                                            <td data-label="Produk" class="fw-semibold">
                                                <?= htmlspecialchars($sales['nama_produk']) ?>
                                            </td>
                                            <td data-label="Terjual"><?= (int) $sales['total_terjual'] ?> unit</td>
                                            <td data-label="Pendapatan" class="text-rupiah">
                                                Rp <?= number_format($sales['total_pendapatan'], 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    // Chart data
    const chartLabels = <?= json_encode(array_column($sales_data ?? [], 'nama_produk')) ?>;
    const chartData = <?= json_encode(array_column($sales_data ?? [], 'total_terjual')) ?>;
    const chartColors = ['#2C1810', '#5D4037', '#A67C52', '#2E5D4F', '#A8D5BA', '#8D6E63', '#1B4D3E'];

    const ctx = document.getElementById('salesChart');
    if (ctx && chartLabels.length > 0) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    backgroundColor: chartColors.slice(0, chartLabels.length),
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 12,
                    hoverBorderWidth: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(44, 24, 16, 0.95)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 14,
                        cornerRadius: 10,
                        displayColors: true,
                        callbacks: {
                            label: function (context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                return `${label}: ${value} unit`;  // ✅ TANPA PERSEN
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 1200,
                    easing: 'easeOutQuart'
                }
            }
        });

        const legendContainer = document.getElementById('chartLegend');
        if (legendContainer && chartLabels.length > 0) {
            legendContainer.innerHTML = chartLabels.map((label, index) => `
                <div class="legend-item">
                    <div class="legend-color" style="background: ${chartColors[index % chartColors.length]}"></div>
                    <span>${label}</span>
                </div>
            `).join('');
        }
    } else if (ctx) {
        const canvas = ctx.getContext('2d');
        canvas.font = '14px Inter, sans-serif';
        canvas.fillStyle = '#5a5a5a';
        canvas.textAlign = 'center';
        canvas.fillText('Belum ada data penjualan', canvas.canvas.width / 2, canvas.canvas.height / 2);
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