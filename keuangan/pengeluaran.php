<?php
// keuangan/pengeluaran.php
// Halaman CRUD Pengeluaran Toko - Hanya untuk Admin dan Owner

include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';

// Validasi Hak Akses: Admin dan Owner
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator atau Owner!');
    echo "<script>window.location.href='/smart-cashier/dashboard/index.php';</script>";
    exit();
}

// --- PROSES ACTION FORM ---

// Tambah Pengeluaran
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama_pengeluaran = trim(mysqli_real_escape_string($conn, $_POST['nama_pengeluaran']));
    $kategori = trim(mysqli_real_escape_string($conn, $_POST['kategori']));
    $nominal = (float)$_POST['nominal'];
    $tanggal = trim(mysqli_real_escape_string($conn, $_POST['tanggal']));
    $keterangan = trim(mysqli_real_escape_string($conn, $_POST['keterangan']));
    
    if (!empty($nama_pengeluaran) && !empty($kategori) && $nominal > 0 && !empty($tanggal)) {
        $query = "INSERT INTO pengeluaran (nama_pengeluaran, kategori, nominal, tanggal, keterangan) 
                  VALUES ('$nama_pengeluaran', '$kategori', $nominal, '$tanggal', '$keterangan')";
        if (mysqli_query($conn, $query)) {
            set_flash('success', 'Berhasil', 'Pengeluaran baru berhasil dicatat!');
        } else {
            set_flash('error', 'Gagal', 'Terjadi kesalahan: ' . mysqli_error($conn));
        }
    } else {
        set_flash('warning', 'Peringatan', 'Semua kolom wajib diisi dengan benar!');
    }
    echo "<script>window.location.href='pengeluaran.php';</script>";
    exit();
}

// Edit Pengeluaran
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $nama_pengeluaran = trim(mysqli_real_escape_string($conn, $_POST['nama_pengeluaran']));
    $kategori = trim(mysqli_real_escape_string($conn, $_POST['kategori']));
    $nominal = (float)$_POST['nominal'];
    $tanggal = trim(mysqli_real_escape_string($conn, $_POST['tanggal']));
    $keterangan = trim(mysqli_real_escape_string($conn, $_POST['keterangan']));
    
    if ($id > 0 && !empty($nama_pengeluaran) && !empty($kategori) && $nominal > 0 && !empty($tanggal)) {
        $query = "UPDATE pengeluaran SET 
                    nama_pengeluaran = '$nama_pengeluaran', 
                    kategori = '$kategori', 
                    nominal = $nominal, 
                    tanggal = '$tanggal', 
                    keterangan = '$keterangan' 
                  WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            set_flash('success', 'Berhasil', 'Pengeluaran berhasil diperbarui!');
        } else {
            set_flash('error', 'Gagal', 'Terjadi kesalahan: ' . mysqli_error($conn));
        }
    } else {
        set_flash('warning', 'Peringatan', 'Semua kolom wajib diisi dengan benar!');
    }
    echo "<script>window.location.href='pengeluaran.php';</script>";
    exit();
}

// Hapus Pengeluaran
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $id = (int)$_GET['id'];
    if ($id > 0) {
        $query = "DELETE FROM pengeluaran WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            set_flash('success', 'Berhasil', 'Catatan pengeluaran berhasil dihapus!');
        } else {
            set_flash('error', 'Gagal', 'Terjadi kesalahan: ' . mysqli_error($conn));
        }
    }
    echo "<script>window.location.href='pengeluaran.php';</script>";
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
                <span class="text-slate-600">Keuangan</span>
                <span>/</span>
                <span class="text-indigo-600">Pengeluaran</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Pengeluaran Toko</h1>
            <p class="text-xs text-slate-400 font-medium">Catat dan kelola pengeluaran biaya operasional, logistik, sewa, gaji karyawan, dsb.</p>
        </div>
        
        <button onclick="openModal('modal-tambah')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs px-5 py-3 rounded-xl shadow-md shadow-indigo-100 flex items-center space-x-2 transition-all cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Catat Pengeluaran</span>
        </button>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table id="table-pengeluaran" class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-12">No</th>
                        <th class="py-3 px-4">Nama Pengeluaran</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4 text-right">Nominal</th>
                        <th class="py-3 px-4">Keterangan</th>
                        <th class="py-3 px-4 w-36 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    <?php
                    $result = mysqli_query($conn, "SELECT * FROM pengeluaran ORDER BY tanggal DESC, id DESC");
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)):
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-4 font-medium text-slate-500"><?= $no++ ?></td>
                            <td class="py-3.5 px-4 font-bold text-slate-700"><?= htmlspecialchars($row['nama_pengeluaran']) ?></td>
                            <td class="py-3.5 px-4">
                                <span class="text-[10px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md uppercase"><?= htmlspecialchars($row['kategori']) ?></span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-400"><?= tanggal_indo($row['tanggal']) ?></td>
                            <td class="py-3.5 px-4 text-right font-bold text-slate-800"><?= rupiah($row['nominal']) ?></td>
                            <td class="py-3.5 px-4 truncate max-w-[150px]"><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                            <td class="py-3.5 px-4 text-center space-x-1">
                                <button onclick="openModalEdit(<?= htmlspecialchars(json_encode($row)) ?>)"
                                    class="bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-600 px-2.5 py-1.5 rounded-lg font-medium inline-flex items-center space-x-1 cursor-pointer">
                                    <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                </button>
                                <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_pengeluaran'])) ?>')"
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

