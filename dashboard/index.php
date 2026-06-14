<?php
// dashboard/index.php
// Halaman dashboard utama SMARTPOS UMKM

// Include layouts
include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';

// --- QUERY DATA STATISTIK ---

// 1. Omzet Hari Ini
$omzet_res = mysqli_query($conn, "SELECT SUM(total_harga) AS total FROM penjualan WHERE DATE(tanggal) = CURDATE()");
$omzet_data = mysqli_fetch_assoc($omzet_res);
$omzet_hari_ini = $omzet_data['total'] ?? 0;

// 2. Laba Hari Ini (Harga Jual - Harga Beli) * Qty
$laba_res = mysqli_query($conn, "
    SELECT SUM((pd.harga_jual - pd.harga_beli) * pd.qty) AS total_laba 
    FROM penjualan_detail pd 
    JOIN penjualan p ON pd.id_penjualan = p.id 
    WHERE DATE(p.tanggal) = CURDATE()
");
$laba_data = mysqli_fetch_assoc($laba_res);
$laba_hari_ini = $laba_data['total_laba'] ?? 0;

// 3. Total Produk Aktif
$produk_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM produk WHERE status = 'aktif'");
$produk_data = mysqli_fetch_assoc($produk_res);
$total_produk = $produk_data['total'] ?? 0;

// 4. Total Transaksi Hari Ini
$trx_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM penjualan WHERE DATE(tanggal) = CURDATE()");
$trx_data = mysqli_fetch_assoc($trx_res);
$total_transaksi = $trx_data['total'] ?? 0;


// --- CHART: PENJUALAN 7 HARI TERAKHIR ---
$chart_labels = [];
$chart_data = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = tanggal_indo($date);
    
    // Cari omzet untuk tanggal tersebut
    $sales_query = mysqli_query($conn, "SELECT SUM(total_harga) AS total FROM penjualan WHERE DATE(tanggal) = '$date'");
    $sales_data = mysqli_fetch_assoc($sales_query);
    $chart_data[] = (float)($sales_data['total'] ?? 0);
}
?>

