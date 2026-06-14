<?php
// keuangan/laba_bersih.php
// Halaman Analisis Laba Bersih (Net Profit) - Hanya untuk Admin dan Owner

include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';

// Validasi Hak Akses: Admin dan Owner
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator atau Owner!');
    echo "<script>window.location.href='/smart-cashier/dashboard/index.php';</script>";
    exit();
}

// Default filter tanggal: awal bulan ini s/d hari ini
$tgl_awal = isset($_GET['tgl_awal']) ? trim(mysqli_real_escape_string($conn, $_GET['tgl_awal'])) : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? trim(mysqli_real_escape_string($conn, $_GET['tgl_akhir'])) : date('Y-m-d');

// 1. Hitung Laba Kotor (Revenue - COGS)
$laba_kotor_query = mysqli_query($conn, "
    SELECT SUM(pd.subtotal - (pd.harga_beli * pd.qty)) AS total_laba_kotor
    FROM penjualan_detail pd
    JOIN penjualan p ON pd.id_penjualan = p.id
    WHERE DATE(p.tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$laba_kotor = (float)(mysqli_fetch_assoc($laba_kotor_query)['total_laba_kotor'] ?? 0);

// 2. Hitung Pengeluaran Operasional
$pengeluaran_query = mysqli_query($conn, "
    SELECT SUM(nominal) AS total_pengeluaran 
    FROM pengeluaran 
    WHERE tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$pengeluaran = (float)(mysqli_fetch_assoc($pengeluaran_query)['total_pengeluaran'] ?? 0);

// 3. Hitung Laba Bersih
$laba_bersih = $laba_kotor - $pengeluaran;
?>

<!-- Container Utama -->
<div class="space-y-8">
    
    <!-- Page Header & Breadcrumbs -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <nav class="flex text-xs text-slate-400 space-x-2 mb-2 font-medium">
                <a href="/smart-cashier/dashboard/index.php" class="hover:text-slate-600">Dashboard</a>
                <span>/</span>
                <span class="text-slate-600">Keuangan</span>
                <span>/</span>
                <span class="text-indigo-600">Laba Bersih</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Laba Bersih (Net Profit)</h1>
            <p class="text-xs text-slate-400 font-medium font-semibold">Tinjau pendapatan bersih setelah dikurangi seluruh biaya HPP produk dan pengeluaran operasional.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <form action="laba_bersih.php" method="GET" class="flex flex-col md:flex-row items-end gap-4 text-xs">
            <div class="flex-1 w-full space-y-2">
                <label for="tgl_awal" class="block font-semibold text-slate-500 uppercase tracking-wider">Tanggal Awal</label>
                <input type="date" name="tgl_awal" id="tgl_awal" value="<?= $tgl_awal ?>" required
                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-600 focus:bg-white transition-colors">
            </div>
            
            <div class="flex-1 w-full space-y-2">
                <label for="tgl_akhir" class="block font-semibold text-slate-500 uppercase tracking-wider">Tanggal Akhir</label>
                <input type="date" name="tgl_akhir" id="tgl_akhir" value="<?= $tgl_akhir ?>" required
                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-600 focus:bg-white transition-colors">
            </div>
            
            <div class="w-full md:w-auto flex space-x-2">
                <button type="submit" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-xl transition-all cursor-pointer shadow-sm flex items-center justify-center space-x-1.5">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Terapkan Filter</span>
                </button>
                <a href="laba_bersih.php" class="w-full md:w-auto bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-5 py-2.5 rounded-xl transition-all flex items-center justify-center space-x-1.5">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Laba Kotor -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Akumulasi Laba Kotor</span>
                <p class="text-xl font-extrabold text-slate-800"><?= rupiah($laba_kotor) ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-slate-50 text-slate-500 flex items-center justify-center">
                <i data-lucide="dollar-sign" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Pengeluaran Operasional -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Biaya Operasional</span>
                <p class="text-xl font-extrabold text-red-500"><?= rupiah($pengeluaran) ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                <i data-lucide="wallet" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Laba Bersih -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Laba Bersih (Net Profit)</span>
                <?php if ($laba_bersih >= 0): ?>
                    <p class="text-xl font-extrabold text-emerald-600"><?= rupiah($laba_bersih) ?></p>
                <?php else: ?>
                    <p class="text-xl font-extrabold text-red-600"><?= rupiah($laba_bersih) ?> (Rugi)</p>
                <?php endif; ?>
            </div>
            <?php if ($laba_bersih >= 0): ?>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="award" class="w-6 h-6"></i>
                </div>
            <?php else: ?>
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Comparative Table of Inflow vs Outflow -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Rincian Pemasukan (Laba Kotor dari Transaksi) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-50 flex items-center space-x-1.5">
                <i data-lucide="trending-up" class="w-4 h-4 text-emerald-600"></i>
                <span>Rincian Pemasukan Laba Kotor</span>
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px] border-collapse" id="table-inflow">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase">
                            <th class="py-2.5 px-3">Invoice</th>
                            <th class="py-2.5 px-3">Tanggal</th>
                            <th class="py-2.5 px-3 text-right">Laba Kotor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-slate-600">
                        <?php
                        $inflow_query = mysqli_query($conn, "
                            SELECT p.invoice, p.tanggal, SUM(pd.subtotal - (pd.harga_beli * pd.qty)) AS laba_kotor
                            FROM penjualan_detail pd
                            JOIN penjualan p ON pd.id_penjualan = p.id
                            WHERE DATE(p.tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
                            GROUP BY p.id
                            ORDER BY p.tanggal DESC
                        ");
                        if (mysqli_num_rows($inflow_query) > 0):
                            while ($inf = mysqli_fetch_assoc($inflow_query)):
                        ?>
                                <tr>
                                    <td class="py-2.5 px-3 font-bold text-slate-700">#<?= htmlspecialchars($inf['invoice']) ?></td>
                                    <td class="py-2.5 px-3 text-slate-400"><?= date('d/m/y H:i', strtotime($inf['tanggal'])) ?></td>
                                    <td class="py-2.5 px-3 text-right font-bold text-emerald-600"><?= rupiah($inf['laba_kotor']) ?></td>
                                </tr>
                            <?php 
                            endwhile;
                        else:
                            echo "<tr><td colspan='3' class='py-4 text-center text-slate-400 italic'>Tidak ada pemasukan.</td></tr>";
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rincian Pengeluaran -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-50 flex items-center space-x-1.5">
                <i data-lucide="trending-down" class="w-4 h-4 text-red-600"></i>
                <span>Rincian Pengeluaran Operasional</span>
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px] border-collapse" id="table-outflow">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase">
                            <th class="py-2.5 px-3">Pengeluaran</th>
                            <th class="py-2.5 px-3">Tanggal</th>
                            <th class="py-2.5 px-3 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-slate-600">
                        <?php
                        $outflow_query = mysqli_query($conn, "
                            SELECT nama_pengeluaran, tanggal, nominal 
                            FROM pengeluaran 
                            WHERE tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'
                            ORDER BY tanggal DESC
                        ");
                        if (mysqli_num_rows($outflow_query) > 0):
                            while ($outf = mysqli_fetch_assoc($outflow_query)):
                        ?>
                                <tr>
                                    <td class="py-2.5 px-3 font-semibold text-slate-700"><?= htmlspecialchars($outf['nama_pengeluaran']) ?></td>
                                    <td class="py-2.5 px-3 text-slate-400"><?= date('d/m/y', strtotime($outf['tanggal'])) ?></td>
                                    <td class="py-2.5 px-3 text-right font-bold text-red-500"><?= rupiah($outf['nominal']) ?></td>
                                </tr>
                            <?php 
                            endwhile;
                        else:
                            echo "<tr><td colspan='3' class='py-4 text-center text-slate-400 italic'>Tidak ada pengeluaran.</td></tr>";
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#table-inflow').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25],
        language: { search: "Cari:" }
    });
    $('#table-outflow').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25],
        language: { search: "Cari:" }
    });
});
</script>

<?php include '../layouts/footer.php'; ?>
