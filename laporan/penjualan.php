<?php
// laporan/penjualan.php
// Halaman Laporan Penjualan - Diakses oleh Admin, Kasir, dan Owner

include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';

// Default filter tanggal: awal bulan ini s/d hari ini
$tgl_awal = isset($_GET['tgl_awal']) ? trim(mysqli_real_escape_string($conn, $_GET['tgl_awal'])) : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? trim(mysqli_real_escape_string($conn, $_GET['tgl_akhir'])) : date('Y-m-d');

// Query Summary Statistik Penjualan
$stats_query = mysqli_query($conn, "
    SELECT 
        SUM(total_harga) AS total_omzet,
        COUNT(id) AS total_transaksi,
        AVG(total_harga) AS rata_rata_transaksi
    FROM penjualan
    WHERE DATE(tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
");
$stats = mysqli_fetch_assoc($stats_query);
$total_omzet = (float)($stats['total_omzet'] ?? 0);
$total_transaksi = (int)($stats['total_transaksi'] ?? 0);
$rata_rata = (float)($stats['rata_rata_transaksi'] ?? 0);
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
                <span class="text-indigo-600">Penjualan</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Laporan Penjualan</h1>
            <p class="text-xs text-slate-400 font-medium font-semibold">Tinjau seluruh riwayat omzet omset dan performa transaksi gerai penjualan Anda.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <form action="penjualan.php" method="GET" class="flex flex-col md:flex-row items-end gap-4 text-xs">
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
                <a href="penjualan.php" class="w-full md:w-auto bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-5 py-2.5 rounded-xl transition-all flex items-center justify-center space-x-1.5">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Omzet -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Pendapatan (Omzet)</span>
                <p class="text-xl font-extrabold text-slate-800"><?= rupiah($total_omzet) ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i data-lucide="trending-up" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Total Transaksi -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Jumlah Transaksi</span>
                <p class="text-xl font-extrabold text-slate-800"><?= number_format($total_transaksi, 0, ',', '.') ?> Nota</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="receipt" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Rata-rata Nilai Transaksi -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Rata-rata Nilai / Nota</span>
                <p class="text-xl font-extrabold text-slate-800"><?= rupiah($rata_rata) ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i data-lucide="calculator" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table id="table-laporan-penjualan" class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-12">No</th>
                        <th class="py-3 px-4">Invoice</th>
                        <th class="py-3 px-4">Tanggal & Waktu</th>
                        <th class="py-3 px-4">Kasir</th>
                        <th class="py-3 px-4 text-right">Total Transaksi</th>
                        <th class="py-3 px-4 text-right">Nominal Tunai</th>
                        <th class="py-3 px-4 text-right">Kembalian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    <?php
                    $sales_list_query = mysqli_query($conn, "
                        SELECT p.*, u.nama_lengkap AS kasir_nama 
                        FROM penjualan p 
                        JOIN pengguna u ON p.id_kasir = u.id 
                        WHERE DATE(p.tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
                        ORDER BY p.tanggal DESC
                    ");
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($sales_list_query)):
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-4 font-medium text-slate-500"><?= $no++ ?></td>
                            <td class="py-3.5 px-4 font-bold text-indigo-600 hover:underline">
                                <a href="/transaksi/riwayat.php?invoice=<?= urlencode($row['invoice']) ?>">#<?= htmlspecialchars($row['invoice']) ?></a>
                            </td>
                            <td class="py-3.5 px-4"><?= tanggal_indo($row['tanggal'], true) ?></td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700"><?= htmlspecialchars($row['kasir_nama']) ?></td>
                            <td class="py-3.5 px-4 text-right font-extrabold text-slate-800"><?= rupiah($row['total_harga']) ?></td>
                            <td class="py-3.5 px-4 text-right"><?= rupiah($row['bayar']) ?></td>
                            <td class="py-3.5 px-4 text-right text-slate-400"><?= rupiah($row['kembalian']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#table-laporan-penjualan').DataTable({
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
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Tidak ada transaksi dalam periode ini",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada transaksi",
            infoFiltered: "(difilter dari _MAX_ total data)"
        },
        drawCallback: function() {
            lucide.createIcons();
        }
    });
});
</script>

<?php include '../layouts/footer.php'; ?>
