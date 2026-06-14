<?php
// analitik/grafik_penjualan.php
// Halaman Grafik Analitik Lanjutan - Hanya untuk Admin dan Owner

include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';

// Validasi Hak Akses: Admin dan Owner
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator atau Owner!');
    echo "<script>window.location.href='/smart-cashier/dashboard/index.php';</script>";
    exit();
}

// Default filter tanggal: 30 hari terakhir
$tgl_awal = isset($_GET['tgl_awal']) ? trim(mysqli_real_escape_string($conn, $_GET['tgl_awal'])) : date('Y-m-d', strtotime('-30 days'));
$tgl_akhir = isset($_GET['tgl_akhir']) ? trim(mysqli_real_escape_string($conn, $_GET['tgl_akhir'])) : date('Y-m-d');

// --- 1. DATA TREN PENJUALAN & LABA HARIAN ---
$trend_query = "
    SELECT 
        DATE(p.tanggal) as tgl,
        SUM(p.total_harga) as omzet,
        COUNT(p.id) as transaksi,
        SUM(pd.subtotal - (pd.harga_beli * pd.qty)) as laba_kotor
    FROM penjualan p
    LEFT JOIN penjualan_detail pd ON p.id = pd.id_penjualan
    WHERE DATE(p.tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    GROUP BY DATE(p.tanggal)
    ORDER BY DATE(p.tanggal) ASC
";
$trend_res = mysqli_query($conn, $trend_query);

$dates = [];
$omzets = [];
$transaksis = [];
$labas = [];

while ($row = mysqli_fetch_assoc($trend_res)) {
    $dates[] = date('d M', strtotime($row['tgl']));
    $omzets[] = (float)$row['omzet'];
    $transaksis[] = (int)$row['transaksi'];
    $labas[] = (float)$row['laba_kotor'];
}

// --- 2. DATA DISTRIBUSI PENJUALAN PER KATEGORI ---
$cat_query = "
    SELECT 
        k.nama_kategori,
        SUM(pd.qty) as total_qty
    FROM penjualan_detail pd
    JOIN produk pr ON pd.id_produk = pr.id
    JOIN kategori k ON pr.id_kategori = k.id
    JOIN penjualan p ON pd.id_penjualan = p.id
    WHERE DATE(p.tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    GROUP BY k.id
    ORDER BY total_qty DESC
";
$cat_res = mysqli_query($conn, $cat_query);

$cat_labels = [];
$cat_values = [];

while ($row = mysqli_fetch_assoc($cat_res)) {
    $cat_labels[] = $row['nama_kategori'];
    $cat_values[] = (int)$row['total_qty'];
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
                <span class="text-indigo-600">Grafik Lanjutan</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Grafik Analitik Bisnis</h1>
            <p class="text-xs text-slate-400 font-medium">Visualisasikan tren pertumbuhan omzet, frekuensi transaksi, laba kotor, dan segmentasi kategori terpopuler.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <form action="grafik_penjualan.php" method="GET" class="flex flex-col md:flex-row items-end gap-4 text-xs">
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
                <a href="grafik_penjualan.php" class="w-full md:w-auto bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-5 py-2.5 rounded-xl transition-all flex items-center justify-center space-x-1.5">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Charts Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Line Chart: Tren Omzet & Laba (Left - 8/12) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
            <div class="border-b border-slate-50 pb-3 mb-6">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Tren Pertumbuhan Keuangan</h3>
                <p class="text-[10px] text-slate-400 mt-1">Perbandingan omzet penjualan harian terhadap margin keuntungan kotor.</p>
            </div>
            
            <?php if (!empty($dates)): ?>
                <div class="h-80 flex items-center justify-center">
                    <canvas id="chart-tren-keuangan"></canvas>
                </div>
            <?php else: ?>
                <div class="h-80 flex flex-col items-center justify-center text-slate-400">
                    <i data-lucide="line-chart" class="w-10 h-10 mb-2"></i>
                    <p class="text-xs">Tidak ada data harian tersedia.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Doughnut Chart: Segmentasi Kategori (Right - 4/12) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
            <div class="border-b border-slate-50 pb-3 mb-6">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Segmentasi Kategori Terlaris</h3>
                <p class="text-[10px] text-slate-400 mt-1">Distribusi volume item terjual per kategori utama.</p>
            </div>
            
            <?php if (!empty($cat_labels)): ?>
                <div class="h-80 flex items-center justify-center">
                    <canvas id="chart-kategori"></canvas>
                </div>
            <?php else: ?>
                <div class="h-80 flex flex-col items-center justify-center text-slate-400">
                    <i data-lucide="pie-chart" class="w-10 h-10 mb-2"></i>
                    <p class="text-xs">Tidak ada data kategori tersedia.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Line Chart: Frekuensi Transaksi Harian (Full Width) -->
        <div class="lg:col-span-12 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="border-b border-slate-50 pb-3 mb-6">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Frekuensi Transaksi Harian</h3>
                <p class="text-[10px] text-slate-400 mt-1">Grafik jumlah transaksi (nota terbit) yang diselesaikan setiap hari.</p>
            </div>
            
            <?php if (!empty($dates)): ?>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="chart-transaksi-frekuensi"></canvas>
                </div>
            <?php else: ?>
                <div class="h-64 flex flex-col items-center justify-center text-slate-400">
                    <i data-lucide="bar-chart-2" class="w-10 h-10 mb-2"></i>
                    <p class="text-xs">Tidak ada data transaksi harian.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($dates)): ?>
<script>
$(document).ready(function() {
    // 1. Chart Tren Keuangan (Omzet & Laba)
    const ctxKeuangan = document.getElementById('chart-tren-keuangan').getContext('2d');
    new Chart(ctxKeuangan, {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [
                {
                    label: 'Omzet Penjualan',
                    data: <?= json_encode($omzets) ?>,
                    borderColor: '#4f46e5', // Indigo 600
                    backgroundColor: 'rgba(79, 70, 229, 0.05)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3
                },
                {
                    label: 'Laba Kotor',
                    data: <?= json_encode($labas) ?>,
                    borderColor: '#10b981', // Emerald 500
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { font: { family: 'Inter', size: 10 } }
                },
                tooltip: {
                    padding: 10,
                    bodyFont: { family: 'Inter', size: 11 },
                    titleFont: { family: 'Inter', size: 11, weight: 'bold' },
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': Rp ' + context.raw.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 9 } }
                },
                y: {
                    grid: { color: '#f1f5f9' },
                    ticks: { 
                        font: { family: 'Inter', size: 9 },
                        callback: function(value) {
                            return 'Rp ' + (value >= 1000000 ? (value/1000000).toFixed(1) + 'M' : (value/1000).toFixed(0) + 'K');
                        }
                    }
                }
            }
        }
    });

    // 2. Chart Kategori
    <?php if (!empty($cat_labels)): ?>
    const ctxKategori = document.getElementById('chart-kategori').getContext('2d');
    new Chart(ctxKategori, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($cat_labels) ?>,
            datasets: [{
                data: <?= json_encode($cat_values) ?>,
                backgroundColor: [
                    '#4f46e5', // Indigo
                    '#10b981', // Emerald
                    '#f59e0b', // Amber
                    '#ef4444', // Red
                    '#6366f1', // Indigo light
                    '#8b5cf6', // Violet
                    '#ec4899'  // Pink
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { family: 'Inter', size: 9 } }
                },
                tooltip: {
                    padding: 8,
                    bodyFont: { family: 'Inter', size: 10 }
                }
            },
            cutout: '65%'
        }
    });
    <?php endif; ?>

    // 3. Chart Frekuensi Transaksi
    const ctxTransaksi = document.getElementById('chart-transaksi-frekuensi').getContext('2d');
    new Chart(ctxTransaksi, {
        type: 'bar',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{
                label: 'Jumlah Nota',
                data: <?= json_encode($transaksis) ?>,
                backgroundColor: 'rgba(99, 102, 241, 0.85)',
                borderColor: '#6366f1',
                borderWidth: 0,
                borderRadius: 4,
                barThickness: 16
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    padding: 8,
                    bodyFont: { family: 'Inter', size: 10 }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 9 } }
                },
                y: {
                    grid: { color: '#f1f5f9' },
                    ticks: { 
                        font: { family: 'Inter', size: 9 },
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include '../layouts/footer.php'; ?>
