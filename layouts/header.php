<?php
// layouts/header.php
// Header layout utama dengan asset CDN dan validasi session

// Masukkan file database dan helper
$base_path = dirname(__DIR__);
require_once $base_path . '/config/database.php';
require_once $base_path . '/helpers/format.php';

// Proteksi halaman: jika belum login, arahkan ke login.php
// Kecuali sedang berada di halaman login.php itu sendiri
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php' && !isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

// Dapatkan nama toko, logo, & slogan secara dinamis dari database untuk digunakan secara global
$toko_query = mysqli_query($conn, "SELECT nama_toko, logo, slogan FROM profil_toko LIMIT 1");
$toko_info = mysqli_fetch_assoc($toko_query);
$nama_toko = $toko_info['nama_toko'] ?? 'SMARTPOS UMKM';
$logo_toko = $toko_info['logo'] ?? '';
$slogan_toko = $toko_info['slogan'] ?? 'Sistem Kasir Pintar Digital';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nama_toko) ?> - Kelola Toko Lebih Mudah</title>
    <link rel="shortcut icon" href="https://ppdb.smkalmujtamak.sch.id/logo-amt.webp" type="image/webp">
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS v4 Browser CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    
    <!-- jQuery (Dibutuhkan untuk DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables CSS & JS + Tailwind CSS integration -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.tailwindcss.css">
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.tailwindcss.js"></script>
    
    <!-- DataTables Buttons (Export) CSS & JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.tailwindcss.css">
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.tailwindcss.js"></script>
    
    <!-- PDF & Excel Export dependencies -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- HTML5 QR Code Scanner Library for Barcode scanning -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    
    <!-- Tema Kustomisasi Tailwind v4 & Global CSS -->
    <style type="text/tailwindcss">
        @theme {
            --font-sans: 'Inter', sans-serif;
            --color-primary: #059669; /* Emerald 600 */
            --color-success: #059669; /* Emerald 600 */
            --color-danger: #dc2626;  /* Red 600 */
            --color-warning: #d97706; /* Amber 600 */
            
            --color-indigo-50: #ecfdf5;
            --color-indigo-100: #d1fae5;
            --color-indigo-200: #a7f3d0;
            --color-indigo-300: #6ee7b7;
            --color-indigo-400: #34d399;
            --color-indigo-500: #10b981;
            --color-indigo-600: #059669;
            --color-indigo-700: #047857;
            --color-indigo-800: #065f46;
            --color-indigo-900: #064e3b;
            --color-indigo-950: #022c22;
        }
        
        /* Modifikasi custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Modifikasi default DataTables search & layout */
        .dt-search input {
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            outline: none;
            transition: all 0.2s;
        }
        .dt-search input:focus {
            border-color: #059669;
            box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.1);
        }
        
        /* Style override untuk DataTables Button export */
        .dt-buttons .btn {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen flex">
    
    <!-- Container Utama untuk Dashboard Layout -->
    <?php if ($current_page !== 'login.php'): ?>
    <div class="flex w-full min-h-screen">
    <?php endif; ?>
