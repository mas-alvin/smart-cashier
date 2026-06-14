<?php
// analitik/prediksi_stok.php
// Halaman Prediksi Stok Habis (AI Forecasting) - Hanya untuk Admin dan Owner

include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';

// Validasi Hak Akses: Admin dan Owner
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator atau Owner!');
    echo "<script>window.location.href='/smart-cashier/dashboard/index.php';</script>";
    exit();
}

// Tentukan periode analisis burn rate: 30 hari terakhir
$days_period = 30;
$tgl_analisis = date('Y-m-d', strtotime("-$days_period days"));

// Query untuk menghitung rata-rata penjualan harian per produk
$burn_rate_query = "
    SELECT 
        p.id AS id_produk,
        p.nama_produk,
        p.stok,
        p.foto,
        k.nama_kategori,
        COALESCE(SUM(pd.qty), 0) AS total_terjual,
        ROUND(COALESCE(SUM(pd.qty), 0) / $days_period, 2) AS rata_harian
    FROM produk p
    LEFT JOIN kategori k ON p.id_kategori = k.id
    LEFT JOIN penjualan_detail pd ON p.id = pd.id_produk
    LEFT JOIN penjualan pen ON pd.id_penjualan = pen.id AND DATE(pen.tanggal) >= '$tgl_analisis'
    GROUP BY p.id
    ORDER BY p.stok ASC, rata_harian DESC
";
$result = mysqli_query($conn, $burn_rate_query);

$critical_count = 0;
$warning_count = 0;
$safe_count = 0;
$predictions = [];

while ($row = mysqli_fetch_assoc($result)) {
    $stok = (int)$row['stok'];
    $rata_harian = (float)$row['rata_harian'];
    
    $estimasi_hari = -1; // Default: Tidak ada penjualan
    $status = 'Aman';
    $tgl_habis = 'N/A';
    
    if ($stok <= 0) {
        $estimasi_hari = 0;
        $status = 'Kritis';
        $tgl_habis = 'Sudah Habis';
        $critical_count++;
    } elseif ($rata_harian > 0) {
        $estimasi_hari = ceil($stok / $rata_harian);
        $tgl_habis = date('d M Y', strtotime("+$estimasi_hari days"));
        
        if ($estimasi_hari <= 3) {
            $status = 'Kritis';
            $critical_count++;
        } elseif ($estimasi_hari <= 10) {
            $status = 'Siaga';
            $warning_count++;
        } else {
            $status = 'Aman';
            $safe_count++;
        }
    } else {
        $status = 'Aman'; // Tidak ada riwayat penjualan = aman/lambat habis
        $safe_count++;
    }
    
    // Rekomendasi restock (supply untuk 30 hari berikutnya)
    $rekomendasi_restock = 0;
    if ($rata_harian > 0) {
        $rekomendasi_restock = ceil(($rata_harian * 30) - $stok);
        if ($rekomendasi_restock < 0) $rekomendasi_restock = 0;
    } else {
        // Jika tidak ada penjualan, restock minimal 10 unit jika stok kosong
        if ($stok == 0) $rekomendasi_restock = 10;
    }

    $predictions[] = [
        'id_produk' => $row['id_produk'],
        'nama_produk' => $row['nama_produk'],
        'foto' => $row['foto'],
        'nama_kategori' => $row['nama_kategori'],
        'stok' => $stok,
        'rata_harian' => $rata_harian,
        'estimasi_hari' => $estimasi_hari,
        'tgl_habis' => $tgl_habis,
        'status' => $status,
        'rekomendasi' => $rekomendasi_restock
    ];
}
?>

