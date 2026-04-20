  
        //  Hamburger Menu Toggle
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
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) toggleSidebar();
            });
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Modal Functions
        function openEditModal(id, nama, kategori, jumlah, kondisi, keterangan) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_kategori').value = kategori;
            document.getElementById('edit_jumlah').value = jumlah;
            document.getElementById('edit_kondisi').value = kondisi;
            document.getElementById('edit_keterangan').value = keterangan;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function openUsageModal(id, nama) {
            document.getElementById('usage_id').value = id;
            document.getElementById('usage_nama').textContent = nama;
            new bootstrap.Modal(document.getElementById('usageModal')).show();
        }

        // Open Print Report for Usage History
        function openPrintUsageReport() {
            const barang = document.getElementById('filter_barang')?.value || '';
            const jenis = document.getElementById('filter_jenis')?.value || '';
            const pengguna = document.getElementById('filter_pengguna')?.value || '';
            const dateFrom = document.getElementById('filter_date_from')?.value || '';
            const dateTo = document.getElementById('filter_date_to')?.value || '';
            const params = new URLSearchParams();
            params.append('print', '1');
            if (barang) params.append('filter_barang', barang);
            if (jenis) params.append('filter_jenis', jenis);
            if (pengguna) params.append('filter_pengguna', pengguna);
            if (dateFrom) params.append('filter_date_from', dateFrom);
            if (dateTo) params.append('filter_date_to', dateTo);
            window.location.href = 'inventory.php?' + params.toString();
        }

        // Apply Filter
        function applyFilter() {
            const barang = document.getElementById('filter_barang').value;
            const jenis = document.getElementById('filter_jenis').value;
            const pengguna = document.getElementById('filter_pengguna').value;
            const dateFrom = document.getElementById('filter_date_from').value;
            const dateTo = document.getElementById('filter_date_to').value;
            updateActiveFilters(barang, jenis, pengguna, dateFrom, dateTo);
            const params = new URLSearchParams();
            if (barang) params.append('filter_barang', barang);
            if (jenis) params.append('filter_jenis', jenis);
            if (pengguna) params.append('filter_pengguna', pengguna);
            if (dateFrom) params.append('filter_date_from', dateFrom);
            if (dateTo) params.append('filter_date_to', dateTo);
            window.location.href = 'inventory.php?' + params.toString();
        }

        //  Reset Filters
        function resetFilters() {
            window.location.href = 'inventory.php';
        }

        // Update Active Filters Indicator
        function updateActiveFilters(barang, jenis, pengguna, dateFrom, dateTo) {
            const indicator = document.getElementById('activeFilters');
            const text = document.getElementById('activeFiltersText');
            if (!indicator || !text) return;
            const filters = [];
            if (barang) {
                const select = document.getElementById('filter_barang');
                const name = select.options[select.selectedIndex].text;
                filters.push(`Barang: ${name}`);
            }
            if (jenis) filters.push(`Jenis: ${jenis}`);
            if (pengguna) filters.push(`Pengguna: ${pengguna}`);
            if (dateFrom) filters.push(`Dari: ${dateFrom}`);
            if (dateTo) filters.push(`Sampai: ${dateTo}`);
            if (filters.length > 0) {
                text.textContent = filters.join(' • ');
                indicator.classList.add('show');
            } else {
                indicator.classList.remove('show');
            }
        }

        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function () {
            const barang = document.getElementById('filter_barang')?.value;
            const jenis = document.getElementById('filter_jenis')?.value;
            const pengguna = document.getElementById('filter_pengguna')?.value;
            const dateFrom = document.getElementById('filter_date_from')?.value;
            const dateTo = document.getElementById('filter_date_to')?.value;
            if (barang || jenis || pengguna || dateFrom || dateTo) {
                updateActiveFilters(barang, jenis, pengguna, dateFrom, dateTo);
            }
        });
    