<?php
// layouts/navbar.php
// Navbar bagian atas dengan pencarian, informasi toko, tanggal, dan profil singkat
?>
<!-- Top Sticky Navbar -->
<header class="h-16 bg-white border-b border-slate-200 sticky top-0 z-20 flex items-center justify-between px-8">
    <!-- Left Section: Mobile menu button and dynamic page title / welcome -->
    <div class="flex items-center space-x-1 lg:space-x-3">
        <!-- Hamburger Menu Button (Mobile Only) -->
        <button type="button" onclick="toggleSidebarDrawer()" class="lg:hidden p-2 hover:bg-slate-100 rounded-lg text-slate-500 mr-1 cursor-pointer">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <span class="text-slate-500 font-medium text-xs hidden lg:inline-block">Selamat Bekerja, <strong class="text-slate-700"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User') ?></strong></span>
    </div>

    <!-- Right Section: Info & Profile -->
    <div class="flex items-center space-x-6">
        <!-- Hari & Tanggal Real-Time (Server Side Initialized) -->
        <div class="hidden md:flex items-center space-x-2 text-slate-500 text-sm">
            <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
            <span class="font-medium"><?= tanggal_indo(date('Y-m-d')) ?></span>
        </div>
        
        <!-- Pemisah -->
        <span class="w-[1px] h-6 bg-slate-200 hidden md:inline-block"></span>

        <!-- Quick Shortcut ke Kasir (Jika Admin/Kasir) -->
        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'kasir'): ?>
        <a href="/transaksi/kasir.php" class="flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors shadow-sm shadow-indigo-100">
            <i data-lucide="calculator" class="w-3.5 h-3.5"></i>
            <span>Buka Kasir</span>
        </a>
        <?php endif; ?>

        <!-- Notification Bell (Dekoratif/Expo Ready) -->
        <div class="relative cursor-pointer hover:bg-slate-50 p-2 rounded-lg transition-colors">
            <i data-lucide="bell" class="w-5 h-5 text-slate-500"></i>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
        </div>
    </div>
</header>

<!-- Main Container Content (Halaman Utama) -->
<main class="flex-1 p-8 overflow-y-auto">
