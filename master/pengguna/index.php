<?php
// master/pengguna/index.php
// Halaman CRUD Pengguna - Hanya untuk Admin

include '../../layouts/header.php';
include '../../layouts/sidebar.php';
include '../../layouts/navbar.php';

// Validasi Hak Akses: Hanya Admin
if ($_SESSION['role'] !== 'admin') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator!');
    echo "<script>window.location.href='/smart-cashier/dashboard/index.php';</script>";
    exit();
}

// --- PROSES ACTION FORM ---

// Tambah Pengguna
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama_lengkap = trim(mysqli_real_escape_string($conn, $_POST['nama_lengkap']));
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = $_POST['password'];
    $role = trim(mysqli_real_escape_string($conn, $_POST['role']));
    
    if (!empty($nama_lengkap) && !empty($username) && !empty($password) && !empty($role)) {
        // Cek apakah username sudah ada
        $check = mysqli_query($conn, "SELECT id FROM pengguna WHERE username = '$username'");
        if (mysqli_num_rows($check) > 0) {
            set_flash('warning', 'Username Terpakai', 'Username sudah digunakan oleh akun lain!');
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO pengguna (nama_lengkap, username, password, role) VALUES ('$nama_lengkap', '$username', '$hashed_password', '$role')";
            if (mysqli_query($conn, $query)) {
                set_flash('success', 'Berhasil', 'Pengguna baru berhasil ditambahkan!');
            } else {
                set_flash('error', 'Gagal', 'Terjadi kesalahan: ' . mysqli_error($conn));
            }
        }
    } else {
        set_flash('warning', 'Peringatan', 'Semua kolom wajib diisi!');
    }
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

// Edit Pengguna
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $nama_lengkap = trim(mysqli_real_escape_string($conn, $_POST['nama_lengkap']));
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = $_POST['password'];
    $role = trim(mysqli_real_escape_string($conn, $_POST['role']));
    
    if ($id > 0 && !empty($nama_lengkap) && !empty($username) && !empty($role)) {
        // Cek username duplikat di user lain
        $check = mysqli_query($conn, "SELECT id FROM pengguna WHERE username = '$username' AND id != $id");
        if (mysqli_num_rows($check) > 0) {
            set_flash('warning', 'Username Terpakai', 'Username sudah digunakan oleh akun lain!');
        } else {
            // Update data dasar
            $query = "UPDATE pengguna SET nama_lengkap = '$nama_lengkap', username = '$username', role = '$role' WHERE id = $id";
            if (mysqli_query($conn, $query)) {
                // Update password jika diisi
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    mysqli_query($conn, "UPDATE pengguna SET password = '$hashed_password' WHERE id = $id");
                }
                set_flash('success', 'Berhasil', 'Data pengguna berhasil diperbarui!');
            } else {
                set_flash('error', 'Gagal', 'Terjadi kesalahan: ' . mysqli_error($conn));
            }
        }
    } else {
        set_flash('warning', 'Peringatan', 'Kolom Nama Lengkap, Username, dan Role wajib diisi!');
    }
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

