<?php
// keuangan/laba_kotor.php
// Halaman Analisis Laba Kotor - Hanya untuk Admin dan Owner

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

// Query Summary KPI Laba Kotor
$summary_query = "
    SELECT 
        SUM(pd.subtotal) AS total_omzet,
        SUM(pd.harga_beli * pd.qty) AS total_modal,
        SUM(pd.subtotal - (pd.harga_beli * pd.qty)) AS total_laba_kotor
    FROM penjualan_detail pd
    JOIN penjualan p ON pd.id_penjualan = p.id
    WHERE DATE(p.tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
";
$summary_res = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_res);

$total_omzet = (float)($summary['total_omzet'] ?? 0);
$total_modal = (float)($summary['total_modal'] ?? 0);
$total_laba_kotor = (float)($summary['total_laba_kotor'] ?? 0);
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
                <span class="text-indigo-600">Laba Kotor</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Analisis Laba Kotor (Gross Profit)</h1>
            <p class="text-xs text-slate-400 font-medium">Lihat ringkasan margin keuntungan kotor sebelum dikurangi biaya operasional/pengeluaran.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <form action="laba_kotor.php" method="GET" class="flex flex-col md:flex-row items-end gap-4 text-xs">
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
                <a href="laba_kotor.php" class="w-full md:w-auto bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-5 py-2.5 rounded-xl transition-all flex items-center justify-center space-x-1.5">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Omzet -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Pendapatan (Omzet)</span>
                <p class="text-xl font-extrabold text-slate-800"><?= rupiah($total_omzet) ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i data-lucide="trending-up" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- HPP / Cost of Goods Sold -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Harga Pokok Penjualan (HPP)</span>
                <p class="text-xl font-extrabold text-slate-800"><?= rupiah($total_modal) ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i data-lucide="shopping-bag" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Laba Kotor -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Laba Kotor</span>
                <p class="text-xl font-extrabold text-emerald-600"><?= rupiah($total_laba_kotor) ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="dollar-sign" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Table breakdown Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-50">
            Rincian Margin Laba Kotor per Item Terjual
        </h3>
        <div class="overflow-x-auto">
            <table id="table-laba-kotor" class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-12">No</th>
                        <th class="py-3 px-4">Invoice</th>
                        <th class="py-3 px-4">Nama Produk</th>
                        <th class="py-3 px-4 text-center">Qty</th>
                        <th class="py-3 px-4 text-right">Harga Pokok (HPP)</th>
                        <th class="py-3 px-4 text-right">Harga Jual</th>
                        <th class="py-3 px-4 text-right">Margin / Pcs</th>
                        <th class="py-3 px-4 text-right">Subtotal Laba Kotor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    <?php
                    $detail_query = "
                        SELECT 
                            p.invoice, p.tanggal, 
                            pd.qty, pd.harga_beli, pd.harga_jual, pd.subtotal,
                            pr.nama_produk
                        FROM penjualan_detail pd
                        JOIN penjualan p ON pd.id_penjualan = p.id
                        JOIN produk pr ON pd.id_produk = pr.id
                        WHERE DATE(p.tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
                        ORDER BY p.tanggal DESC
                    ";
                    $result = mysqli_query($conn, $detail_query);
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)):
                        $margin_pcs = $row['harga_jual'] - $row['harga_beli'];
                        $subtotal_laba = $margin_pcs * $row['qty'];
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 px-4 font-medium text-slate-500"><?= $no++ ?></td>
                            <td class="py-3 px-4 font-bold text-indigo-600">#<?= htmlspecialchars($row['invoice']) ?></td>
                            <td class="py-3 px-4 font-semibold text-slate-700"><?= htmlspecialchars($row['nama_produk']) ?></td>
                            <td class="py-3 px-4 text-center font-bold"><?= $row['qty'] ?> Pcs</td>
                            <td class="py-3 px-4 text-right"><?= rupiah($row['harga_beli']) ?></td>
                            <td class="py-3 px-4 text-right"><?= rupiah($row['harga_jual']) ?></td>
                            <td class="py-3 px-4 text-right font-medium text-slate-500"><?= rupiah($margin_pcs) ?></td>
                            <td class="py-3 px-4 text-right font-extrabold text-emerald-600"><?= rupiah($subtotal_laba) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#table-laba-kotor').DataTable({
        layout: {
            topStart: {
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<span class="flex items-center space-x-1"><i data-lucide="sheet" class="w-4 h-4"></i><span>Excel</span></span>',
                        className: 'bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors inline-block mr-2 cursor-pointer shadow-sm shadow-emerald-100 border-0'
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<span class="flex items-center space-x-1"><i data-lucide="file" class="w-4 h-4"></i><span>PDF</span></span>',
                        className: 'bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors inline-block mr-2 cursor-pointer shadow-sm shadow-red-100 border-0'
                    },
                    {
                        extend: 'print',
                        text: '<span class="flex items-center space-x-1"><i data-lucide="printer" class="w-4 h-4"></i><span>Cetak</span></span>',
                        className: 'bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors inline-block mr-2 cursor-pointer shadow-sm border-0'
                    }
                ]
            }
        },
        language: {
            search: "Cari data:",
            lengthMenu: "Tampilkan _MENU_ rincian per halaman",
            zeroRecords: "Data rincian kosong untuk periode ini",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada data rincian",
            infoFiltered: "(difilter dari _MAX_ total data)"
        },
        drawCallback: function() {
            lucide.createIcons();
        }
    });
});
</script>

<?php include '../layouts/footer.php'; ?>
