<?php
// pengaturan/backup.php
// Halaman Backup Database - Hanya untuk Admin

session_start();
require_once '../config/database.php';
require_once '../helpers/format.php';

// Validasi Hak Akses: Hanya Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator!');
    echo "<script>window.location.href='/smart-cashier/dashboard/index.php';</script>";
    exit();
}

// --- PROSES EXPORT DOWNLOAD DATABASE DUMP ---
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    // Nonaktifkan output buffering & set max execution time
    set_time_limit(0);
    
    // Ambil daftar seluruh tabel dalam database saat ini
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    
    $sql_dump = "-- SMARTPOS UMKM DATABASE BACKUP\n";
    $sql_dump .= "-- Generated on " . date('Y-m-d H:i:s') . "\n";
    $sql_dump .= "-- Database: " . $database . "\n\n";
    $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        // 1. Ambil Skema CREATE TABLE
        $create_res = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
        $create_row = mysqli_fetch_row($create_res);
        $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_dump .= $create_row[1] . ";\n\n";
        
        // 2. Ambil Isi Record Data
        $data_res = mysqli_query($conn, "SELECT * FROM `$table`");
        while ($data_row = mysqli_fetch_assoc($data_res)) {
            $fields = array_keys($data_row);
            $values = array_map(function($val) use ($conn) {
                if ($val === null) return 'NULL';
                return "'" . mysqli_real_escape_string($conn, $val) . "'";
            }, array_values($data_row));
            
            $sql_dump .= "INSERT INTO `$table` (`" . implode("`, `", $fields) . "`) VALUES (" . implode(", ", $values) . ");\n";
        }
        $sql_dump .= "\n\n";
    }
    
    $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Trigger download file attachment
    $filename = "backup_db_smartpos_" . date('Ymd_His') . ".sql";
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql_dump));
    echo $sql_dump;
    exit();
}

include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';
?>

<!-- Container Utama -->
<div class="space-y-8 max-w-xl mx-auto">
    
    <!-- Page Header & Breadcrumbs -->
    <div class="space-y-2">
        <nav class="flex text-xs text-slate-400 space-x-2 mb-2 font-medium">
            <a href="/smart-cashier/dashboard/index.php" class="hover:text-slate-600">Dashboard</a>
            <span>/</span>
            <span class="text-slate-600">Pengaturan</span>
            <span>/</span>
            <span class="text-indigo-600">Backup</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Backup Database</h1>
        <p class="text-xs text-slate-400 font-medium">Ekspor database sistem POS Anda menjadi file SQL untuk mencadangkan data penting toko secara mandiri.</p>
    </div>

    <!-- Backup Action Box -->
    <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm flex flex-col items-center text-center space-y-6">
        <div class="w-16 h-16 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shadow-inner">
            <i data-lucide="database" class="w-8 h-8"></i>
        </div>
        
        <div class="space-y-2">
            <h3 class="font-bold text-slate-800 text-sm">Backup Satu Klik</h3>
            <p class="text-[11px] text-slate-400 max-w-sm font-semibold">Proses ini akan mengumpulkan seluruh data kategori, supplier, produk, pengeluaran, pengguna, dan riwayat transaksi penjualan.</p>
        </div>

        <div class="w-full bg-slate-50 border border-slate-100 p-4 rounded-xl text-left space-y-2 text-[10px] text-slate-500 font-medium">
            <div class="flex justify-between">
                <span>Ekstensi File:</span>
                <span class="font-bold text-slate-700">.sql (SQL Dump)</span>
            </div>
            <div class="flex justify-between">
                <span>Metode Ekspor:</span>
                <span class="font-bold text-slate-700">Native PHP (Direct Stream)</span>
            </div>
            <div class="flex justify-between">
                <span>Rekomendasi Rutinitas:</span>
                <span class="font-bold text-indigo-600">Setiap Minggu / Bulan</span>
            </div>
        </div>

        <a href="backup.php?action=download" 
            class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl transition-all shadow-md shadow-indigo-100 flex items-center justify-center space-x-2 cursor-pointer">
            <i data-lucide="download" class="w-4 h-4"></i>
            <span>Unduh Backup SQL</span>
        </a>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
