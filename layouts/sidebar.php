<?php
// layouts/sidebar.php
// Sidebar navigasi kiri dengan kontrol hak akses (Role-Based Access Control)

$user_role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['nama_lengkap'] ?? 'User';
?>
<style>
    @media (max-width: 1023px) {
        #sidebar-navigation {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            transform: translateX(-100%) !important;
            transition: transform 0.3s ease-in-out !important;
            z-index: 50 !important;
        }
        #sidebar-navigation.open {
            transform: translateX(0) !important;
        }
        #sidebar-overlay {
            display: none;
            position: fixed !important;
            inset: 0 !important;
            background-color: rgba(15, 23, 42, 0.4) !important;
            backdrop-filter: blur(4px) !important;
            z-index: 40 !important;
        }
        #sidebar-overlay.open {
            display: block !important;
        }
    }
</style>

<!-- Sidebar Kiri -->
<aside id="sidebar-navigation" class="w-[280px] bg-white border-r border-slate-200 h-screen lg:sticky lg:top-0 flex flex-col flex-shrink-0">
    <!-- Logo Section -->
    <div class="p-6 border-b border-slate-100 flex items-center space-x-3 flex-shrink-0">
        <?php if (!empty($logo_toko) && file_exists(dirname(__DIR__) . '/assets/uploads/' . $logo_toko)): ?>
            <img src="/assets/uploads/<?= htmlspecialchars($logo_toko) ?>" alt="Logo" class="w-10 h-10 rounded-xl object-cover shadow-md shadow-indigo-100">
        <?php else: ?>
            <img src="https://ppdb.smkalmujtamak.sch.id/logo-amt.webp" alt="Logo" class="w-10 h-10 rounded-xl object-cover shadow-md shadow-indigo-100">
        <?php endif; ?>
        <div class="overflow-hidden">
            <h1 class="font-bold text-slate-800 tracking-tight text-sm leading-tight truncate" title="<?= htmlspecialchars($nama_toko) ?>"><?= htmlspecialchars($nama_toko) ?></h1>
            <span class="text-[10px] text-slate-400 font-semibold tracking-wide uppercase block truncate mt-1" title="<?= htmlspecialchars($slogan_toko) ?>"><?= htmlspecialchars($slogan_toko) ?></span>
        </div>
    </div>

    <!-- Navigation Menus (Scrollable) -->
    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-7">
            <!-- UTAMA -->
            <div>
                <span class="text-xs font-semibold text-slate-400 tracking-wider uppercase px-3">Utama</span>
                <div class="mt-2 space-y-1">
                    <a href="/dashboard/index.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/dashboard/') ?>">
                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>

            <!-- MASTER DATA (Only Admin) -->
            <?php if ($user_role === 'admin'): ?>
            <div>
                <span class="text-xs font-semibold text-slate-400 tracking-wider uppercase px-3">Master Data</span>
                <div class="mt-2 space-y-1">
                    <a href="/master/produk/index.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/master/produk/') ?>">
                        <i data-lucide="package" class="w-4 h-4"></i>
                        <span>Produk</span>
                    </a>
                    <a href="/master/kategori/index.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/master/kategori/') ?>">
                        <i data-lucide="tags" class="w-4 h-4"></i>
                        <span>Kategori</span>
                    </a>
                    <a href="/master/pengguna/index.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/master/pengguna/') ?>">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>Pengguna</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- TRANSAKSI (Admin and Kasir) -->
            <?php if ($user_role === 'admin' || $user_role === 'kasir'): ?>
            <div>
                <span class="text-xs font-semibold text-slate-400 tracking-wider uppercase px-3">Transaksi</span>
                <div class="mt-2 space-y-1">
                    <a href="/transaksi/kasir.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/transaksi/kasir.php') ?>">
                        <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                        <span>Kasir (POS)</span>
                    </a>
                    <a href="/transaksi/riwayat.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/transaksi/riwayat.php') ?>">
                        <i data-lucide="history" class="w-4 h-4"></i>
                        <span>Riwayat Transaksi</span>
                    </a>
                    <a href="/transaksi/retur.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/transaksi/retur.php') ?>">
                        <i data-lucide="undo-2" class="w-4 h-4"></i>
                        <span>Retur Penjualan</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- KEUANGAN (Admin and Owner) -->
            <?php if ($user_role === 'admin' || $user_role === 'owner'): ?>
            <div>
                <span class="text-xs font-semibold text-slate-400 tracking-wider uppercase px-3">Keuangan</span>
                <div class="mt-2 space-y-1">
                    <a href="/keuangan/pengeluaran.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/keuangan/pengeluaran.php') ?>">
                        <i data-lucide="wallet" class="w-4 h-4"></i>
                        <span>Pengeluaran</span>
                    </a>
                    <a href="/keuangan/laba_kotor.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/keuangan/laba_kotor.php') ?>">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                        <span>Laba Kotor</span>
                    </a>
                    <a href="/keuangan/laba_bersih.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/keuangan/laba_bersih.php') ?>">
                        <i data-lucide="line-chart" class="w-4 h-4"></i>
                        <span>Laba Bersih</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- LAPORAN & ANALITIK (Admin and Owner) -->
            <?php if ($user_role === 'admin' || $user_role === 'owner'): ?>
            <div>
                <span class="text-xs font-semibold text-slate-400 tracking-wider uppercase px-3">Laporan & Analitik</span>
                <div class="mt-2 space-y-1">
                    <a href="/laporan/penjualan.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/laporan/penjualan.php') ?>">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        <span>Laporan Penjualan</span>
                    </a>
                    <a href="/laporan/stok.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/laporan/stok.php') ?>">
                        <i data-lucide="package-search" class="w-4 h-4"></i>
                        <span>Laporan Stok</span>
                    </a>
                    <a href="/laporan/produk_terlaris.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/laporan/produk_terlaris.php') ?>">
                        <i data-lucide="star" class="w-4 h-4"></i>
                        <span>Produk Terlaris</span>
                    </a>
                    <a href="/laporan/keuntungan.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/laporan/keuntungan.php') ?>">
                        <i data-lucide="dollar-sign" class="w-4 h-4"></i>
                        <span>Laporan Keuntungan</span>
                    </a>
                    <a href="/analitik/grafik_penjualan.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/analitik/grafik_penjualan.php') ?>">
                        <i data-lucide="pie-chart" class="w-4 h-4"></i>
                        <span>Grafik Analitik</span>
                    </a>
                    <a href="/analitik/prediksi_stok.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/analitik/prediksi_stok.php') ?>">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>Prediksi Stok Habis</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- PENGATURAN -->
            <div>
                <span class="text-xs font-semibold text-slate-400 tracking-wider uppercase px-3">Pengaturan</span>
                <div class="mt-2 space-y-1">
                    <?php if ($user_role === 'admin' || $user_role === 'owner'): ?>
                    <a href="/pengaturan/profil.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/pengaturan/profil.php') ?>">
                        <i data-lucide="settings" class="w-4 h-4"></i>
                        <span>Profil Toko</span>
                    </a>
                    <a href="/pengaturan/backup.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/pengaturan/backup.php') ?>">
                        <i data-lucide="database" class="w-4 h-4"></i>
                        <span>Backup Database</span>
                    </a>
                    <?php endif; ?>
                    <a href="/pengaturan/ganti_password.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all <?= is_active('/pengaturan/ganti_password.php') ?>">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                        <span>Ganti Password</span>
                    </a>
                </div>
            </div>
            </nav>

        <!-- Current User Profile Card in Sidebar Footer -->
        <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center space-x-3 overflow-hidden">
                <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-sm flex-shrink-0">
                    <?= strtoupper(substr($user_name, 0, 2)) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-semibold text-slate-800 truncate leading-tight"><?= htmlspecialchars($user_name) ?></p>
                    <span class="text-[10px] bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded-md font-semibold uppercase tracking-wider"><?= htmlspecialchars($user_role) ?></span>
                </div>
            </div>
            <a href="/login.php?action=logout" class="text-slate-400 hover:text-red-500 transition-colors p-1" title="Keluar">
                <i data-lucide="log-out" class="w-4 h-4"></i>
            </a>
        </div>
    </aside>

<!-- Overlay untuk Sidebar Drawer di Mobile -->
<div id="sidebar-overlay" onclick="toggleSidebarDrawer()" class="lg:hidden"></div>

<!-- Area Content Utama (Kanan) -->
<div class="flex-1 flex flex-col min-w-0">
