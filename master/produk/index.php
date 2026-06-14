<?php
// master/produk/index.php
// Halaman CRUD Produk - Hanya untuk Admin

include '../../layouts/header.php';
include '../../layouts/sidebar.php';
include '../../layouts/navbar.php';

// Validasi Hak Akses: Hanya Admin
if ($_SESSION['role'] !== 'admin') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator!');
    echo "<script>window.location.href='/smart-cashier/dashboard/index.php';</script>";
    exit();
}

// Upload directory setup
$upload_dir = dirname(dirname(__DIR__)) . '/assets/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Dapatkan daftar kategori untuk option select
$kategori_list = [];
$kategori_query = mysqli_query($conn, "SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
while ($row = mysqli_fetch_assoc($kategori_query)) {
    $kategori_list[] = $row;
}

// --- PROSES ACTION FORM ---

// Tambah Produk
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama_produk = trim(mysqli_real_escape_string($conn, $_POST['nama_produk']));
    $id_kategori = (int)$_POST['id_kategori'];
    $harga_beli = (float)$_POST['harga_beli'];
    $harga_jual = (float)$_POST['harga_jual'];
    $stok = (int)$_POST['stok'];
    $status = trim(mysqli_real_escape_string($conn, $_POST['status']));
    
    // Handle File Upload
    $foto_name = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_name = $_FILES['foto']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validasi ekstensi
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_ext, $allowed_ext)) {
            // Beri nama unik
            $foto_name = time() . '_' . uniqid() . '.' . $file_ext;
            move_uploaded_file($file_tmp, $upload_dir . $foto_name);
        }
    }
    
    if (!empty($nama_produk) && $id_kategori > 0 && $harga_beli >= 0 && $harga_jual >= 0) {
        $query = "INSERT INTO produk (foto, nama_produk, id_kategori, harga_beli, harga_jual, stok, status) 
                  VALUES (" . ($foto_name ? "'$foto_name'" : "NULL") . ", '$nama_produk', $id_kategori, $harga_beli, $harga_jual, $stok, '$status')";
        if (mysqli_query($conn, $query)) {
            set_flash('success', 'Berhasil', 'Produk baru berhasil ditambahkan!');
        } else {
            set_flash('error', 'Gagal', 'Terjadi kesalahan database: ' . mysqli_error($conn));
        }
    } else {
        set_flash('warning', 'Peringatan', 'Mohon isi semua data wajib dengan benar!');
    }
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

// Edit Produk
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $nama_produk = trim(mysqli_real_escape_string($conn, $_POST['nama_produk']));
    $id_kategori = (int)$_POST['id_kategori'];
    $harga_beli = (float)$_POST['harga_beli'];
    $harga_jual = (float)$_POST['harga_jual'];
    $stok = (int)$_POST['stok'];
    $status = trim(mysqli_real_escape_string($conn, $_POST['status']));
    
    if ($id > 0 && !empty($nama_produk) && $id_kategori > 0) {
        // Ambil data produk lama untuk mendeteksi foto lama
        $old_query = mysqli_query($conn, "SELECT foto FROM produk WHERE id = $id");
        $old_data = mysqli_fetch_assoc($old_query);
        $foto_name = $old_data['foto'];
        
        // Handle File Upload Baru
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['foto']['tmp_name'];
            $file_name = $_FILES['foto']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($file_ext, $allowed_ext)) {
                // Hapus file lama jika ada
                if (!empty($foto_name) && file_exists($upload_dir . $foto_name)) {
                    unlink($upload_dir . $foto_name);
                }
                
                $foto_name = time() . '_' . uniqid() . '.' . $file_ext;
                move_uploaded_file($file_tmp, $upload_dir . $foto_name);
            }
        }
        
        $query = "UPDATE produk SET 
                    nama_produk = '$nama_produk', 
                    id_kategori = $id_kategori, 
                    harga_beli = $harga_beli, 
                    harga_jual = $harga_jual, 
                    stok = $stok, 
                    status = '$status', 
                    foto = " . ($foto_name ? "'$foto_name'" : "NULL") . " 
                  WHERE id = $id";
                  
        if (mysqli_query($conn, $query)) {
            set_flash('success', 'Berhasil', 'Produk berhasil diperbarui!');
        } else {
            set_flash('error', 'Gagal', 'Terjadi kesalahan database: ' . mysqli_error($conn));
        }
    }
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

