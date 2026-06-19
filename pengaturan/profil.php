<?php
// pengaturan/profil.php
// Halaman Pengaturan Profil Toko - Hanya untuk Admin

include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';

// Validasi Hak Akses: Hanya Admin
if ($_SESSION['role'] !== 'admin') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator!');
    echo "<script>window.location.href='/smart-cashier/dashboard/index.php';</script>";
    exit();
}

// Ambil profil toko saat ini
$profile_query = mysqli_query($conn, "SELECT * FROM profil_toko LIMIT 1");
$profile = mysqli_fetch_assoc($profile_query);

// Jika kosong (antisipasi), buat default row
if (!$profile) {
    mysqli_query($conn, "INSERT INTO profil_toko (nama_toko, alamat, nomor_hp, email) VALUES ('SMARTPOS UMKM', '-', '-', '-')");
    $profile_query = mysqli_query($conn, "SELECT * FROM profil_toko LIMIT 1");
    $profile = mysqli_fetch_assoc($profile_query);
}

// --- PROSES UPDATE PROFIL TOKO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $nama_toko = trim(mysqli_real_escape_string($conn, $_POST['nama_toko']));
    $nomor_hp = trim(mysqli_real_escape_string($conn, $_POST['nomor_hp']));
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $alamat = trim(mysqli_real_escape_string($conn, $_POST['alamat']));
    
    if (!empty($nama_toko) && !empty($nomor_hp)) {
        // Handle File Upload Logo
        $logo_name = $profile['logo'];
        $upload_dir = dirname(__DIR__) . '/assets/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['logo']['tmp_name'];
            $file_name = $_FILES['logo']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($file_ext, $allowed_ext)) {
                // Hapus logo lama jika ada
                if (!empty($logo_name) && file_exists($upload_dir . $logo_name)) {
                    unlink($upload_dir . $logo_name);
                }
                
                $logo_name = 'logo_' . time() . '_' . uniqid() . '.' . $file_ext;
                move_uploaded_file($file_tmp, $upload_dir . $logo_name);
            }
        }

        $update_query = "UPDATE profil_toko SET 
                            nama_toko = '$nama_toko', 
                            nomor_hp = '$nomor_hp', 
                            email = '$email', 
                            alamat = '$alamat',
                            logo = " . ($logo_name ? "'$logo_name'" : "NULL") . " 
                         WHERE id = {$profile['id']}";
        if (mysqli_query($conn, $update_query)) {
            set_flash('success', 'Berhasil Diperbarui', 'Profil identitas toko berhasil disimpan!');
            echo "<script>window.location.href='profil.php';</script>";
            exit();
        } else {
            set_flash('error', 'Gagal', 'Gagal memperbarui profil: ' . mysqli_error($conn));
        }
    } else {
        set_flash('warning', 'Peringatan', 'Nama Toko dan No. Telepon wajib diisi.');
    }
}
?>

<!-- Container Utama -->
<div class="space-y-8 max-w-4xl mx-auto">
    
    <!-- Page Header & Breadcrumbs -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <nav class="flex text-xs text-slate-400 space-x-2 mb-2 font-medium">
                <a href="/smart-cashier/dashboard/index.php" class="hover:text-slate-600">Dashboard</a>
                <span>/</span>
                <span class="text-slate-600">Pengaturan</span>
                <span>/</span>
                <span class="text-indigo-600">Profil Toko</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Profil & Identitas Toko</h1>
            <p class="text-xs text-slate-400 font-medium">Ubah nama usaha, alamat, email, atau kontak telepon yang akan dicetak pada lembar struk transaksi belanja.</p>
        </div>
    </div>

    <!-- Layout Form Pengaturan -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
        <!-- Panel Info Singkat (Left 4 cols) -->
        <div class="md:col-span-4 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm text-center flex flex-col items-center justify-center space-y-4">
                <!-- Visual Mockup Logo Toko -->
                <?php if (!empty($profile['logo']) && file_exists(dirname(__DIR__) . '/assets/uploads/' . $profile['logo'])): ?>
                    <img src="../assets/uploads/<?= $profile['logo'] ?>" alt="Logo Toko" class="w-20 h-20 rounded-2xl object-cover border border-slate-200 shadow-md">
                <?php else: ?>
                    <div class="w-20 h-20 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xl shadow-inner">
                        <?= strtoupper(substr($profile['nama_toko'], 0, 2)) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <h3 class="font-bold text-slate-800 text-sm mt-2"><?= htmlspecialchars($profile['nama_toko']) ?></h3>
                    <p class="text-[10px] text-slate-400 uppercase tracking-wide font-semibold mt-1">Sistem Point of Sale</p>
                </div>
                <div class="w-full border-t border-slate-50 pt-4 text-[10px] text-left text-slate-400 space-y-2 font-medium">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span><?= htmlspecialchars($profile['nomor_hp']) ?></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span><?= htmlspecialchars($profile['email']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Form Input (Right 8 cols) -->
        <div class="md:col-span-8">
            <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
                <form action="profil.php" method="POST" enctype="multipart/form-data" class="space-y-6 text-xs">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nama_toko" class="block font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Toko / Usaha</label>
                            <input type="text" name="nama_toko" id="nama_toko" required value="<?= htmlspecialchars($profile['nama_toko']) ?>" placeholder="Masukkan nama toko"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                        </div>
                        <div>
                            <label for="nomor_hp" class="block font-bold text-slate-500 uppercase tracking-wider mb-2">No. Telepon / Kontak WA</label>
                            <input type="text" name="nomor_hp" id="nomor_hp" required value="<?= htmlspecialchars($profile['nomor_hp']) ?>" placeholder="Masukkan nomor telepon"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="email" class="block font-bold text-slate-500 uppercase tracking-wider mb-2">Email Toko</label>
                            <input type="email" name="email" id="email" required value="<?= htmlspecialchars($profile['email']) ?>" placeholder="Masukkan email toko"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                        </div>
                        <div>
                            <label for="logo" class="block font-bold text-slate-500 uppercase tracking-wider mb-2">Logo Toko <span class="text-[10px] text-slate-400 lowercase font-normal italic">(jpg, jpeg, png, webp)</span></label>
                            <input type="file" name="logo" id="logo" accept="image/*"
                                class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        </div>
                    </div>

                    <div>
                        <label for="alamat" class="block font-bold text-slate-500 uppercase tracking-wider mb-2">Alamat Lengkap Usaha</label>
                        <textarea name="alamat" id="alamat" required placeholder="Masukkan alamat lengkap toko..." rows="4"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all resize-none"><?= htmlspecialchars($profile['alamat']) ?></textarea>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t border-slate-100">
                        <button type="submit" 
                            class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl transition-all shadow-md shadow-indigo-100 flex items-center space-x-2 cursor-pointer">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                            <span>Simpan Perubahan Profil</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