<!-- Halaman Dashboard Container -->
<div class="space-y-8">
    
    <!-- Page Header & Breadcrumb -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <nav class="flex text-xs text-slate-400 space-x-2 mb-2 font-medium">
                <span class="text-indigo-600">Dashboard</span>
                <span>/</span>
                <span>Ringkasan</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Dashboard Ringkasan</h1>
            <p class="text-xs text-slate-400">Pantau performa penjualan dan keuangan toko Anda.</p>
        </div>
        
        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'kasir'): ?>
        <a href="/smart-cashier/transaksi/kasir.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs px-5 py-3 rounded-xl shadow-md shadow-indigo-100 flex items-center space-x-2 transition-all">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>Transaksi Baru</span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Grid Card Statistik Utama -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Omzet Hari Ini -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 flex items-center justify-between shadow-sm hover:shadow-md transition-shadow">
            <div class="space-y-2">
                <span class="text-xs font-semibold text-slate-400 tracking-wider uppercase">Omzet Hari Ini</span>
                <h3 class="text-xl font-bold text-slate-800 tracking-tight"><?= rupiah($omzet_hari_ini) ?></h3>
                <span class="text-[10px] text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded flex items-center w-max space-x-0.5">
                    <i data-lucide="trending-up" class="w-3 h-3"></i>
                    <span>Hari ini</span>
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                <i data-lucide="banknote" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Laba Hari Ini -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 flex items-center justify-between shadow-sm hover:shadow-md transition-shadow">
            <div class="space-y-2">
                <span class="text-xs font-semibold text-slate-400 tracking-wider uppercase">Laba Kotor Hari Ini</span>
                <h3 class="text-xl font-bold text-slate-800 tracking-tight"><?= rupiah($laba_hari_ini) ?></h3>
                <span class="text-[10px] text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded flex items-center w-max space-x-0.5">
                    <i data-lucide="arrow-up" class="w-3 h-3"></i>
                    <span>Margin keuntungan</span>
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                <i data-lucide="coins" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Total Produk -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 flex items-center justify-between shadow-sm hover:shadow-md transition-shadow">
            <div class="space-y-2">
                <span class="text-xs font-semibold text-slate-400 tracking-wider uppercase">Produk Aktif</span>
                <h3 class="text-xl font-bold text-slate-800 tracking-tight"><?= number_format($total_produk) ?></h3>
                <span class="text-[10px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded block w-max">Tersedia dijual</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500">
                <i data-lucide="box" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Total Transaksi -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 flex items-center justify-between shadow-sm hover:shadow-md transition-shadow">
            <div class="space-y-2">
                <span class="text-xs font-semibold text-slate-400 tracking-wider uppercase">Transaksi Hari Ini</span>
                <h3 class="text-xl font-bold text-slate-800 tracking-tight"><?= number_format($total_transaksi) ?></h3>
                <span class="text-[10px] text-indigo-600 font-semibold bg-indigo-50 px-2 py-0.5 rounded flex items-center w-max space-x-0.5">
                    <i data-lucide="shopping-bag" class="w-3 h-3"></i>
                    <span>Checkout kasir</span>
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-red-500">
                <i data-lucide="clipboard-list" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Grafik & Produk Terlaris Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Grafik Penjualan (Left 2 cols) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 lg:col-span-2 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Tren Grafik Penjualan</h3>
                    <p class="text-[10px] text-slate-400">Total omzet penjualan 7 hari terakhir</p>
                </div>
                <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                    <i data-lucide="line-chart" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="w-full h-[280px]">
                <canvas id="salesTrendsChart"></canvas>
            </div>
        </div>

        <!-- Produk Terlaris (Right 1 col) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Produk Terlaris</h3>
                        <p class="text-[10px] text-slate-400">Daftar item paling banyak terjual</p>
                    </div>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-500">
                        <i data-lucide="star" class="w-4 h-4"></i>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <?php
                    $top_res = mysqli_query($conn, "
                        SELECT pr.nama_produk, k.nama_kategori, SUM(pd.qty) AS total_qty, pr.foto
                        FROM penjualan_detail pd
                        JOIN produk pr ON pd.id_produk = pr.id
                        JOIN kategori k ON pr.id_kategori = k.id
                        GROUP BY pr.id
                        ORDER BY total_qty DESC
                        LIMIT 4
                    ");
                    if (mysqli_num_rows($top_res) > 0):
                        while ($top = mysqli_fetch_assoc($top_res)):
                    ?>
                        <div class="flex items-center justify-between border-b border-slate-50 pb-3 last:border-0 last:pb-0">
                            <div class="flex items-center space-x-3 overflow-hidden">
                                <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 flex-shrink-0">
                                    <?php if (!empty($top['foto']) && file_exists('../assets/uploads/' . $top['foto'])): ?>
                                        <img src="/assets/uploads/<?= $top['foto'] ?>" alt="Produk" class="w-full h-full rounded-lg object-cover">
                                    <?php else: ?>
                                        <i data-lucide="image" class="w-4 h-4"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="overflow-hidden">
                                    <h4 class="text-xs font-bold text-slate-800 truncate"><?= htmlspecialchars($top['nama_produk']) ?></h4>
                                    <span class="text-[10px] text-slate-400 font-medium"><?= htmlspecialchars($top['nama_kategori']) ?></span>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 pl-2">
                                <span class="text-xs font-bold text-slate-700 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100"><?= $top['total_qty'] ?> Pcs</span>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div class="text-center py-8 text-slate-400 text-xs">
                            <i data-lucide="package-x" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                            <span>Belum ada transaksi</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'owner'): ?>
            <a href="/smart-cashier/laporan/produk_terlaris.php" class="text-center block text-xs font-semibold text-indigo-600 hover:text-indigo-700 mt-4 pt-3 border-t border-slate-100">
                Lihat Selengkapnya &rarr;
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Aktivitas / Transaksi Terbaru -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-sm font-bold text-slate-800 font-semibold">Transaksi Terbaru</h3>
                <p class="text-[10px] text-slate-400">Daftar transaksi kasir yang baru saja dilakukan</p>
            </div>
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'kasir'): ?>
            <a href="/smart-cashier/transaksi/riwayat.php" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Lihat Semua</a>
            <?php endif; ?>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="pb-3">No. Invoice</th>
                        <th class="pb-3">Tanggal & Waktu</th>
                        <th class="pb-3">Kasir</th>
                        <th class="pb-3 text-right">Total Belanja</th>
                        <th class="pb-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php
                    $recent_res = mysqli_query($conn, "
                        SELECT p.*, u.nama_lengkap AS kasir_nama 
                        FROM penjualan p 
                        JOIN pengguna u ON p.id_kasir = u.id 
                        ORDER BY p.tanggal DESC 
                        LIMIT 5
                    ");
                    if (mysqli_num_rows($recent_res) > 0):
                        while ($trx = mysqli_fetch_assoc($recent_res)):
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 font-semibold text-indigo-600">#<?= htmlspecialchars($trx['invoice']) ?></td>
                            <td class="py-3 text-slate-500"><?= tanggal_indo($trx['tanggal'], true) ?></td>
                            <td class="py-3">
                                <span class="font-medium text-slate-700"><?= htmlspecialchars($trx['kasir_nama']) ?></span>
                            </td>
                            <td class="py-3 text-right font-bold text-slate-800"><?= rupiah($trx['total_harga']) ?></td>
                            <td class="py-3 text-center">
                                <a href="/transaksi/riwayat.php?invoice=<?= urlencode($trx['invoice']) ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-lg font-medium inline-block">Detail</a>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">Belum ada transaksi saat ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('salesTrendsChart').getContext('2d');
    
    // Gradient fill effect
    const gradient = ctx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.2)');
    gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Omzet (Rp)',
                data: <?= json_encode($chart_data) ?>,
                borderColor: '#4f46e5',
                borderWidth: 3,
                backgroundColor: gradient,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#4f46e5',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return ' Omzet: Rp ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    grid: {
                        color: '#f1f5f9'
                    },
                    ticks: {
                        font: {
                            family: 'Inter',
                            size: 10
                        },
                        color: '#94a3b8',
                        callback: function(value) {
                            if (value >= 1000000) {
                                return 'Rp ' + (value / 1000000) + 'jt';
                            } else if (value >= 1000) {
                                return 'Rp ' + (value / 1000) + 'rb';
                            }
                            return 'Rp ' + value;
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Inter',
                            size: 10
                        },
                        color: '#94a3b8'
                    }
                }
            }
        }
    });
});
</script>

<?php
// Include footer
include '../layouts/footer.php';
?>