// Hapus Produk
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $id = (int)$_GET['id'];
    if ($id > 0) {
        // Hapus file foto terkait dari disk
        $old_query = mysqli_query($conn, "SELECT foto FROM produk WHERE id = $id");
        $old_data = mysqli_fetch_assoc($old_query);
        if ($old_data && !empty($old_data['foto'])) {
            $old_foto = $upload_dir . $old_data['foto'];
            if (file_exists($old_foto)) {
                unlink($old_foto);
            }
        }
        
        $query = "DELETE FROM produk WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            set_flash('success', 'Berhasil', 'Produk berhasil dihapus!');
        } else {
            set_flash('error', 'Gagal', 'Produk tidak dapat dihapus karena terkait data transaksi.');
        }
    }
    echo "<script>window.location.href='index.php';</script>";
    exit();
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
                <span class="text-slate-600">Master Data</span>
                <span>/</span>
                <span class="text-indigo-600">Produk</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Katalog Produk</h1>
            <p class="text-xs text-slate-400 font-medium">Kelola daftar item barang dagangan, harga, dan jumlah stok penjualan Anda.</p>
        </div>
        
        <button onclick="openModal('modal-tambah')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs px-5 py-3 rounded-xl shadow-md shadow-indigo-100 flex items-center space-x-2 transition-all cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Tambah Produk</span>
        </button>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        
        <!-- Category Filter -->
        <div class="flex items-center space-x-3 mb-6 bg-slate-50 p-4 rounded-xl border border-slate-100 max-w-sm">
            <label for="filter-kategori" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Filter Kategori:</label>
            <select id="filter-kategori" class="flex-1 bg-white border border-slate-200 rounded-lg text-xs py-1.5 px-3 focus:outline-none focus:border-indigo-600">
                <option value="">Semua Kategori</option>
                <?php foreach ($kategori_list as $kat): ?>
                    <option value="<?= htmlspecialchars($kat['nama_kategori']) ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table id="table-produk" class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-12">No</th>
                        <th class="py-3 px-4 w-16">Foto</th>
                        <th class="py-3 px-4">Nama Produk</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4 text-right">Harga Beli</th>
                        <th class="py-3 px-4 text-right">Harga Jual</th>
                        <th class="py-3 px-4 text-center">Stok</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 w-36 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php
                    $result = mysqli_query($conn, "
                        SELECT p.*, k.nama_kategori 
                        FROM produk p 
                        JOIN kategori k ON p.id_kategori = k.id 
                        ORDER BY p.id DESC
                    ");
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)):
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors text-slate-600">
                            <td class="py-3 px-4 font-medium text-slate-400"><?= $no++ ?></td>
                            <td class="py-3 px-4">
                                <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-300">
                                    <?php if (!empty($row['foto']) && file_exists($upload_dir . $row['foto'])): ?>
                                        <img src="/assets/uploads/<?= $row['foto'] ?>" alt="Foto" class="w-full h-full rounded-lg object-cover">
                                    <?php else: ?>
                                        <i data-lucide="image" class="w-5 h-5"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-800"><?= htmlspecialchars($row['nama_produk']) ?></td>
                            <td class="py-3 px-4 font-medium text-indigo-600 bg-indigo-50/20 rounded-md px-2 py-1 inline-block mt-3"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                            <td class="py-3 px-4 text-right font-medium"><?= rupiah($row['harga_beli']) ?></td>
                            <td class="py-3 px-4 text-right font-bold text-slate-700"><?= rupiah($row['harga_jual']) ?></td>
                            <td class="py-3 px-4 text-center font-bold">
                                <?php if ($row['stok'] <= 0): ?>
                                    <span class="text-red-600 bg-red-50 px-2 py-0.5 rounded">Habis</span>
                                <?php elseif ($row['stok'] <= 5): ?>
                                    <span class="text-amber-600 bg-amber-50 px-2 py-0.5 rounded"><?= $row['stok'] ?> (Menipis)</span>
                                <?php else: ?>
                                    <span class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded"><?= $row['stok'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <?php if ($row['status'] === 'aktif'): ?>
                                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full uppercase">Aktif</span>
                                <?php else: ?>
                                    <span class="text-[10px] font-bold text-red-700 bg-red-50 border border-red-100 px-2 py-0.5 rounded-full uppercase">Non-Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-center space-x-1">
                                <button onclick="openModalEdit(<?= htmlspecialchars(json_encode($row)) ?>)"
                                    class="bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-600 px-2.5 py-1.5 rounded-lg font-medium inline-flex items-center space-x-1 cursor-pointer">
                                    <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                </button>
                                <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_produk'])) ?>')"
                                    class="bg-red-50 hover:bg-red-100 text-red-600 px-2.5 py-1.5 rounded-lg font-medium inline-flex items-center space-x-1 cursor-pointer">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Produk -->
