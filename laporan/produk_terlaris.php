<?php
// laporan/produk_terlaris.php
// Halaman Analisis Produk Terlaris - Hanya untuk Admin dan Owner

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

// Query Dapatkan Top 10 Produk Terlaris
$top_query = "
    SELECT 
        pr.nama_produk, 
        k.nama_kategori,
        SUM(pd.qty) AS total_terjual,
        SUM(pd.subtotal) AS total_revenue
    FROM penjualan_detail pd
    JOIN penjualan p ON pd.id_penjualan = p.id
    JOIN produk pr ON pd.id_produk = pr.id
    JOIN kategori k ON pr.id_kategori = k.id
    WHERE DATE(p.tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    GROUP BY pd.id_produk
    ORDER BY total_terjual DESC
    LIMIT 10
";
$result = mysqli_query($conn, $top_query);

$chart_labels = [];
$chart_values = [];
$table_data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $chart_labels[] = $row['nama_produk'];
    $chart_values[] = (int)$row['total_terjual'];
    $table_data[] = $row;
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
                <span class="text-slate-600">Laporan</span>
                <span>/</span>
                <span class="text-indigo-600">Produk Terlaris</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Analisis Produk Terlaris</h1>
            <p class="text-xs text-slate-400 font-medium">Identifikasi 10 produk dengan volume penjualan tertinggi untuk merencanakan pengadaan persediaan barang.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <form action="produk_terlaris.php" method="GET" class="flex flex-col md:flex-row items-end gap-4 text-xs">
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
                <a href="produk_terlaris.php" class="w-full md:w-auto bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-5 py-2.5 rounded-xl transition-all flex items-center justify-center space-x-1.5">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Chart & Table Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Chart Container (Left col - 7/12) -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-6 pb-2 border-b border-slate-50">
                Grafik Volume Penjualan Top 10 Produk
            </h3>
            
            <?php if (!empty($chart_labels)): ?>
                <div class="flex-1 min-h-[300px] flex items-center justify-center">
                    <canvas id="chart-best-sellers"></canvas>
                </div>
            <?php else: ?>
                <div class="h-64 flex flex-col items-center justify-center text-center text-slate-400 py-12">
                    <i data-lucide="bar-chart-3" class="w-10 h-10 text-slate-300 mb-3"></i>
                    <p class="font-semibold text-xs">Data chart kosong untuk periode ini.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Table Container (Right col - 5/12) -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-6 pb-2 border-b border-slate-50">
                Peringkat Produk Terlaris
            </h3>
            
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase">
                            <th class="py-2.5 px-3 w-12 text-center">Rank</th>
                            <th class="py-2.5 px-3">Nama Produk</th>
                            <th class="py-2.5 px-3 text-center">Terjual</th>
                            <th class="py-2.5 px-3 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-slate-600">
                        <?php 
                        $rank = 1;
                        if (!empty($table_data)):
                            foreach ($table_data as $row):
                                // Style badge ranking top 3
                                $rank_class = "text-slate-500 bg-slate-50";
                                if ($rank === 1) $rank_class = "bg-amber-100 text-amber-800 font-black";
                                if ($rank === 2) $rank_class = "bg-slate-200 text-slate-800 font-black";
                                if ($rank === 3) $rank_class = "bg-orange-100 text-orange-800 font-black";
                        ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-3 px-3 text-center">
                                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] mx-auto <?= $rank_class ?>"><?= $rank++ ?></span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <p class="font-semibold text-slate-700"><?= htmlspecialchars($row['nama_produk']) ?></p>
                                        <span class="text-[9px] text-slate-400 uppercase"><?= htmlspecialchars($row['nama_kategori']) ?></span>
                                    </td>
                                    <td class="py-3 px-3 text-center font-bold text-slate-800"><?= number_format($row['total_terjual'], 0, ',', '.') ?> Pcs</td>
                                    <td class="py-3 px-3 text-right font-bold text-indigo-600"><?= rupiah($row['total_revenue']) ?></td>
                                </tr>
                        <?php 
                            endforeach;
                        else:
                            echo "<tr><td colspan='4' class='py-12 text-center text-slate-400 italic'>Belum ada transaksi pada periode ini.</td></tr>";
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($chart_labels)): ?>
<script>
$(document).ready(function() {
    const ctx = document.getElementById('chart-best-sellers').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Volume Terjual (Pcs)',
                data: <?= json_encode($chart_values) ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.85)', // Indigo 600
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 0,
                borderRadius: 8,
                barThickness: 24
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y', // Horizontal Bar Chart
            plugins: {
                legend: { display: false },
                tooltip: {
                    padding: 10,
                    bodyFont: { family: 'Inter', size: 12 },
                    titleFont: { family: 'Inter', size: 12, weight: 'bold' }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 10 } }
                },
                y: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 10, weight: '500' } }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include '../layouts/footer.php'; ?>