// Hapus Pengguna
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $id = (int)$_GET['id'];
    
    // Cegah menghapus diri sendiri
    if ($id === $_SESSION['user_id']) {
        set_flash('error', 'Gagal Hapus', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif!');
    } else if ($id > 0) {
        $query = "DELETE FROM pengguna WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            set_flash('success', 'Berhasil', 'Pengguna berhasil dihapus!');
        } else {
            set_flash('error', 'Gagal', 'Pengguna tidak dapat dihapus karena terkait data transaksi.');
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
                <span class="text-indigo-600">Pengguna</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Pengguna</h1>
            <p class="text-xs text-slate-400 font-medium">Kelola akun kasir, admin, dan pemilik toko yang memiliki akses sistem.</p>
        </div>
        
        <button onclick="openModal('modal-tambah')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs px-5 py-3 rounded-xl shadow-md shadow-indigo-100 flex items-center space-x-2 transition-all cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Tambah Pengguna</span>
        </button>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table id="table-pengguna" class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-16">No</th>
                        <th class="py-3 px-4">Nama Lengkap</th>
                        <th class="py-3 px-4">Username</th>
                        <th class="py-3 px-4">Hak Akses / Role</th>
                        <th class="py-3 px-4">Tanggal Dibuat</th>
                        <th class="py-3 px-4 w-40 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php
                    $result = mysqli_query($conn, "SELECT * FROM pengguna ORDER BY id DESC");
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)):
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors text-slate-600">
                            <td class="py-3.5 px-4 font-medium text-slate-500"><?= $no++ ?></td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700 flex items-center space-x-2">
                                <div class="w-8 h-8 rounded-full bg-slate-100 text-indigo-600 font-bold flex items-center justify-center text-xs">
                                    <?= strtoupper(substr($row['nama_lengkap'], 0, 2)) ?>
                                </div>
                                <span><?= htmlspecialchars($row['nama_lengkap']) ?></span>
                            </td>
                            <td class="py-3.5 px-4 font-medium"><?= htmlspecialchars($row['username']) ?></td>
                            <td class="py-3.5 px-4">
                                <?php if ($row['role'] === 'admin'): ?>
                                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-full uppercase">Admin</span>
                                <?php elseif ($row['role'] === 'kasir'): ?>
                                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full uppercase">Kasir</span>
                                <?php else: ?>
                                    <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-full uppercase">Owner</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-400"><?= tanggal_indo($row['created_at']) ?></td>
                            <td class="py-3.5 px-4 text-center space-x-2">
                                <button onclick="openModalEdit(<?= htmlspecialchars(json_encode($row)) ?>)"
                                    class="bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-600 px-3 py-1.5 rounded-lg font-medium inline-flex items-center space-x-1 transition-all cursor-pointer">
                                    <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                    <span>Edit</span>
                                </button>
                                <?php if ($row['id'] !== $_SESSION['user_id']): ?>
                                <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_lengkap'])) ?>')"
                                    class="bg-red-50 hover:bg-red-100 text-red-600 px-3 py-1.5 rounded-lg font-medium inline-flex items-center space-x-1 transition-all cursor-pointer">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    <span>Hapus</span>
                                </button>
                                <?php else: ?>
                                <span class="text-[10px] text-slate-400 italic font-medium">Aktif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Pengguna -->
<div id="modal-tambah" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-all duration-300">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 transform scale-95 transition-all duration-300">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <h3 class="text-sm font-bold text-slate-800">Tambah Pengguna Baru</h3>
            <button onclick="closeModal('modal-tambah')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="index.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="tambah">
            <div>
                <label for="nama_lengkap_tambah" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" id="nama_lengkap_tambah" required placeholder="Contoh: Muhammad Rafli"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>
            <div>
                <label for="username_tambah" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Username</label>
                <input type="text" name="username" id="username_tambah" required placeholder="Contoh: rafli_kasir"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>
            <div>
                <label for="password_tambah" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Password</label>
                <input type="password" name="password" id="password_tambah" required placeholder="Masukkan password"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>
            <div>
                <label for="role_tambah" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Hak Akses / Role</label>
                <select name="role" id="role_tambah" required
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                    <option value="kasir">Kasir</option>
                    <option value="admin">Administrator</option>
                    <option value="owner">Owner (Pemilik)</option>
                </select>
            </div>
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-tambah')" 
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer">Batal</button>
                <button type="submit" 
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm cursor-pointer">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pengguna -->
<div id="modal-edit" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-all duration-300">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 transform scale-95 transition-all duration-300">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <h3 class="text-sm font-bold text-slate-800">Edit Pengguna</h3>
            <button onclick="closeModal('modal-edit')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="index.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div>
                <label for="edit-nama" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" id="edit-nama" required placeholder="Nama lengkap"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>
            <div>
                <label for="edit-username" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Username</label>
                <input type="text" name="username" id="edit-username" required placeholder="Username"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>
            <div>
                <label for="edit-password" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Password Baru <span class="text-[10px] text-slate-400 lowercase italic">(kosongkan jika tidak diubah)</span></label>
                <input type="password" name="password" id="edit-password" placeholder="Masukkan password baru jika ingin diubah"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>
            <div>
                <label for="edit-role" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Hak Akses / Role</label>
                <select name="role" id="edit-role" required
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                    <option value="kasir">Kasir</option>
                    <option value="admin">Administrator</option>
                    <option value="owner">Owner (Pemilik)</option>
                </select>
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
    $('#table-pengguna').DataTable({
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
    document.getElementById('edit-nama').value = data.nama_lengkap;
    document.getElementById('edit-username').value = data.username;
    document.getElementById('edit-role').value = data.role;
    document.getElementById('edit-password').value = ''; // Reset password input
    openModal('modal-edit');
}

// SweetAlert2 Delete Confirmation
function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: `Pengguna "${nama}" akan dihapus permanen!`,
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