<div id="modal-tambah" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-all duration-300">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 transform scale-95 transition-all duration-300 h-[85vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <h3 class="text-sm font-bold text-slate-800">Tambah Produk Baru</h3>
            <button onclick="closeModal('modal-tambah')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="index.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="tambah">
            
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Foto Produk</label>
                <input type="file" name="foto" accept="image/*"
                    class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            </div>

            <div>
                <label for="nama_produk_tambah" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama Produk</label>
                <input type="text" name="nama_produk" id="nama_produk_tambah" required placeholder="Contoh: Coca-Cola 330ml"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>

            <div>
                <label for="id_kategori_tambah" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Kategori Produk</label>
                <select name="id_kategori" id="id_kategori_tambah" required
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                    <option value="" disabled selected>Pilih Kategori</option>
                    <?php foreach ($kategori_list as $kat): ?>
                        <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="harga_beli_tambah" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Harga Beli (Rp)</label>
                    <input type="number" name="harga_beli" id="harga_beli_tambah" min="0" required placeholder="0"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                </div>
                <div>
                    <label for="harga_jual_tambah" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Harga Jual (Rp)</label>
                    <input type="number" name="harga_jual" id="harga_jual_tambah" min="0" required placeholder="0"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="stok_tambah" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Jumlah Stok</label>
                    <input type="number" name="stok" id="stok_tambah" min="0" required value="0"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                </div>
                <div>
                    <label for="status_tambah" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Status Produk</label>
                    <select name="status" id="status_tambah" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                        <option value="aktif">Aktif (Dijual)</option>
                        <option value="non-aktif">Non-Aktif (Arsip)</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-tambah')" 
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer">Batal</button>
                <button type="submit" 
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm cursor-pointer">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Produk -->
<div id="modal-edit" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-all duration-300">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 transform scale-95 transition-all duration-300 h-[85vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <h3 class="text-sm font-bold text-slate-800">Edit Produk</h3>
            <button onclick="closeModal('modal-edit')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="index.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Foto Produk <span class="text-[10px] text-slate-400 lowercase italic">(kosongkan jika tidak diganti)</span></label>
                <input type="file" name="foto" accept="image/*"
                    class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            </div>

            <div>
                <label for="edit-nama" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama Produk</label>
                <input type="text" name="nama_produk" id="edit-nama" required placeholder="Nama produk"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>

            <div>
                <label for="edit-kategori" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Kategori Produk</label>
                <select name="id_kategori" id="edit-kategori" required
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                    <?php foreach ($kategori_list as $kat): ?>
                        <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit-harga-beli" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Harga Beli (Rp)</label>
                    <input type="number" name="harga_beli" id="edit-harga-beli" min="0" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                </div>
                <div>
                    <label for="edit-harga-jual" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Harga Jual (Rp)</label>
                    <input type="number" name="harga_jual" id="edit-harga-jual" min="0" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit-stok" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Jumlah Stok</label>
                    <input type="number" name="stok" id="edit-stok" min="0" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                </div>
                <div>
                    <label for="edit-status" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Status Produk</label>
                    <select name="status" id="edit-status" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                        <option value="aktif">Aktif (Dijual)</option>
                        <option value="non-aktif">Non-Aktif (Arsip)</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-edit')" 
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer">Batal</button>
                <button type="submit" 
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm cursor-pointer">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
// DataTables Setup
$(document).ready(function() {
    const table = $('#table-produk').DataTable({
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
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Data tidak ditemukan",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada data tersedia",
            infoFiltered: "(difilter dari _MAX_ total data)"
        },
        drawCallback: function() {
            lucide.createIcons();
        }
    });

    // Custom Category Filter
    $('#filter-kategori').on('change', function() {
        const val = $(this).val();
        table.column(3).search(val ? '^' + val + '$' : '', true, false).draw();
    });
});

// Modal Logic
function openModal(id) {
    const modal = document.getElementById(id);
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.transform').classList.remove('scale-95');
    }, 50);
}

function closeModal(id) {
    const modal = document.getElementById(id);
    modal.classList.add('opacity-0');
    modal.querySelector('.transform').classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

function openModalEdit(data) {
    document.getElementById('edit-id').value = data.id;
    document.getElementById('edit-nama').value = data.nama_produk;
    document.getElementById('edit-kategori').value = data.id_kategori;
    document.getElementById('edit-harga-beli').value = data.harga_beli;
    document.getElementById('edit-harga-jual').value = data.harga_jual;
    document.getElementById('edit-stok').value = data.stok;
    document.getElementById('edit-status').value = data.status;
    openModal('modal-edit');
}

// SweetAlert2 Delete Confirmation
function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: `Produk "${nama}" akan dihapus permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `index.php?action=hapus&id=${id}`;
        }
    });
}
</script>

<?php include '../../layouts/footer.php'; ?>
