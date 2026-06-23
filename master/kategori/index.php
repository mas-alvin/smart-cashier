<?php
// master/kategori/index.php
// Halaman CRUD Kategori Produk - Hanya untuk Admin

include '../../layouts/header.php';
include '../../layouts/sidebar.php';
include '../../layouts/navbar.php';

// Validasi Hak Akses: Hanya Admin
if ($_SESSION['role'] !== 'admin') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator!');
    echo "<script>window.location.href='/dashboard/index.php';</script>";
    exit();
}

// --- PROSES ACTION FORM ---

// Tambah Kategori
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama_kategori = trim(mysqli_real_escape_string($conn, $_POST['nama_kategori']));
    if (!empty($nama_kategori)) {
        $query = "INSERT INTO kategori (nama_kategori) VALUES ('$nama_kategori')";
        if (mysqli_query($conn, $query)) {
            set_flash('success', 'Berhasil', 'Kategori baru berhasil ditambahkan!');
        } else {
            set_flash('error', 'Gagal', 'Terjadi kesalahan: ' . mysqli_error($conn));
        }
    } else {
        set_flash('warning', 'Peringatan', 'Nama kategori tidak boleh kosong!');
    }
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

// Edit Kategori
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $nama_kategori = trim(mysqli_real_escape_string($conn, $_POST['nama_kategori']));
    if ($id > 0 && !empty($nama_kategori)) {
        $query = "UPDATE kategori SET nama_kategori = '$nama_kategori' WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            set_flash('success', 'Berhasil', 'Kategori berhasil diperbarui!');
        } else {
            set_flash('error', 'Gagal', 'Terjadi kesalahan: ' . mysqli_error($conn));
        }
    } else {
        set_flash('warning', 'Peringatan', 'Nama kategori tidak boleh kosong!');
    }
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

// Hapus Kategori
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $id = (int)$_GET['id'];
    if ($id > 0) {
        $query = "DELETE FROM kategori WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            set_flash('success', 'Berhasil', 'Kategori berhasil dihapus!');
        } else {
            set_flash('error', 'Gagal', 'Kategori tidak bisa dihapus karena masih digunakan oleh produk lain.');
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
                <a href="/dashboard/index.php" class="hover:text-slate-600">Dashboard</a>
                <span>/</span>
                <span class="text-slate-600">Master Data</span>
                <span>/</span>
                <span class="text-indigo-600">Kategori</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Kategori Produk</h1>
            <p class="text-xs text-slate-400 font-medium">Kelola kategori produk untuk memudahkan pengelompokkan produk Anda.</p>
        </div>
        
        <button onclick="openModal('modal-tambah')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs px-5 py-3 rounded-xl shadow-md shadow-indigo-100 flex items-center space-x-2 transition-all cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Tambah Kategori</span>
        </button>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table id="table-kategori" class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-16">No</th>
                        <th class="py-3 px-4">Nama Kategori</th>
                        <th class="py-3 px-4 w-40 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php
                    $result = mysqli_query($conn, "SELECT * FROM kategori ORDER BY id DESC");
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)):
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-4 font-medium text-slate-500"><?= $no++ ?></td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                            <td class="py-3.5 px-4 text-center space-x-2">
                                <button onclick="openModalEdit(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_kategori'])) ?>')"
                                    class="bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-600 px-3 py-1.5 rounded-lg font-medium inline-flex items-center space-x-1 transition-all cursor-pointer">
                                    <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                    <span>Edit</span>
                                </button>
                                <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_kategori'])) ?>')"
                                    class="bg-red-50 hover:bg-red-100 text-red-600 px-3 py-1.5 rounded-lg font-medium inline-flex items-center space-x-1 transition-all cursor-pointer">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    <span>Hapus</span>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div id="modal-tambah" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-all duration-300">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 transform scale-95 transition-all duration-300">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <h3 class="text-sm font-bold text-slate-800">Tambah Kategori Baru</h3>
            <button onclick="closeModal('modal-tambah')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="index.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="tambah">
            <div>
                <label for="nama_kategori_tambah" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama Kategori</label>
                <input type="text" name="nama_kategori" id="nama_kategori_tambah" required placeholder="Masukkan nama kategori"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-tambah')" 
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer">Batal</button>
                <button type="submit" 
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm cursor-pointer">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div id="modal-edit" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-all duration-300">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 transform scale-95 transition-all duration-300">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <h3 class="text-sm font-bold text-slate-800">Edit Kategori</h3>
            <button onclick="closeModal('modal-edit')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="index.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div>
                <label for="nama_kategori_edit" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama Kategori</label>
                <input type="text" name="nama_kategori" id="edit-nama" required placeholder="Masukkan nama kategori"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
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
    $('#table-kategori').DataTable({
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
            lucide.createIcons(); // Redraw Lucide icons inside pagination buttons
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

function openModalEdit(id, nama) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nama').value = nama;
    openModal('modal-edit');
}

// SweetAlert2 Delete Confirmation
function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: `Kategori "${nama}" akan dihapus permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626', // Red 600
        cancelButtonColor: '#64748b',  // Slate 500
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
