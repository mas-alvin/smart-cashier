<?php
// laporan/stok.php
// Halaman Laporan Stok Produk - Hanya untuk Admin dan Owner

include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';

// Validasi Hak Akses: Admin dan Owner
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator atau Owner!');
    echo "<script>window.location.href='/dashboard/index.php';</script>";
    exit();
}

// Hitung statistik produk
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM produk");
$total_products = mysqli_fetch_assoc($total_query)['total'];

$habis_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM produk WHERE stok <= 0");
$stock_habis = mysqli_fetch_assoc($habis_query)['total'];

$menipis_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM produk WHERE stok > 0 AND stok <= 5");
$stock_menipis = mysqli_fetch_assoc($menipis_query)['total'];

$aman_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM produk WHERE stok > 5");
$stock_aman = mysqli_fetch_assoc($aman_query)['total'];
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
                <span class="text-indigo-600">Stok Barang</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Laporan Stok Barang</h1>
            <p class="text-xs text-slate-400 font-medium">Pantau ketersediaan produk, rincian barang habis, atau lakukan pemesanan ulang (restock) ke supplier.</p>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Produk -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Katalog Produk</span>
                <p class="text-xl font-extrabold text-slate-800"><?= $total_products ?> Item</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-500 flex items-center justify-center">
                <i data-lucide="package" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Stok Aman -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Stok Aman (> 5 Pcs)</span>
                <p class="text-xl font-extrabold text-emerald-600"><?= $stock_aman ?> Item</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Stok Menipis -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Stok Menipis (1-5 Pcs)</span>
                <p class="text-xl font-extrabold text-amber-500"><?= $stock_menipis ?> Item</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Stok Habis -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Stok Habis (0 Pcs)</span>
                <p class="text-xl font-extrabold text-red-500"><?= $stock_habis ?> Item</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                <i data-lucide="x-circle" class="w-5 h-5"></i>
            </div>
        </div>
    </div>

    <!-- Filter Buttons & Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        
        <!-- Quick State Filter Buttons -->
        <div class="flex flex-wrap gap-2 mb-6">
            <button onclick="filterStockState('all')" id="btn-filter-all"
                class="bg-indigo-600 text-white font-semibold text-xs px-4 py-2 rounded-xl transition-all cursor-pointer shadow-sm">
                Semua Produk
            </button>
            <button onclick="filterStockState('aman')" id="btn-filter-aman"
                class="bg-slate-50 text-slate-600 hover:bg-slate-100 font-semibold text-xs px-4 py-2 rounded-xl transition-all cursor-pointer">
                Stok Aman
            </button>
            <button onclick="filterStockState('menipis')" id="btn-filter-menipis"
                class="bg-slate-50 text-slate-600 hover:bg-slate-100 font-semibold text-xs px-4 py-2 rounded-xl transition-all cursor-pointer">
                Stok Menipis
            </button>
            <button onclick="filterStockState('habis')" id="btn-filter-habis"
                class="bg-slate-50 text-slate-600 hover:bg-slate-100 font-semibold text-xs px-4 py-2 rounded-xl transition-all cursor-pointer">
                Stok Habis
            </button>
        </div>

        <div class="overflow-x-auto">
            <table id="table-stok" class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-12">No</th>
                        <th class="py-3 px-4">Nama Produk</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4 text-right">Harga Jual</th>
                        <th class="py-3 px-4 text-center">Stok</th>
                        <th class="py-3 px-4 text-center">Status Kesehatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    <?php
                    $list_query = mysqli_query($conn, "
                        SELECT p.*, k.nama_kategori 
                        FROM produk p 
                        JOIN kategori k ON p.id_kategori = k.id 
                        ORDER BY p.stok ASC, p.nama_produk ASC
                    ");
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($list_query)):
                        // Tentukan label health status
                        $health = 'aman';
                        if ($row['stok'] <= 0) {
                            $health = 'habis';
                        } else if ($row['stok'] <= 5) {
                            $health = 'menipis';
                        }
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors row-produk-stok" data-health="<?= $health ?>">
                            <td class="py-3.5 px-4 font-medium text-slate-500"><?= $no++ ?></td>
                            <td class="py-3.5 px-4 font-bold text-slate-700"><?= htmlspecialchars($row['nama_produk']) ?></td>
                            <td class="py-3.5 px-4 font-semibold text-indigo-600 bg-indigo-50/20 px-2 py-0.5 rounded inline-block mt-3"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                            <td class="py-3.5 px-4 text-right font-bold text-slate-800"><?= rupiah($row['harga_jual']) ?></td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-800"><?= $row['stok'] ?> Pcs</td>
                            <td class="py-3.5 px-4 text-center">
                                <?php if ($health === 'habis'): ?>
                                    <span class="text-[9px] font-extrabold text-red-700 bg-red-50 border border-red-100 px-2.5 py-1 rounded-full uppercase">Stok Habis</span>
                                <?php elseif ($health === 'menipis'): ?>
                                    <span class="text-[9px] font-extrabold text-amber-700 bg-amber-50 border border-amber-100 px-2.5 py-1 rounded-full uppercase">Menipis</span>
                                <?php else: ?>
                                    <span class="text-[9px] font-extrabold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2.5 py-1 rounded-full uppercase">Stok Aman</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let dataTable;

$(document).ready(function() {
    dataTable = $('#table-stok').DataTable({
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
            search: "Cari Produk:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Produk tidak ditemukan",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada produk",
            infoFiltered: "(difilter dari _MAX_ total data)"
        },
        drawCallback: function() {
            lucide.createIcons();
        }
    });
});

// Custom Javascript filter state
function filterStockState(state) {
    // Reset classes
    ['all', 'aman', 'menipis', 'habis'].forEach(st => {
        const btn = document.getElementById(`btn-filter-${st}`);
        btn.className = "bg-slate-50 text-slate-600 hover:bg-slate-100 font-semibold text-xs px-4 py-2 rounded-xl transition-all cursor-pointer";
    });
    
    // Highlight active button
    const activeBtn = document.getElementById(`btn-filter-${state}`);
    activeBtn.className = "bg-indigo-600 text-white font-semibold text-xs px-4 py-2 rounded-xl transition-all cursor-pointer shadow-sm shadow-indigo-100";
    
    // Apply DataTable filter
    if (state === 'all') {
        dataTable.column(5).search('').draw();
    } else if (state === 'aman') {
        dataTable.column(5).search('Stok Aman').draw();
    } else if (state === 'menipis') {
        dataTable.column(5).search('Menipis').draw();
    } else if (state === 'habis') {
        dataTable.column(5).search('Stok Habis').draw();
    }
}
</script>

<?php include '../layouts/footer.php'; ?>
