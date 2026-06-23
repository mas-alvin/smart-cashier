<?php
// login.php
// Halaman login untuk masuk ke sistem SMARTPOS UMKM

// Masukkan file database dan helper secara manual sebelum HTML output
$base_path = __DIR__;
require_once $base_path . '/config/database.php';
require_once $base_path . '/helpers/format.php';

// Proses Logout jika ada parameter ?action=logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Simpan pesan flash sebelum menghapus session
    session_destroy();
    session_start();
    set_flash('success', 'Berhasil Keluar', 'Anda telah keluar dari aplikasi.');
    header("Location: /login.php");
    exit();
}

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard/index.php");
    exit();
}

$error_message = '';

// Proses form login saat di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password wajib diisi!';
    } else {
        // Query user berdasarkan username
        $query = "SELECT * FROM pengguna WHERE username = '$username' LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Verifikasi password hash
            if (password_verify($password, $user['password'])) {
                // Set Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Set Flash Message
                set_flash('success', 'Selamat Datang!', 'Selamat bekerja, ' . $user['nama_lengkap'] . '.');
                
                header("Location: /dashboard/index.php");
                exit();
            } else {
                $error_message = 'Password yang Anda masukkan salah!';
            }
        } else {
            $error_message = 'Username tidak terdaftar di sistem!';
        }
    }
}

// Panggil header layout (hanya untuk merender HTML head, dsb.)
include 'layouts/header.php';
?>

<div class="min-h-screen w-full flex items-center justify-center p-6 bg-slate-50 relative overflow-hidden">
    <!-- Dekorasi Hiasan Background (Premium Blur Circles) -->
    <div class="absolute w-[400px] h-[400px] rounded-full bg-indigo-100 blur-3xl -top-40 -left-40 opacity-70"></div>
    <div class="absolute w-[400px] h-[400px] rounded-full bg-indigo-50 blur-3xl -bottom-40 -right-40 opacity-70"></div>
    
    <div class="bg-white w-full max-w-md rounded-2xl border border-slate-100 shadow-xl p-8 relative z-10 flex flex-col justify-between">
        <div>
            <!-- Header Brand -->
            <div class="flex flex-col items-center mb-8">
                <?php if (!empty($logo_toko) && file_exists(__DIR__ . '/assets/uploads/' . $logo_toko)): ?>
                    <img src="/assets/uploads/<?= htmlspecialchars($logo_toko) ?>" alt="Logo" class="w-14 h-14 rounded-2xl object-cover shadow-lg shadow-indigo-100 mb-4">
                <?php else: ?>
                    <img src="https://ppdb.smkalmujtamak.sch.id/logo-amt.webp" alt="Logo" class="w-14 h-14 rounded-2xl object-cover shadow-lg shadow-indigo-100 mb-4">
                <?php endif; ?>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight"><?= htmlspecialchars($nama_toko) ?></h1>
                <p class="text-sm text-slate-400 mt-1">Silakan masuk ke akun Anda</p>
            </div>
            
            <!-- Alert Error -->
            <?php if (!empty($error_message)): ?>
                <div class="mb-6 bg-red-50 border border-red-100 text-red-700 text-xs px-4 py-3 rounded-lg flex items-start space-x-2.5">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Form Login -->
            <form action="login.php" method="POST" class="space-y-5">
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </div>
                        <input type="text" name="username" id="username" placeholder="Masukkan username" required
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </div>
                        <input type="password" name="password" id="password" placeholder="Masukkan password" required
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                    </div>
                </div>
                
                <button type="submit" 
                    class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-all shadow-md shadow-indigo-100 flex items-center justify-center space-x-2 cursor-pointer mt-2">
                    <span>Masuk ke Dashboard</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
        
        <!-- Demo Credentials Helper (Untuk Keperluan Expo RPL) -->
        <div class="mt-8 pt-6 border-t border-slate-100">
            <span class="text-[10px] font-semibold text-slate-400 tracking-wider uppercase block mb-3 text-center">Akun Demo Expo RPL</span>
            <div class="grid grid-cols-3 gap-2 text-[10px]">
                <div class="bg-indigo-50/50 p-2 rounded-lg text-center border border-indigo-50">
                    <span class="font-bold text-indigo-700 block">Admin</span>
                    <span class="text-slate-500">U: admin</span>
                    <span class="text-slate-500 block">P: admin123</span>
                </div>
                <div class="bg-emerald-50/50 p-2 rounded-lg text-center border border-emerald-50">
                    <span class="font-bold text-emerald-700 block">Kasir</span>
                    <span class="text-slate-500">U: kasir</span>
                    <span class="text-slate-500 block">P: kasir123</span>
                </div>
                <div class="bg-amber-50/50 p-2 rounded-lg text-center border border-amber-50">
                    <span class="font-bold text-amber-700 block">Owner</span>
                    <span class="text-slate-500">U: owner</span>
                    <span class="text-slate-500 block">P: owner123</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>