<!-- Container Utama -->
<div class="space-y-8">
    
    <!-- Page Header & Breadcrumbs -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <nav class="flex text-xs text-slate-400 space-x-2 mb-2 font-medium">
                <a href="/smart-cashier/dashboard/index.php" class="hover:text-slate-600">Dashboard</a>
                <span>/</span>
                <span class="text-slate-600">Analitik</span>
                <span>/</span>
                <span class="text-indigo-600">Prediksi Stok Habis</span>
            </nav>
            <div class="flex items-center space-x-2.5">
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">AI Smart Stock Prediction</h1>
                <span class="bg-indigo-50 border border-indigo-100 text-indigo-700 font-extrabold text-[9px] px-2 py-0.5 rounded-md uppercase tracking-wider flex items-center space-x-1">
                    <i data-lucide="sparkles" class="w-3 h-3"></i>
                    <span>AI Engine Active</span>
                </span>
            </div>
            <p class="text-xs text-slate-400 font-medium">Algoritma menghitung kecepatan penjualan harian (burn rate) dalam 30 hari terakhir untuk memproyeksikan tanggal habisnya barang.</p>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Kritis -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status Kritis (Habis &lt;= 3 Hari)</span>
                <p class="text-xl font-extrabold text-red-600"><?= $critical_count ?> Produk</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                <i data-lucide="alert-circle" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Siaga -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status Siaga (Habis 4-10 Hari)</span>
                <p class="text-xl font-extrabold text-amber-500"><?= $warning_count ?> Produk</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Aman -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status Aman (&gt; 10 Hari)</span>
                <p class="text-xl font-extrabold text-emerald-600"><?= $safe_count ?> Produk</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Prediction Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table id="table-prediksi" class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-12">No</th>
                        <th class="py-3 px-4">Produk</th>
                        <th class="py-3 px-4 text-center">Stok Saat Ini</th>
                        <th class="py-3 px-4 text-center">Kecepatan Jual / Hari</th>
                        <th class="py-3 px-4 text-center">Sisa Hari Layak</th>
                        <th class="py-3 px-4">Prediksi Tanggal Habis</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Rekomendasi Restock</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    <?php 
                    $no = 1;
                    foreach ($predictions as $row):
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-4 font-medium text-slate-500"><?= $no++ ?></td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                        <?php if ($row['foto']): ?>
                                            <img src="/assets/uploads/<?= htmlspecialchars($row['foto']) ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <i data-lucide="image" class="w-4 h-4 text-slate-400"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-700"><?= htmlspecialchars($row['nama_produk']) ?></p>
                                        <span class="text-[9px] text-slate-400 uppercase font-semibold"><?= htmlspecialchars($row['nama_kategori']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-700"><?= $row['stok'] ?> Pcs</td>
                            <td class="py-3.5 px-4 text-center font-semibold text-slate-500"><?= $row['rata_harian'] ?> / hari</td>
                            <td class="py-3.5 px-4 text-center">
                                <?php if ($row['estimasi_hari'] === 0): ?>
                                    <span class="font-black text-red-600">0 Hari (Habis)</span>
                                <?php elseif ($row['estimasi_hari'] === -1): ?>
                                    <span class="text-slate-400">-</span>
                                <?php else: ?>
                                    <span class="font-bold text-slate-700"><?= $row['estimasi_hari'] ?> Hari</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-500"><?= htmlspecialchars($row['tgl_habis']) ?></td>
                            <td class="py-3.5 px-4 text-center">
                                <?php if ($row['status'] === 'Kritis'): ?>
                                    <span class="text-[9px] font-extrabold text-red-700 bg-red-50 border border-red-100 px-2.5 py-0.5 rounded-full uppercase">Kritis</span>
                                <?php elseif ($row['status'] === 'Siaga'): ?>
                                    <span class="text-[9px] font-extrabold text-amber-700 bg-amber-50 border border-amber-100 px-2.5 py-0.5 rounded-full uppercase">Siaga</span>
                                <?php else: ?>
                                    <span class="text-[9px] font-extrabold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2.5 py-0.5 rounded-full uppercase">Aman</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-right font-extrabold text-indigo-600">
                                <?php if ($row['rekomendasi'] > 0): ?>
                                    +<?= $row['rekomendasi'] ?> Pcs
                                <?php else: ?>
                                    <span class="text-slate-300 font-normal">Cukup</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#table-prediksi').DataTable({
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
            zeroRecords: "Data tidak ditemukan",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada data",
            infoFiltered: "(difilter dari _MAX_ total data)"
        },
        drawCallback: function() {
            lucide.createIcons();
        }
    });
});
</script>

<?php include '../layouts/footer.php'; ?>
