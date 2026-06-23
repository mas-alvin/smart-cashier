<?php
// pengaturan/ganti_password.php
// Halaman Ubah Sandi Mandiri - Diakses oleh Semua Pengguna

include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';

$id_user = $_SESSION['user_id'];

// --- PROSES UBAH PASSWORD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ganti_password') {
    $password_lama = trim($_POST['password_lama']);
    $password_baru = trim($_POST['password_baru']);
    $password_konfirmasi = trim($_POST['password_konfirmasi']);
    
    if (!empty($password_lama) && !empty($password_baru) && !empty($password_konfirmasi)) {
        // Ambil password lama di database
        $query = mysqli_query($conn, "SELECT password FROM pengguna WHERE id = $id_user LIMIT 1");
        $user = mysqli_fetch_assoc($query);
        
        if ($user && password_verify($password_lama, $user['password'])) {
            if ($password_baru === $password_konfirmasi) {
                // Hash password baru
                $password_hash = password_hash($password_baru, PASSWORD_BCRYPT);
                $update = mysqli_query($conn, "UPDATE pengguna SET password = '$password_hash' WHERE id = $id_user");
                if ($update) {
                    set_flash('success', 'Berhasil', 'Sandi Anda berhasil diperbarui!');
                } else {
                    set_flash('error', 'Gagal', 'Gagal memperbarui sandi: ' . mysqli_error($conn));
                }
            } else {
                set_flash('warning', 'Peringatan', 'Konfirmasi sandi baru tidak cocok!');
            }
        } else {
            set_flash('error', 'Salah', 'Kata sandi saat ini tidak cocok!');
        }
    } else {
        set_flash('warning', 'Peringatan', 'Seluruh kolom kata sandi wajib diisi!');
    }
    echo "<script>window.location.href='ganti_password.php';</script>";
    exit();
}
?>

<!-- Container Utama -->
<div class="space-y-8 max-w-lg mx-auto">
    
    <!-- Page Header & Breadcrumbs -->
    <div class="space-y-2">
        <nav class="flex text-xs text-slate-400 space-x-2 mb-2 font-medium">
            <a href="/dashboard/index.php" class="hover:text-slate-600">Dashboard</a>
            <span>/</span>
            <span class="text-slate-600">Pengaturan</span>
            <span>/</span>
            <span class="text-indigo-600">Ubah Password</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Ubah Kata Sandi</h1>
        <p class="text-xs text-slate-400 font-medium">Jaga keamanan akun Anda dengan mengganti kata sandi secara berkala.</p>
    </div>

    <!-- Password Form Box -->
    <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
        <form action="ganti_password.php" method="POST" class="space-y-5 text-xs">
            <input type="hidden" name="action" value="ganti_password">
            
            <div>
                <label for="password_lama" class="block font-bold text-slate-500 uppercase tracking-wider mb-2">Kata Sandi Saat Ini</label>
                <input type="password" name="password_lama" id="password_lama" required placeholder="••••••••"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>

            <div class="border-t border-slate-100 pt-4">
                <label for="password_baru" class="block font-bold text-slate-500 uppercase tracking-wider mb-2">Kata Sandi Baru</label>
                <input type="password" name="password_baru" id="password_baru" required placeholder="••••••••"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>

            <div>
                <label for="password_konfirmasi" class="block font-bold text-slate-500 uppercase tracking-wider mb-2">Konfirmasi Kata Sandi Baru</label>
                <input type="password" name="password_konfirmasi" id="password_konfirmasi" required placeholder="••••••••"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>

            <div class="flex items-center justify-end pt-4 border-t border-slate-100">
                <button type="submit" 
                    class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl transition-all shadow-md shadow-indigo-100 flex items-center justify-center space-x-2 cursor-pointer">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                    <span>Perbarui Kata Sandi</span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
