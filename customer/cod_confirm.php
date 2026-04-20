<?php
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    redirect('auth/login.php');
}

$kode_transaksi = isset($_GET['kode']) ? $_GET['kode'] : '';

if (empty($kode_transaksi)) {
    redirect('customer/dashboard.php');
}

$transaksi = mysqli_query($conn, "
    SELECT t.* 
    FROM transactions t 
    WHERE t.kode_transaksi = '$kode_transaksi' AND t.user_id = '{$_SESSION['user_id']}'
");

if (mysqli_num_rows($transaksi) == 0) {
    redirect('customer/dashboard.php');
}

$data = mysqli_fetch_assoc($transaksi);

$detail = mysqli_query($conn, "
    SELECT td.*, p.nama_produk, p.harga 
    FROM transaction_details td 
    JOIN products p ON td.product_id = p.id 
    WHERE td.transaction_id = '{$data['id']}'
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran - TEFA Coffee</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=SF+Mono+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css?v=<?= time() ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?= time() ?>">
    <style>
        :root {
            --coffee-dark: #2C1810;
            --coffee-primary: #4A3728;
            --coffee-gold: #D4A574;
            --coffee-light: #F5E6D3;
            --coffee-bg: #FFFBF7;
            --coffee-cream: #F5E9D7;
            --coffee-cream-light: #FFF9F0;
            --c-muted: #7a6f5d;
            --c-border: #e8dcc8;
            --shadow-lg: 0 24px 80px rgba(44,24,16,0.18);
        }
        
        * { font-family: 'Poppins', system-ui, sans-serif; }
        
        body { 
            background: linear-gradient(135deg, var(--coffee-bg) 0%, var(--coffee-light) 100%); 
            color: var(--coffee-dark); 
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .confirm-container {
            max-width: 500px;
            width: 100%;
        }
        
        .confirm-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: none;
        }
        
        .confirm-header {
            background: linear-gradient(135deg, var(--coffee-primary), var(--coffee-dark));
            color: #fff;
            padding: 2rem 1.75rem 1.5rem;
            text-align: center;
        }

        .confirm-icon {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .confirm-icon i { font-size: 2rem; color: #fff; }
        .confirm-title { font-weight: 700; font-size: 1.5rem; margin: 0 0 0.5rem 0; }
        .confirm-subtitle { font-size: 0.9rem; opacity: 0.9; margin: 0; }
        
        .confirm-body { padding: 1.75rem; background: #fff; }
        
        .code-card {
            background: linear-gradient(135deg, var(--coffee-cream-light), var(--coffee-cream));
            border: 2px solid var(--coffee-gold);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .code-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--c-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }
        
        .code-value {
            font-family: 'SF Mono', 'Menlo', 'Consolas', monospace;
            font-variant-numeric: tabular-nums;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--coffee-primary);
            letter-spacing: 3px;
            margin-bottom: 0.5rem;
            word-break: break-all;
        }
        
        .code-hint { font-size: 0.8rem; color: var(--c-muted); }
        .code-hint i { color: var(--coffee-gold); margin-right: 0.3rem; }
        
        .order-summary {
            background: #FAF8F5;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.25rem;
            border: 1px solid var(--c-border);
        }

        .order-summary-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--c-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 0 0 0.75rem 0;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px dashed var(--c-border);
            font-size: 0.9rem;
        }

        .order-item:last-child { border-bottom: none; }
        .order-item-name { font-weight: 600; color: var(--coffee-dark); }
        
        .order-item-price {
            font-family: 'SF Mono', 'Menlo', 'Consolas', monospace;
            font-variant-numeric: tabular-nums;
            font-weight: 700;
            color: var(--coffee-primary);
        }
        
        .order-total {
            background: linear-gradient(135deg, #FAF8F5, var(--coffee-cream-light));
            border-radius: 12px;
            padding: 1rem;
            margin: 1.25rem 0;
            border: 1px solid var(--c-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-total-label { font-weight: 700; color: var(--coffee-dark); }
        
        .order-total-amount {
            font-family: 'SF Mono', 'Menlo', 'Consolas', monospace;
            font-variant-numeric: tabular-nums;
            font-weight: 800;
            font-size: 1.3rem;
            color: #1B4D3E;
        }
        
        .payment-info {
            background: #FAF8F5;
            border-radius: 14px;
            padding: 1.25rem;
            margin: 1.25rem 0;
            border: 1px solid var(--c-border);
        }

        .payment-info-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--c-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 0 0 0.75rem 0;
        }

        .payment-info-content {
            background: #fff;
            border-radius: 10px;
            padding: 1rem;
            border: 1px solid var(--c-border);
        }

        .payment-info-content p {
            font-size: 0.85rem;
            color: var(--coffee-dark);
            margin: 0 0 0.5rem 0;
            line-height: 1.6;
        }

        .payment-info-content p:last-child { margin-bottom: 0; }
        .payment-info-content strong { color: var(--coffee-primary); }
        
        .payment-steps {
            list-style: none;
            padding: 0;
            margin: 0.75rem 0 0 0;
            border-top: 1px dashed var(--c-border);
            padding-top: 0.75rem;
        }

        .payment-steps li {
            font-size: 0.8rem;
            color: var(--c-muted);
            padding: 0.25rem 0;
            padding-left: 1.2rem;
            position: relative;
        }

        .payment-steps li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--coffee-gold);
            font-weight: bold;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .btn-premium {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            text-decoration: none;
            flex: 1;
        }

        .btn-premium.secondary {
            background: #fff;
            color: var(--coffee-primary) !important;
            border: 2px solid var(--c-border);
        }

        .btn-premium.secondary:hover {
            background: #FAF8F5;
            border-color: var(--coffee-gold);
        }

        .btn-premium.primary {
            background: var(--coffee-primary);
            color: #fff !important;
            border: 2px solid var(--coffee-primary);
        }

        .btn-premium.primary:hover {
            background: var(--coffee-dark);
            transform: translateY(-2px);
        }
        
        .footer-note {
            text-align: center;
            padding: 1rem 1.75rem;
            font-size: 0.75rem;
            color: var(--c-muted);
            border-top: 1px solid var(--c-border);
            background: #FAF8F5;
        }
        
        /* ===== RECEIPT MODAL - SAME AS DASHBOARD ===== */
        #receiptModal {
            z-index: 1150 !important;
        }

        #receiptModal .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.75) !important;
            opacity: 1 !important;
            z-index: 1140 !important;
        }

        #receiptModal .modal-dialog {
            max-width: 420px !important;
            margin: 2.5rem auto !important;
            z-index: 1150 !important;
            position: relative;
        }

        #receiptModal .modal-content {
            border-radius: 0 !important;
            border: none !important;
            max-width: 400px;
            font-family: 'Courier New', Courier, monospace;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5) !important;
            z-index: 1150 !important;
            position: relative;
        }

        .receipt-modal-header {
            background: #fff !important;
            border: none !important;
            padding: 0 !important;
            border-radius: 0 !important;
            position: relative;
        }

        .receipt-modal-header .btn-close {
            position: absolute !important;
            right: 10px !important;
            top: 10px !important;
            z-index: 1160 !important;
            background-color: rgba(0, 0, 0, 0.1) !important;
            border-radius: 50% !important;
            width: 32px !important;
            height: 32px !important;
            opacity: 0.7 !important;
        }

        .receipt-modal-header .btn-close:hover {
            opacity: 1 !important;
            background-color: rgba(0, 0, 0, 0.2) !important;
        }

        .receipt-modal-body {
            background: #fff !important;
            padding: 25px 20px !important;
            max-height: 75vh !important;
            overflow-y: auto !important;
        }

        .receipt-modal-footer {
            background: #fff !important;
            border: none !important;
            padding: 10px !important;
            justify-content: center !important;
        }

        .receipt-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 9px;
            line-height: 1.6;
        }

        .receipt-item .item-name {
            flex: 1;
            text-align: left;
        }

        .receipt-item .item-qty {
            margin: 0 10px;
            text-align: center;
            min-width: 30px;
        }

        .receipt-item .item-price {
            text-align: right;
            min-width: 80px;
            font-weight: 600;
        }

        @media (max-width: 576px) {
            #receiptModal .modal-dialog {
                margin: 1rem auto !important;
                max-width: 95% !important;
            }

            .receipt-modal-body {
                padding: 20px 15px !important;
                max-height: 85vh !important;
                font-size: 8px !important;
            }

            .receipt-item {
                font-size: 8px !important;
            }
        }

        @media print {
            #receiptModal .modal-backdrop,
            #receiptModal .modal-header .btn-close,
            #receiptModal .modal-footer {
                display: none !important;
            }

            body {
                background: #fff !important;
                padding: 0 !important;
            }
        }
        
        @media (max-width: 576px) {
            .confirm-container { max-width: 100%; }
            .confirm-header { padding: 1.5rem 1.25rem; }
            .confirm-body { padding: 1.25rem; }
            .code-value { font-size: 1.5rem; }
            .action-buttons { flex-direction: column; }
        }
        
        @media print {
            .action-buttons, .footer-note { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="confirm-container">
        <div class="confirm-card">
            
            <div class="confirm-header">
                <div class="confirm-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="confirm-title">Pesanan Berhasil!</h1>
                <p class="confirm-subtitle">Terima kasih telah memesan di TEFA Coffee. Silakan lakukan pembayaran dan ambil pesanan Anda sesuai petunjuk di bawah.</p>
            </div>

            <div class="confirm-body">
                
                <div class="code-card">
                    <div class="code-label">Kode Transaksi</div>
                    <div class="code-value"><?= htmlspecialchars($data['kode_transaksi']) ?></div>
                    <div class="code-hint">
                       
                    </div>
                </div>
                
                <div class="order-summary">
                    <div class="order-summary-title">Detail Pesanan</div>
                    <?php 
                    mysqli_data_seek($detail, 0);
                    while($item = mysqli_fetch_assoc($detail)): 
                        $qty = isset($item['qty']) ? $item['qty'] : (isset($item['quantity']) ? $item['quantity'] : 1);
                    ?>
                    <div class="order-item">
                        <span class="order-item-name">
                            <?= htmlspecialchars($item['nama_produk']) ?> 
                            <small class="text-muted">× <?= $qty ?></small>
                        </span>
                        <span class="order-item-price">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="order-total">
                    <span class="order-total-label">Total Pembayaran</span>
                    <span class="order-total-amount">Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></span>
                </div>
                
                <div class="payment-info">
                    <div class="payment-info-title">Cara Pembayaran</div>
                    <div class="payment-info-content">
                        <p>
                            Pembayaran dapat dilakukan di <strong>outlet TEFA Coffee</strong> dengan 2 metode:
                        </p>
                        <p>
                            <strong>Tunai </strong> atau <strong>QRIS</strong> (OVO, GoPay, Dana, ShopeePay, Mobile Banking)
                        </p>
                        <p>
                            atau hubungi kami untuk metode pembayaran lainnya.
                        </p>
                        <ul class="payment-steps">
                            <li>Datang ke outlet TEFA Coffee </li>
                            <li>Tunjukkan kode transaksi atau struk ke kasir</li>
                            <li>Bayar & ambil pesanan Anda</li>
                        </ul>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="button" class="btn-premium secondary" onclick="showReceiptModal()">
                        <i class="fas fa-receipt"></i> Lihat Struk
                    </button>
                    <a href="dashboard.php" class="btn-premium primary">
                        <i class="fas fa-home"></i> dashboard
                    </a>
                </div>
                
            </div>
            
            <div class="footer-note">
                <i class="fas fa-clock"></i>
                Jam operasional: Senin-Sabtu, 08.00-17.00 WIB
            </div>
            
        </div>
    </div>

    <!-- RECEIPT MODAL - SAME AS DASHBOARD -->
    <div class="modal fade" id="receiptModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content receipt-modal-content">
                <div class="modal-header receipt-modal-header">
                    
                </div>
                <div class="modal-body receipt-modal-body p-0">
                    <!-- Header Struk -->
                    <div style="text-align: center; margin-bottom: 15px; border-bottom: 1px dashed #000; padding-bottom: 15px;">
                        <img src="../assets/images/logopolije.png" alt="Polije" style="width: 60px; height: 60px; margin-bottom: 10px;" onerror="this.style.display='none'">
                        <div style="font-weight: bold; font-size: 14px; margin: 5px 0;">TEFA COFFEE</div>
                        <div style="font-size: 9px; line-height: 1.4;">
                            Politeknik Negeri Jember<br>
                            Jl. Mastrip, Kotak Pos 164<br>
                            Jember 68101, Jawa Timur<br>
                            <i class="fas fa-phone"></i> 0812-3456-7890
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div style="border: 1px solid #000; padding: 5px; margin-bottom: 15px; text-align: center; font-weight: bold; font-size: 10px; text-transform: uppercase;" id="receiptStatus">
                        PENDING
                    </div>

                    <!-- Info Customer -->
                    <div style="font-size: 10px; margin-bottom: 15px; line-height: 1.6;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-weight: bold;">No:</span>
                            <span style="font-family: monospace;" id="receiptKode"></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-weight: bold;">Tgl:</span>
                            <span id="receiptTanggal"></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-weight: bold;">Nama:</span>
                            <span id="receiptNama"></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-weight: bold;">Telp:</span>
                            <span id="receiptTelepon"></span>
                        </div>
                    </div>

                    <!-- Status Pengambilan -->
                    <div style="border: 1px solid #000; padding: 8px; margin-bottom: 15px; font-size: 9px;">
                        <div style="text-align: center; font-weight: bold; margin-bottom: 5px;">PENGAMBILAN PRODUK
                        </div>
                        <div id="receiptPickupStatus" style="text-align: center; font-weight: bold;"></div>
                        <div style="text-align: center; margin-top: 3px; font-size: 8px;" id="receiptPickupInfo"></div>
                    </div>

                    <!-- Items -->
                    <div style="border-top: 2px solid #000; border-bottom: 1px dashed #000; padding: 5px 0; text-align: center; font-weight: bold; font-size: 10px; margin-bottom: 10px;">
                        ITEM PESANAN
                    </div>
                    <div id="receiptItems" style="font-size: 9px; margin-bottom: 15px; line-height: 1.8;"></div>

                    <!-- Total -->
                    <div style="border-top: 2px solid #000; padding-top: 8px; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 12px;">
                            <span>TOTAL</span>
                            <span id="receiptTotal"></span>
                        </div>
                    </div>
                    <!-- Footer Struk -->
                    <div style="text-align: center; border-top: 2px solid #000; padding-top: 10px; font-size: 9px; line-height: 1.6;">
                        <div style="font-weight: bold; margin-bottom: 5px;">*** TERIMA KASIH ***</div>
                        <div>Dukungan Anda membantu</div>
                        <div>mahasiswa Politeknik Jember</div>
                        <div style="font-weight: bold; margin-top: 5px;">Struk ini sah tanpa tanda tangan</div>
                        <div style="margin-top: 5px; font-size: 8px;" id="receiptTimestamp"></div>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="modal-footer receipt-modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="margin-right: 5px;">Tutup
                    </button>
                    <button type="button" class="btn btn-sm" onclick="printModalReceipt()" style="background: #2C1810; color: #fff; border: none;">
                        <i class="fas fa-print"></i> Cetak
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js?v=<?= time() ?>"></script>
    <script>
        const transaksiData = <?= json_encode($data) ?>;
        
        function formatRupiahPro(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount).replace('Rp', 'Rp ').trim();
        }
        
        function formatDateIndonesia(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${day}/${month}/${year} ${hours}:${minutes}`;
        }
        
        function showReceiptModal() {
            // Populate data
            document.getElementById('receiptKode').textContent = transaksiData.kode_transaksi;
            document.getElementById('receiptTanggal').textContent = formatDateIndonesia(transaksiData.tanggal_transaksi);
            document.getElementById('receiptNama').textContent = '<?= htmlspecialchars($_SESSION['nama']) ?>';
            document.getElementById('receiptTelepon').textContent = '-';
            
            // Status pembayaran
            const statusEl = document.getElementById('receiptStatus');
            statusEl.textContent = transaksiData.status_pembayaran.toUpperCase();
            statusEl.style.background = transaksiData.status_pembayaran === 'lunas' ? '#000' : '#fff';
            statusEl.style.color = transaksiData.status_pembayaran === 'lunas' ? '#fff' : '#000';
            
            // Status pengambilan
            const pickupStatusEl = document.getElementById('receiptPickupStatus');
            const pickupInfoEl = document.getElementById('receiptPickupInfo');
            if (transaksiData.status_pengambilan === 'sudah_diambil') {
                pickupStatusEl.innerHTML = '✓ SUDAH DIAMBIL';
                pickupInfoEl.innerHTML = formatDateIndonesia(transaksiData.tanggal_diambil) + '<br>Oleh: ' + transaksiData.diambil_oleh;
            } else {
                pickupStatusEl.innerHTML = '⏳ BELUM DIAMBIL';
                pickupInfoEl.innerHTML = 'Tunjukkan struk ini ke admin saat ambil';
            }
            
            // Fetch items
            fetch(`receipt.php?kode=<?= urlencode($kode_transaksi) ?>&format=json`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.items && Array.isArray(data.items)) {
                    let itemsHtml = '';
                    data.items.forEach(item => {
                        const qty = item.qty || item.quantity || 1;
                        const subtotal = item.subtotal || (item.harga * qty);
                        itemsHtml += `
                            <div class="receipt-item">
                                <span class="item-name">${item.nama_produk || item.nama}</span>
                                <span class="item-qty">${qty}x</span>
                                <span class="item-price">Rp ${formatRupiahPro(subtotal).replace('Rp ', '')}</span>
                            </div>
                        `;
                    });
                    document.getElementById('receiptItems').innerHTML = itemsHtml;
                }
                
                document.getElementById('receiptTotal').textContent = 'Rp ' + formatRupiahPro(data.total_harga).replace('Rp ', '');
                
                // Payment method
                const paymentMethod = data.metode_pembayaran || data.payment_method || 'cod';
                const paymentEl = document.getElementById('receiptPayment');
                const paymentDescEl = document.getElementById('receiptPaymentDesc');
                
                if (paymentMethod === 'qris') {
                    paymentEl.textContent = 'QRIS (Digital)';
                    paymentDescEl.textContent = 'Pembayaran via QR Code';
                } else {
                    paymentEl.textContent = 'COD (Cash)';
                    paymentDescEl.textContent = 'Bayar tunai di outlet TEFA Coffee';
                }
                
                document.getElementById('receiptTimestamp').textContent = 'Dicetak: ' + new Date().toLocaleString('id-ID');
            })
            .catch(error => {
                console.error('Error:', error);
            });
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
            modal.show();
        }
        
        function printModalReceipt() {
            const printWindow = window.open('', '_blank', 'width=400,height=700');
            const modalContent = document.querySelector('#receiptModal .modal-content');
            
            if (!modalContent) {
                alert('Gagal memuat struk untuk dicetak');
                return;
            }
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Cetak Struk</title>
                    <style>
                        body { 
                            font-family: 'Courier New', Courier, monospace; 
                            padding: 20px; 
                            background: white; 
                            margin: 0; 
                        }
                        .modal-content { 
                            box-shadow: none !important; 
                            border: none !important; 
                            max-width: 100%; 
                        }
                        .modal-footer, .btn-close { 
                            display: none !important; 
                        }
                        @media print { 
                            body { padding: 0; } 
                            .no-print { display: none !important; } 
                        }
                    </style>
                </head>
                <body>${modalContent.outerHTML}</body>
                </html>
            `);
            printWindow.document.close();
            setTimeout(() => printWindow.print(), 500);
        }
    </script>
</body>
</html>