<!-- Modal Tambah Pengeluaran -->
<div id="modal-tambah" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-all duration-300">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 transform scale-95 transition-all duration-300">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <h3 class="text-sm font-bold text-slate-800">Catat Pengeluaran Baru</h3>
            <button onclick="closeModal('modal-tambah')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="pengeluaran.php" method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="action" value="tambah">
            <div>
                <label for="nama_pengeluaran_tambah" class="block font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama Pengeluaran</label>
                <input type="text" name="nama_pengeluaran" id="nama_pengeluaran_tambah" required placeholder="Contoh: Pembayaran Token Listrik"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="kategori_tambah" class="block font-semibold text-slate-500 uppercase tracking-wider mb-2">Kategori</label>
                    <select name="kategori" id="kategori_tambah" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                        <option value="Operasional">Operasional</option>
                        <option value="Perlengkapan">Perlengkapan</option>
                        <option value="Sewa Tempat">Sewa Tempat</option>
                        <option value="Gaji Karyawan">Gaji Karyawan</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label for="tanggal_tambah" class="block font-semibold text-slate-500 uppercase tracking-wider mb-2">Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal_tambah" required value="<?= date('Y-m-d') ?>"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                </div>
            </div>
            <div>
                <label for="nominal_tambah" class="block font-semibold text-slate-500 uppercase tracking-wider mb-2">Nominal Pengeluaran (Rp)</label>
                <input type="number" name="nominal" id="nominal_tambah" required min="1" placeholder="0"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>
            <div>
                <label for="keterangan_tambah" class="block font-semibold text-slate-500 uppercase tracking-wider mb-2">Keterangan Tambahan</label>
                <textarea name="keterangan" id="keterangan_tambah" placeholder="Tulis rincian atau catatan..." rows="3"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all resize-none"></textarea>
            </div>
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-tambah')" 
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer">Batal</button>
                <button type="submit" 
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm cursor-pointer">Simpan Pengeluaran</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pengeluaran -->
<div id="modal-edit" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-all duration-300">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 transform scale-95 transition-all duration-300">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <h3 class="text-sm font-bold text-slate-800">Edit Catatan Pengeluaran</h3>
            <button onclick="closeModal('modal-edit')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="pengeluaran.php" method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div>
                <label for="edit-nama" class="block font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama Pengeluaran</label>
                <input type="text" name="nama_pengeluaran" id="edit-nama" required placeholder="Nama pengeluaran"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit-kategori" class="block font-semibold text-slate-500 uppercase tracking-wider mb-2">Kategori</label>
                    <select name="kategori" id="edit-kategori" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                        <option value="Operasional">Operasional</option>
                        <option value="Perlengkapan">Perlengkapan</option>
                        <option value="Sewa Tempat">Sewa Tempat</option>
                        <option value="Gaji Karyawan">Gaji Karyawan</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label for="edit-tanggal" class="block font-semibold text-slate-500 uppercase tracking-wider mb-2">Tanggal</label>
                    <input type="date" name="tanggal" id="edit-tanggal" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                </div>
            </div>
            <div>
                <label for="edit-nominal" class="block font-semibold text-slate-500 uppercase tracking-wider mb-2">Nominal Pengeluaran (Rp)</label>
                <input type="number" name="nominal" id="edit-nominal" required min="1" placeholder="0"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>
            <div>
                <label for="edit-keterangan" class="block font-semibold text-slate-500 uppercase tracking-wider mb-2">Keterangan Tambahan</label>
                <textarea name="keterangan" id="edit-keterangan" placeholder="Catatan..." rows="3"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all resize-none"></textarea>
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
    $('#table-pengeluaran').DataTable({
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
    document.getElementById('edit-nama').value = data.nama_pengeluaran;
    document.getElementById('edit-kategori').value = data.kategori;
    document.getElementById('edit-tanggal').value = data.tanggal;
    document.getElementById('edit-nominal').value = data.nominal;
    document.getElementById('edit-keterangan').value = data.keterangan || '';
    openModal('modal-edit');
}

// SweetAlert2 Delete Confirmation
function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: `Catatan pengeluaran "${nama}" akan dihapus permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `pengeluaran.php?action=hapus&id=${id}`;
        }
    });
}
</script>

<?php include '../layouts/footer.php'; ?>
