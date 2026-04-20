<?php
require_once '../config/config.php';
checkRole('admin');

// ========== STATISTIK DASHBOARD ==========
$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"))['total'];
$total_transaksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transactions"))['total'];
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_harga) as total FROM transactions WHERE status_pembayaran = 'lunas'"))['total'] ?? 0;
$stok_rendah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE stok < 20"))['total'];

// ========== MANAJEMEN USER - LIHAT SEMUA USER ==========
$current_user_id = $_SESSION['user_id'] ?? 0;

// Get all users dengan info lengkap
$users_list = mysqli_query($conn, "
    SELECT 
        id, 
        username, 
        nama_lengkap, 
        role, 
        telepon, 
        email,
        status,
        created_at,
        last_login,
        CASE 
            WHEN last_login IS NULL THEN 'Belum pernah login'
            WHEN last_login > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN '🟢 Online'
            ELSE CONCAT('⚫ Offline (', DATE_FORMAT(last_login, '%d/%m %H:%i'), ')')
        END as status_online
    FROM users 
    WHERE id != '$current_user_id'
    ORDER BY 
        CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN 0 ELSE 1 END,
        last_login DESC
");

// User stats
$user_stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role='customer' THEN 1 ELSE 0 END) as total_customer,
        SUM(CASE WHEN role='admin' THEN 1 ELSE 0 END) as total_admin,
        SUM(CASE WHEN role='manager' THEN 1 ELSE 0 END) as total_manager,
        SUM(CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN 1 ELSE 0 END) as online_users
    FROM users
    WHERE id != '$current_user_id'
")) ?? ['total_users' => 0, 'total_customer' => 0, 'total_admin' => 0, 'total_manager' => 0, 'online_users' => 0];

