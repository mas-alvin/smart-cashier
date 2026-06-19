<?php
// helpers/format.php
// Helper functions untuk SMARTPOS UMKM

// Jalankan session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Format angka ke format mata uang Rupiah
 * Contoh: 15000 -> Rp 15.000
 */
function rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

/**
 * Format tanggal ke bahasa Indonesia
 * Contoh: 2026-06-14 09:15:00 -> 14 Juni 2026 (09:15)
 */
function tanggal_indo($tanggal, $tampil_waktu = false) {
    if (empty($tanggal)) return '-';
    
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $split = explode(' ', $tanggal);
    $tgl_split = explode('-', $split[0]);
    
    if (count($tgl_split) !== 3) return $tanggal;
    
    $tanggal_indo = $tgl_split[2] . ' ' . $bulan[(int)$tgl_split[1]] . ' ' . $tgl_split[0];
    
    if ($tampil_waktu && isset($split[1])) {
        $waktu = substr($split[1], 0, 5); // Ambil HH:MM
        $tanggal_indo .= ' (' . $waktu . ')';
    }
    
    return $tanggal_indo;
}

/**
 * Helper untuk menentukan class menu aktif di sidebar
 */
function is_active($page_path) {
    $current_uri = $_SERVER['REQUEST_URI'];
    if (strpos($current_uri, $page_path) !== false) {
        return 'bg-indigo-50 text-indigo-600 font-semibold';
    }
    return 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';
}

/**
 * Set flash message menggunakan session
 */
function set_flash($tipe, $judul, $pesan) {
    $_SESSION['flash'] = [
        'tipe' => $tipe, // success, error, warning, info
        'judul' => $judul,
        'pesan' => $pesan
    ];
}

/**
 * Tampilkan flash message dengan SweetAlert2 jika ada
 */
function show_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        
        $tipe = $flash['tipe'];
        $judul = addslashes($flash['judul']);
        $pesan = addslashes($flash['pesan']);
        
        return "
        <script>
            Swal.fire({
                icon: '{$tipe}',
                title: '{$judul}',
                text: '{$pesan}',
                confirmButtonColor: '#059669'
            });
        </script>
        ";
    }
    return '';
}
?>
