<?php
// laporan/keuntungan.php
// Halaman Laporan Keuntungan (Laporan Laba Rugi) - Hanya untuk Admin dan Owner

include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';

// Validasi Hak Akses: Admin dan Owner
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator atau Owner!');
    echo "<script>window.location.href='/dashboard/index.php';</script>";
    exit();
}

// Default filter bulan dan tahun
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// Dapatkan nama bulan Indonesia
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$start_date = "$tahun-" . sprintf("%02d", $bulan) . "-01";
$end_date = date("Y-m-t", strtotime($start_date));

// --- 1. PENDAPATAN PENJUALAN ---
// Penjualan Kotor (Omzet) & HPP (Modal)
$sales_query = mysqli_query($conn, "
    SELECT 
        SUM(pd.subtotal) AS total_omzet,
        SUM(pd.harga_beli * pd.qty) AS total_hpp
    FROM penjualan_detail pd
    JOIN penjualan p ON pd.id_penjualan = p.id
    WHERE DATE(p.tanggal) BETWEEN '$start_date' AND '$end_date'
");
$sales_data = mysqli_fetch_assoc($sales_query);
$penjualan_kotor = (float)($sales_data['total_omzet'] ?? 0);
$total_hpp = (float)($sales_data['total_hpp'] ?? 0);

// Nilai Retur Penjualan (Nominal produk dikembalikan)
$retur_query = mysqli_query($conn, "
    SELECT SUM(r.qty * p.harga_jual) AS total_retur 
    FROM retur r
    JOIN produk p ON r.id_produk = p.id
    WHERE r.tanggal BETWEEN '$start_date' AND '$end_date'
");
$retur_data = mysqli_fetch_assoc($retur_query);
$nominal_retur = (float)($retur_data['total_retur'] ?? 0);

// Penjualan Bersih
$penjualan_bersih = $penjualan_kotor - $nominal_retur;

// Laba Kotor
$laba_kotor = $penjualan_bersih - $total_hpp;


// --- 2. BIAYA OPERASIONAL (PENGELUARAN) ---
$expenses = [
    'Operasional' => 0,
    'Perlengkapan' => 0,
    'Sewa Tempat' => 0,
    'Gaji Karyawan' => 0,
    'Lainnya' => 0
];

$expense_query = mysqli_query($conn, "
    SELECT kategori, SUM(nominal) AS total 
    FROM pengeluaran 
    WHERE tanggal BETWEEN '$start_date' AND '$end_date'
    GROUP BY kategori
");
while ($row = mysqli_fetch_assoc($expense_query)) {
    if (array_key_exists($row['kategori'], $expenses)) {
        $expenses[$row['kategori']] = (float)$row['total'];
    } else {
        $expenses['Lainnya'] += (float)$row['total'];
    }
}

$total_pengeluaran = array_sum($expenses);

// --- 3. LABA BERSIH ---
$laba_bersih = $laba_kotor - $total_pengeluaran;
?>

<!-- Container Utama -->
<div class="space-y-8">
    
    <!-- Page Header & Breadcrumbs -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <nav class="flex text-xs text-slate-400 space-x-2 mb-2 font-medium">
                <a href="/dashboard/index.php" class="hover:text-slate-600">Dashboard</a>
                <span>/</span>
                <span class="text-slate-600">Laporan</span>
                <span>/</span>
                <span class="text-indigo-600">Laporan Keuntungan</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Laporan Keuntungan (Laba Rugi)</h1>
            <p class="text-xs text-slate-400 font-medium">Laporan rincian struktur laba rugi bulanan usaha Anda sesuai standar akuntansi komersial.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <form action="keuntungan.php" method="GET" class="flex flex-col md:flex-row items-end gap-4 text-xs">
            <div class="flex-1 w-full space-y-2">
                <label for="bulan" class="block font-semibold text-slate-500 uppercase tracking-wider">Pilih Bulan</label>
                <select name="bulan" id="bulan" required
                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-600 focus:bg-white transition-colors">
                    <?php foreach ($nama_bulan as $key => $val): ?>
                        <option value="<?= $key ?>" <?= $bulan === $key ? 'selected' : '' ?>><?= $val ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex-1 w-full space-y-2">
                <label for="tahun" class="block font-semibold text-slate-500 uppercase tracking-wider">Pilih Tahun</label>
                <select name="tahun" id="tahun" required
                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-600 focus:bg-white transition-colors">
                    <?php 
                    $current_year = (int)date('Y');
                    for ($y = $current_year - 3; $y <= $current_year + 1; $y++):
                    ?>
                        <option value="<?= $y ?>" <?= $tahun === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="w-full md:w-auto flex space-x-2">
                <button type="submit" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-xl transition-all cursor-pointer shadow-sm flex items-center justify-center space-x-1.5">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Tampilkan Laporan</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Layout Laporan Laba Rugi -->
    <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm max-w-3xl mx-auto">
        <!-- Header Laporan -->
        <div class="text-center pb-6 border-b border-slate-100 space-y-1">
            <h2 class="text-base font-bold text-slate-800 uppercase tracking-wide">LAPORAN LABA RUGI</h2>
            <p class="text-xs text-slate-500 font-medium">Periode: <?= $nama_bulan[$bulan] ?> <?= $tahun ?></p>
        </div>

        <!-- Tabel Akuntansi Laba Rugi -->
        <div class="py-6 space-y-6 text-xs text-slate-700">
            <!-- PENDAPATAN -->
            <div class="space-y-2">
                <h4 class="font-bold text-slate-800 uppercase tracking-wider">1. Pendapatan Penjualan</h4>
                <div class="pl-4 space-y-2">
                    <div class="flex justify-between">
                        <span>Penjualan Kotor (Omzet)</span>
                        <span><?= rupiah($penjualan_kotor) ?></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1 text-red-500">
                        <span>Retur Penjualan</span>
                        <span>(<?= rupiah($nominal_retur) ?>)</span>
                    </div>
                    <div class="flex justify-between font-bold text-slate-800 pt-1">
                        <span>Total Penjualan Bersih</span>
                        <span><?= rupiah($penjualan_bersih) ?></span>
                    </div>
                </div>
            </div>

            <!-- HARGA POKOK PENJUALAN (HPP) -->
            <div class="space-y-2">
                <h4 class="font-bold text-slate-800 uppercase tracking-wider">2. Harga Pokok Penjualan (HPP)</h4>
                <div class="pl-4 space-y-2">
                    <div class="flex justify-between border-b border-slate-100 pb-1 text-slate-600">
                        <span>Modal Pembelian Barang Terjual</span>
                        <span>(<?= rupiah($total_hpp) ?>)</span>
                    </div>
                    <div class="flex justify-between font-bold text-emerald-600 pt-1">
                        <span>LABA KOTOR (GROSS PROFIT)</span>
                        <span><?= rupiah($laba_kotor) ?></span>
                    </div>
                </div>
            </div>

            <!-- BIAYA OPERASIONAL (PENGELUARAN) -->
            <div class="space-y-2">
                <h4 class="font-bold text-slate-800 uppercase tracking-wider">3. Biaya Operasional / Pengeluaran</h4>
                <div class="pl-4 space-y-2 text-slate-600">
                    <div class="flex justify-between">
                        <span>Biaya Operasional Umum</span>
                        <span><?= rupiah($expenses['Operasional']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Biaya Perlengkapan</span>
                        <span><?= rupiah($expenses['Perlengkapan']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Biaya Sewa Tempat</span>
                        <span><?= rupiah($expenses['Sewa Tempat']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Biaya Gaji Karyawan</span>
                        <span><?= rupiah($expenses['Gaji Karyawan']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Biaya Lain-lain</span>
                        <span><?= rupiah($expenses['Lainnya']) ?></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1 text-red-500">
                        <span>Total Biaya Operasional</span>
                        <span>(<?= rupiah($total_pengeluaran) ?>)</span>
                    </div>
                </div>
            </div>

            <!-- HASIL AKHIR: LABA / RUGI BERSIH -->
            <div class="border-t-2 border-slate-800 pt-4 flex justify-between items-center">
                <h4 class="text-sm font-black text-slate-900 uppercase tracking-wide">LABA BERSIH (NET INCOME)</h4>
                <?php if ($laba_bersih >= 0): ?>
                    <span class="text-base font-black text-emerald-600"><?= rupiah($laba_bersih) ?></span>
                <?php else: ?>
                    <span class="text-base font-black text-red-600"><?= rupiah($laba_bersih) ?> (RUGI)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tombol Cetak / Simpan Laporan -->
        <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3">
            <button onclick="window.print()" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs px-5 py-2.5 rounded-xl flex items-center space-x-2 transition-all cursor-pointer">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak Laporan</span>
            </button>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