// Handle User Actions
$user_action_msg = '';
if (isset($_POST['user_action'])) {
    $action = $_POST['action_type'];
    $target_user_id = (int)$_POST['target_user_id'];
    
    if ($target_user_id == $current_user_id) {
        $user_action_msg = '<div class="alert alert-danger alert-custom"><i class="fas fa-exclamation-circle"></i>Tidak dapat mengubah akun sendiri!</div>';
    } else {
        switch($action) {
            case 'delete':
                mysqli_query($conn, "UPDATE users SET status='nonaktif' WHERE id='$target_user_id'");
                $user_action_msg = '<div class="alert alert-success alert-custom"><i class="fas fa-check-circle"></i>User berhasil dinonaktifkan!</div>';
                break;
            case 'activate':
                mysqli_query($conn, "UPDATE users SET status='aktif' WHERE id='$target_user_id'");
                $user_action_msg = '<div class="alert alert-success alert-custom"><i class="fas fa-check-circle"></i>User berhasil diaktifkan!</div>';
                break;
            case 'change_role':
                $new_role = mysqli_real_escape_string($conn, $_POST['new_role']);
                if (in_array($new_role, ['customer', 'manager', 'admin'])) {
                    mysqli_query($conn, "UPDATE users SET role='$new_role' WHERE id='$target_user_id'");
                    $user_action_msg = '<div class="alert alert-success alert-custom"><i class="fas fa-check-circle"></i>Role user berhasil diubah!</div>';
                }
                break;
            case 'reset_password':
                $temp_pass = substr(md5(uniqid(rand(), true)), 0, 8);
                $hashed_pass = password_hash($temp_pass, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE users SET password='$hashed_pass' WHERE id='$target_user_id'");
                $user_action_msg = '<div class="alert alert-warning alert-custom"><i class="fas fa-key"></i>Password direset! Password sementara: <strong>'.$temp_pass.'</strong></div>';
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - TEFA Coffee</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --text-primary: #1a1a1a;
            --text-secondary: #4a4a4a;
            --text-muted: #6b7280;
            --coffee-dark: #3E2723;
            --coffee-accent: #8D6E63;
            --coffee-gold: #B8956A;
            --success-bg: #dcfce7;
            --success-text: #166534;
            --warning-bg: #fef3c7;
            --warning-text: #92400e;
            --info-bg: #dbeafe;
            --info-text: #1e40af;
            --danger-bg: #fee2e2;
            --danger-text: #991b1b;
            --bg-page: #fafafa;
            --bg-card: #ffffff;
            --bg-sidebar: #ffffff;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-primary);
            line-height: 1.5;
        }

        /* TOP HEADER */
        .top-header {
            background: var(--coffee-dark);
            padding: 0.875rem 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .logo-container { display: flex; align-items: center; gap: 1rem; }
        .logo-polije, .logo-tefa { height: 44px; width: auto; object-fit: contain; }
        .logo-divider { height: 32px; width: 1px; background: rgba(255,255,255,0.3); }
        .brand-text { color: #fff; font-size: 1.35rem; font-weight: 700; }

        /* SIDEBAR */
        .sidebar {
            background: var(--bg-sidebar);
            min-height: calc(100vh - 72px);
            border-right: 1px solid #e5e7eb;
            padding: 1.5rem 0;
            position: sticky;
            top: 72px;
            z-index: 900;
        }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { margin: 0.25rem 0; }
        .sidebar-menu a {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.15s ease;
            border-left: 3px solid transparent;
        }
        .sidebar-menu a:hover { background: #f9fafb; color: var(--coffee-dark); }
        .sidebar-menu a.active {
            background: #fffbeb;
            color: var(--coffee-dark);
            border-left-color: var(--coffee-gold);
            font-weight: 600;
        }
        .sidebar-menu a i { width: 20px; text-align: center; font-size: 1rem; color: var(--coffee-accent); }
        .sidebar-menu a.active i, .sidebar-menu a:hover i { color: var(--coffee-gold); }

        /* MAIN CONTENT */
        .main-content { padding: 1.5rem 2rem; }
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.75rem; padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .page-title { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .user-info {
            display: flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 1rem; background: var(--bg-card);
            border: 1px solid #e5e7eb; border-radius: 8px;
            font-weight: 500; font-size: 0.9rem;
        }
        .user-info i { color: var(--coffee-gold); }

        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem; margin-bottom: 1.5rem;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 1.25rem;
            display: flex; align-items: flex-start; gap: 1rem;
        }
        .stat-card:hover { border-color: var(--coffee-gold); }
        .stat-icon {
            width: 44px; height: 44px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 1.1rem;
        }
        .stat-icon.blue { background: #eff6ff; color: #2563eb; }
        .stat-icon.green { background: #f0fdf4; color: #16a34a; }
        .stat-icon.gold { background: #fffbeb; color: #b4834c; }
        .stat-icon.red { background: #fef2f2; color: #dc2626; }
        .stat-content { flex: 1; min-width: 0; }
        .stat-value { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem; }
        .stat-label { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; text-transform: uppercase; }

        /* CARDS & TABLES */
        .card-custom {
            background: var(--bg-card);
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .card-header-custom {
            padding: 1rem 1.25rem;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-header-custom i { color: var(--coffee-gold); margin-right: 0.5rem; }
        .card-body { padding: 1.25rem; }
        .table-custom { width: 100%; margin-bottom: 0; }
        .table-custom thead th {
            background: #f9fafb; color: var(--text-secondary);
            font-weight: 600; font-size: 0.8rem; text-transform: uppercase;
            padding: 0.875rem 1.25rem; border-bottom: 1px solid #e5e7eb;
        }
        .table-custom tbody td {
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        .table-custom tbody tr:hover { background: #f9fafb; }

        /* BADGES */
        .badge-custom {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.35rem 0.875rem; border-radius: 6px;
            font-size: 0.75rem; font-weight: 600;
        }
        .badge-success { background: var(--success-bg); color: var(--success-text); }
        .badge-warning { background: var(--warning-bg); color: var(--warning-text); }
        .badge-info { background: var(--info-bg); color: var(--info-text); }
        .badge-role-admin { background: #fef3c7; color: #92400e; }
        .badge-role-manager { background: #dbeafe; color: #1e40af; }
        .badge-role-customer { background: #f3f4f6; color: #4a4a4a; }
        .badge-status-aktif { background: var(--success-bg); color: var(--success-text); }
        .badge-status-nonaktif { background: var(--danger-bg); color: var(--danger-text); }

        /* ALERTS */
        .alert-custom {
            border: none; border-radius: 8px;
            padding: 0.875rem 1.25rem; margin-bottom: 1rem;
        }
        .alert-success { background: var(--success-bg); color: var(--success-text); border-left: 4px solid #86efac; }
        .alert-danger { background: var(--danger-bg); color: var(--danger-text); border-left: 4px solid #fca5a5; }
        .alert-warning { background: var(--warning-bg); color: var(--warning-text); border-left: 4px solid #fcd34d; }

        /* ACTION BUTTONS */
        .action-btn {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 6px; border: none; cursor: pointer;
            margin-right: 0.25rem; transition: all 0.15s;
        }
        .action-btn:last-child { margin-right: 0; }
        .action-btn.edit { background: var(--info-bg); color: var(--info-text); }
        .action-btn.edit:hover { background: #bfdbfe; }
        .action-btn.delete { background: var(--danger-bg); color: var(--danger-text); }
        .action-btn.delete:hover { background: #fecaca; }
        .action-btn.reset { background: var(--warning-bg); color: var(--warning-text); }
        .action-btn.reset:hover { background: #fde68a; }

        /* MODALS */
        .modal-content { border: none; border-radius: 12px; }
        .modal-header { background: var(--coffee-dark); color: white; border-radius: 12px 12px 0 0; }
        .modal-header .btn-close { filter: brightness(0) invert(1); }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar { position: static; min-height: auto; border-right: none; border-bottom: 1px solid #e5e7eb; }
            .sidebar-menu { display: flex; flex-wrap: wrap; gap: 0.25rem; padding: 0 0.5rem; }
            .sidebar-menu a { padding: 0.5rem 0.75rem; font-size: 0.85rem; border-left: none; border-radius: 6px; }
            .sidebar-menu a.active { border-left-color: transparent; }
            .main-content { padding: 1rem; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .table-custom thead { display: none; }
            .table-custom tbody tr { display: block; margin-bottom: 1rem; border: 1px solid #e5e7eb; border-radius: 10px; }
            .table-custom tbody td { display: flex; justify-content: space-between; padding: 0.75rem 1rem; }
            .table-custom tbody td::before { content: attr(data-label); font-weight: 600; color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem; }
        }
        @media (max-width: 480px) { .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <div class="container">
            <div class="logo-container">
                <img src="../assets/images/logopolije.png" alt="Polije" class="logo-polije" onerror="this.src='https://via.placeholder.com/44x44/4CAF50/FFFFFF?text=P'">
                <div class="logo-divider"></div>
                <img src="../assets/images/sip.png" alt="TEFA" class="logo-tefa" onerror="this.src='https://via.placeholder.com/44x44/B8956A/FFFFFF?text=T'">
                <span class="brand-text">TEFA COFFEE</span>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar dengan Menu Manajemen User -->
            <div class="col-md-2 sidebar d-none d-md-block">
                <ul class="sidebar-menu">
                    <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                    <li><a href="products.php"><i class="fas fa-box"></i><span>Kelola Produk</span></a></li>
                    <li><a href="stock.php"><i class="fas fa-warehouse"></i><span>Stok Masuk/Keluar</span></a></li>
                    <li><a href="inventory.php"><i class="fas fa-clipboard-list"></i><span>Inventaris</span></a></li>
                    <li><a href="transactions.php"><i class="fas fa-shopping-cart"></i><span>Transaksi</span></a></li>
                    <!-- ✅ MENU BARU: MANAJEMEN USER -->
                    <li><a href="#user-section" onclick="document.getElementById('user-section').scrollIntoView({behavior: 'smooth'})"><i class="fas fa-users"></i><span>Manajemen User</span></a></li>
                    <li class="mt-3"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">Dashboard</h1>
                    <div class="user-info">
                        <i class="fas fa-user"></i>
                        <span><?= htmlspecialchars($_SESSION['nama']) ?></span>
                    </div>
                </div>

                <?= $user_action_msg ?>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-box"></i></div>
                        <div class="stat-content"><div class="stat-value"><?= (int)$total_produk ?></div><div class="stat-label">Total Produk</div></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-shopping-cart"></i></div>
                        <div class="stat-content"><div class="stat-value"><?= (int)$total_transaksi ?></div><div class="stat-label">Total Transaksi</div></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon gold"><i class="fas fa-rupiah-sign"></i></div>
                        <div class="stat-content"><div class="stat-value text-rupiah">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div><div class="stat-label">Total Pendapatan</div></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-content"><div class="stat-value" style="color: var(--danger-text);"><?= (int)$stok_rendah ?></div><div class="stat-label">Stok Rendah</div></div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card-custom">
                    <div class="card-header-custom"><i class="fas fa-clock"></i>Transaksi Terbaru</div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead><tr><th>Kode</th><th>Customer</th><th>Total</th><th>Pembayaran</th><th>Status</th><th>Tanggal</th></tr></thead>
                            <tbody>
                                <?php
                                $transactions = mysqli_query($conn, "SELECT t.*, u.nama_lengkap FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.tanggal_transaksi DESC LIMIT 5");
                                while($trans = mysqli_fetch_assoc($transactions)):
                                ?>
                                <tr>
                                    <td data-label="Kode" class="fw-semibold"><?= htmlspecialchars($trans['kode_transaksi']) ?></td>
                                    <td data-label="Customer"><?= htmlspecialchars($trans['nama_lengkap']) ?></td>
                                    <td data-label="Total" class="text-rupiah">Rp <?= number_format($trans['total_harga'], 0, ',', '.') ?></td>
                                    <td data-label="Pembayaran"><?= strtoupper(htmlspecialchars($trans['metode_pembayaran'])) ?></td>
                                    <td data-label="Status">
                                        <?php if($trans['status_pembayaran'] == 'lunas'): ?>
                                            <span class="badge-custom badge-success"><i class="fas fa-check"></i>Lunas</span>
                                        <?php elseif($trans['status_pembayaran'] == 'dikonfirmasi'): ?>
                                            <span class="badge-custom badge-info"><i class="fas fa-info"></i>Dikonfirmasi</span>
                                        <?php else: ?>
                                            <span class="badge-custom badge-warning"><i class="fas fa-hourglass"></i>Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Tanggal"><?= date('d/m/Y H:i', strtotime($trans['tanggal_transaksi'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ========== SECTION MANAJEMEN USER ========== -->
                <div class="card-custom" id="user-section">
                    <div class="card-header-custom">
                        <span><i class="fas fa-users"></i>Manajemen User - Siapa Saja yang Login</span>
                        <span class="badge-custom badge-info"><i class="fas fa-user"></i><?= (int)$user_stats['total_users'] ?> Total User</span>
                    </div>
                    <div class="card-body">
                        <!-- User Stats Summary -->
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <span class="badge-custom badge-role-customer"><i class="fas fa-user"></i><?= (int)$user_stats['total_customer'] ?> Customer</span>
                            <span class="badge-custom badge-role-manager"><i class="fas fa-user-tie"></i><?= (int)$user_stats['total_manager'] ?> Manager</span>
                            <span class="badge-custom badge-role-admin"><i class="fas fa-user-shield"></i><?= (int)$user_stats['total_admin'] ?> Admin</span>
                            <span class="badge-custom badge-success"><i class="fas fa-wifi"></i><?= (int)$user_stats['online_users'] ?> Online</span>
                        </div>

                        <!-- Users Table -->
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Nama</th>
                                        <th>Role</th>
                                        <th>Email/Telepon</th>
                                        <th>Status Online</th>
                                        <th>Last Login</th>
                                        <th>Status Akun</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($users_list) > 0): ?>
                                        <?php while($user = mysqli_fetch_assoc($users_list)): 
                                            $role_badge = match($user['role']) {
                                                'admin' => 'badge-role-admin',
                                                'manager' => 'badge-role-manager',
                                                default => 'badge-role-customer'
                                            };
                                            $status = $user['status'] ?? 'aktif';
                                            $status_badge = $status == 'aktif' ? 'badge-status-aktif' : 'badge-status-nonaktif';
                                            $is_online = strpos($user['status_online'], '🟢') !== false;
                                        ?>
                                        <tr <?= $is_online ? 'style="background: #f0fdf4;"' : '' ?>>
                                            <td data-label="Username" class="fw-semibold"><?= htmlspecialchars($user['username']) ?></td>
                                            <td data-label="Nama"><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                                            <td data-label="Role"><span class="badge-custom <?= $role_badge ?>"><?= ucfirst($user['role']) ?></span></td>
                                            <td data-label="Kontak">
                                                <small class="text-muted d-block"><?= htmlspecialchars($user['email'] ?? '-') ?></small>
                                                <small class="text-muted"><?= htmlspecialchars($user['telepon'] ?? '-') ?></small>
                                            </td>
                                            <td data-label="Status Online"><?= $user['status_online'] ?></td>
                                            <td data-label="Last Login"><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-' ?></td>
                                            <td data-label="Status"><span class="badge-custom <?= $status_badge ?>"><?= ucfirst($status) ?></span></td>
                                            <td data-label="Aksi">
                                                <button class="action-btn edit" onclick="openRoleModal(<?= $user['id'] ?>, '<?= addslashes($user['nama_lengkap']) ?>', '<?= $user['role'] ?>')" title="Ubah Role"><i class="fas fa-user-tag"></i></button>
                                                <button class="action-btn reset" onclick="confirmResetPassword(<?= $user['id'] ?>, '<?= addslashes($user['username']) ?>')" title="Reset Password"><i class="fas fa-key"></i></button>
                                                <?php if($status == 'aktif'): ?>
                                                <button class="action-btn delete" onclick="confirmUserAction(<?= $user['id'] ?>, 'nonaktif', '<?= addslashes($user['username']) ?>')" title="Nonaktifkan"><i class="fas fa-user-slash"></i></button>
                                                <?php else: ?>
                                                <button class="action-btn edit" onclick="confirmUserAction(<?= $user['id'] ?>, 'aktif', '<?= addslashes($user['username']) ?>')" title="Aktifkan"><i class="fas fa-user-check"></i></button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr><td colspan="8" class="text-center py-4"><div class="empty-state"><i class="fas fa-users fa-2x mb-2" style="color: var(--coffee-accent); opacity: 0.6;"></i><p class="text-muted mb-0">Belum ada user terdaftar</p></div></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modals (sama seperti sebelumnya) -->
    <div class="modal fade" id="roleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header"><h5 class="modal-title">Ubah Role User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="user_action" value="1"><input type="hidden" name="action_type" value="change_role"><input type="hidden" name="target_user_id" id="role_user_id">
                        <p class="mb-3"><strong>User:</strong> <span id="role_user_name"></span></p>
                        <label class="form-label">Role Baru</label>
                        <select name="new_role" class="form-control" required><option value="customer">Customer</option><option value="manager">Manager</option><option value="admin">Admin</option></select>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header border-bottom-0"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body text-center py-4">
                        <input type="hidden" name="user_action" value="1"><input type="hidden" name="action_type" id="confirm_action_type"><input type="hidden" name="target_user_id" id="confirm_user_id">
                        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #dc2626; margin-bottom: 1rem;"></i>
                        <h6 class="fw-semibold mb-2" id="confirm_title">Konfirmasi</h6>
                        <p class="text-muted mb-0" id="confirm_message">Apakah Anda yakin?</p>
                    </div>
                    <div class="modal-footer border-top-0 justify-content-center pb-4"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, Lanjutkan</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function openRoleModal(userId, userName, currentRole) {
        document.getElementById('role_user_id').value = userId;
        document.getElementById('role_user_name').textContent = userName;
        document.querySelector('#roleModal select[name="new_role"]').value = currentRole;
        new bootstrap.Modal(document.getElementById('roleModal')).show();
    }
    function confirmUserAction(userId, action, username) {
        document.getElementById('confirm_user_id').value = userId;
        document.getElementById('confirm_action_type').value = action;
        document.getElementById('confirm_title').innerHTML = action === 'aktif' ? 'Aktifkan User?' : 'Nonaktifkan User?';
        document.getElementById('confirm_message').innerHTML = action === 'aktif' ? `Aktifkan akun <strong>${username}</strong>?` : `Nonaktifkan akun <strong>${username}</strong>?`;
        new bootstrap.Modal(document.getElementById('confirmModal')).show();
    }
    function confirmResetPassword(userId, username) {
        document.getElementById('confirm_user_id').value = userId;
        document.getElementById('confirm_action_type').value = 'reset_password';
        document.getElementById('confirm_title').innerHTML = 'Reset Password?';
        document.getElementById('confirm_message').innerHTML = `Reset password untuk <strong>${username}</strong>?`;
        new bootstrap.Modal(document.getElementById('confirmModal')).show();
    }
    </script>
</body>
</html>