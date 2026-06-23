<?php
// transaksi/riwayat.php
// Halaman Riwayat Transaksi - Hanya untuk Admin dan Kasir

include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';

// Validasi Hak Akses: Admin dan Kasir
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kasir') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator atau Kasir!');
    echo "<script>window.location.href='/dashboard/index.php';</script>";
    exit();
}

// --- PROSES ACTION FORM ---

// Hapus/Void Transaksi (Hanya Admin)
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    if ($_SESSION['role'] !== 'admin') {
        set_flash('error', 'Akses Ditolak', 'Hanya Administrator yang dapat membatalkan transaksi!');
        echo "<script>window.location.href='riwayat.php';</script>";
        exit();
    }
    
    $id = (int)$_GET['id'];
    if ($id > 0) {
        // Mulai transaksi DB secara aman
        mysqli_begin_transaction($conn);
        
        try {
            // 1. Ambil detail transaksi untuk mengembalikan stok produk
            $detail_query = mysqli_query($conn, "SELECT id_produk, qty FROM penjualan_detail WHERE id_penjualan = $id");
            while ($item = mysqli_fetch_assoc($detail_query)) {
                $id_prod = $item['id_produk'];
                $qty_restored = $item['qty'];
                
                // Tambahkan stok kembali ke produk
                if (!mysqli_query($conn, "UPDATE produk SET stok = stok + $qty_restored WHERE id = $id_prod")) {
                    throw new Exception("Gagal mengembalikan stok produk!");
                }
            }
            
            // 2. Hapus data penjualan (detail terhapus otomatis karena CASCADE foreign key)
            if (!mysqli_query($conn, "DELETE FROM penjualan WHERE id = $id")) {
                throw new Exception("Gagal menghapus data transaksi!");
            }
            
            mysqli_commit($conn);
            set_flash('success', 'Transaksi Dibatalkan', 'Transaksi berhasil dihapus dan stok produk telah dikembalikan.');
        } catch (Exception $e) {
            mysqli_rollback($conn);
            set_flash('error', 'Gagal Membatalkan', $e->getMessage());
        }
    }
    echo "<script>window.location.href='riwayat.php';</script>";
    exit();
}

// Default filter tanggal: awal bulan ini s/d hari ini
$tgl_awal = isset($_GET['tgl_awal']) ? trim(mysqli_real_escape_string($conn, $_GET['tgl_awal'])) : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? trim(mysqli_real_escape_string($conn, $_GET['tgl_akhir'])) : date('Y-m-d');
?>

<!-- Container Utama -->
<div class="space-y-8">
    
    <!-- Page Header & Breadcrumbs -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <nav class="flex text-xs text-slate-400 space-x-2 mb-2 font-medium">
                <a href="/dashboard/index.php" class="hover:text-slate-600">Dashboard</a>
                <span>/</span>
                <span class="text-slate-600">Transaksi</span>
                <span>/</span>
                <span class="text-indigo-600">Riwayat</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Riwayat Transaksi</h1>
            <p class="text-xs text-slate-400 font-medium">Lacak seluruh transaksi penjualan, cetak ulang struk belanja, atau batalkan transaksi.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <form action="riwayat.php" method="GET" class="flex flex-col md:flex-row items-end gap-4 text-xs">
            <div class="flex-1 w-full space-y-2">
                <label for="tgl_awal" class="block font-semibold text-slate-500 uppercase tracking-wider">Tanggal Awal</label>
                <input type="date" name="tgl_awal" id="tgl_awal" value="<?= $tgl_awal ?>" required
                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-600 focus:bg-white transition-colors">
            </div>
            
            <div class="flex-1 w-full space-y-2">
                <label for="tgl_akhir" class="block font-semibold text-slate-500 uppercase tracking-wider">Tanggal Akhir</label>
                <input type="date" name="tgl_akhir" id="tgl_akhir" value="<?= $tgl_akhir ?>" required
                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-600 focus:bg-white transition-colors">
            </div>
            
            <div class="w-full md:w-auto flex space-x-2">
                <button type="submit" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-xl transition-all cursor-pointer shadow-sm flex items-center justify-center space-x-1.5">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Terapkan Filter</span>
                </button>
                <a href="riwayat.php" class="w-full md:w-auto bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-5 py-2.5 rounded-xl transition-all flex items-center justify-center space-x-1.5">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table id="table-riwayat" class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-12">No</th>
                        <th class="py-3 px-4">Invoice</th>
                        <th class="py-3 px-4">Tanggal & Waktu</th>
                        <th class="py-3 px-4">Kasir</th>
                        <th class="py-3 px-4 text-right">Total Belanja</th>
                        <th class="py-3 px-4 w-44 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php
                    $query_riwayat = "
                        SELECT p.*, u.nama_lengkap AS kasir_nama 
                        FROM penjualan p 
                        JOIN pengguna u ON p.id_kasir = u.id 
                        WHERE DATE(p.tanggal) BETWEEN '$tgl_awal' AND '$tgl_akhir'
                        ORDER BY p.tanggal DESC
                    ";
                    $result = mysqli_query($conn, $query_riwayat);
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)):
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors text-slate-600">
                            <td class="py-3.5 px-4 font-medium text-slate-500"><?= $no++ ?></td>
                            <td class="py-3.5 px-4 font-bold text-indigo-600">#<?= htmlspecialchars($row['invoice']) ?></td>
                            <td class="py-3.5 px-4"><?= tanggal_indo($row['tanggal'], true) ?></td>
                            <td class="py-3.5 px-4 font-medium text-slate-700"><?= htmlspecialchars($row['kasir_nama']) ?></td>
                            <td class="py-3.5 px-4 text-right font-extrabold text-slate-800"><?= rupiah($row['total_harga']) ?></td>
                            <td class="py-3.5 px-4 text-center space-x-1">
                                <button onclick="showDetails(<?= $row['id'] ?>)"
                                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1.5 rounded-lg font-medium inline-flex items-center space-x-1 cursor-pointer" title="Detail">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                </button>
                                <a href="cetak_struk.php?invoice=<?= urlencode($row['invoice']) ?>" target="_blank"
                                    class="bg-indigo-50 hover:bg-indigo-100 text-indigo-600 px-2.5 py-1.5 rounded-lg font-medium inline-flex items-center space-x-1 cursor-pointer" title="Cetak Struk">
                                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                </a>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                <button onclick="confirmVoid(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['invoice'])) ?>')"
                                    class="bg-red-50 hover:bg-red-100 text-red-600 px-2.5 py-1.5 rounded-lg font-medium inline-flex items-center space-x-1 cursor-pointer" title="Batalkan Transaksi">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail Transaksi -->
<div id="modal-detail" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-all duration-300">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl p-6 transform scale-95 transition-all duration-300">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <h3 class="text-sm font-bold text-slate-800" id="detail-title">Detail Invoice #...</h3>
            <button onclick="closeModal('modal-detail')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <div class="space-y-4">
            <!-- Ringkasan Metadata -->
            <div class="grid grid-cols-2 gap-4 text-xs bg-slate-50 p-4 rounded-xl border border-slate-100">
                <div>
                    <span class="text-slate-400 font-medium">Tanggal Transaksi:</span>
                    <p class="font-bold text-slate-700 mt-0.5" id="detail-date">-</p>
                </div>
                <div>
                    <span class="text-slate-400 font-medium">Kasir:</span>
                    <p class="font-bold text-slate-700 mt-0.5" id="detail-cashier">-</p>
                </div>
            </div>
            
            <!-- Tabel Item Penjualan -->
            <div class="border border-slate-100 rounded-xl overflow-hidden">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-4">Nama Produk</th>
                            <th class="py-2.5 px-4 text-center">Harga Jual</th>
                            <th class="py-2.5 px-4 text-center">Qty</th>
                            <th class="py-2.5 px-4 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="detail-items-body" class="divide-y divide-slate-50">
                        <!-- Items rendered via Javascript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Ringkasan Keuangan Detail -->
            <div class="flex flex-col items-end space-y-1.5 text-xs pt-2">
                <div class="flex justify-between w-60">
                    <span class="text-slate-400 font-medium">Grand Total:</span>
                    <span class="font-bold text-slate-800" id="detail-total">Rp 0</span>
                </div>
                <div class="flex justify-between w-60">
                    <span class="text-slate-400 font-medium">Uang Bayar:</span>
                    <span class="font-semibold text-slate-600" id="detail-pay">Rp 0</span>
                </div>
                <div class="flex justify-between w-60 pt-1.5 border-t border-slate-100">
                    <span class="font-bold text-slate-800">Uang Kembalian:</span>
                    <span class="font-bold text-indigo-600" id="detail-change">Rp 0</span>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 mt-6 border-t border-slate-100">
            <button onclick="closeModal('modal-detail')" 
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer">Tutup</button>
            <a href="" id="detail-btn-print" target="_blank"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm flex items-center space-x-1.5">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak Ulang Struk</span>
            </a>
        </div>
    </div>
</div>

<script>
// DataTables Setup
$(document).ready(function() {
    const table = $('#table-riwayat').DataTable({
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

    // Check if redirect parameters from search/dashboard are active
    const urlParams = new URLSearchParams(window.location.search);
    const invoiceParam = urlParams.get('invoice');
    if (invoiceParam) {
        table.search(invoiceParam).draw();
        // Dapatkan ID invoice dan buka modal detail otomatis jika ada
        const rows = table.rows().data();
        if (rows.length > 0) {
            // Kita triggger detail manual via ajax
            $.get('get_detail_transaksi.php', { invoice: invoiceParam }, function(res) {
                if (res.success) {
                    renderDetailModal(res.data);
                }
            }, 'json');
        }
    }
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

// Ambil detail transaksi via Ajax (GET)
function showDetails(id) {
    $.get('get_detail_transaksi.php', { id: id }, function(res) {
        if (res.success) {
            renderDetailModal(res.data);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: res.message || 'Gagal mengambil detail transaksi.',
                confirmButtonColor: '#059669'
            });
        }
    }, 'json');
}

// Render isi modal detail transaksi
function renderDetailModal(data) {
    document.getElementById('detail-title').innerText = `Detail Invoice #${data.invoice}`;
    document.getElementById('detail-date').innerText = data.tanggal_indo;
    document.getElementById('detail-cashier').innerText = data.kasir_nama;
    document.getElementById('detail-total').innerText = formatRupiah(parseFloat(data.total_harga));
    document.getElementById('detail-pay').innerText = formatRupiah(parseFloat(data.bayar));
    document.getElementById('detail-change').innerText = formatRupiah(parseFloat(data.kembalian));
    document.getElementById('detail-btn-print').href = `cetak_struk.php?invoice=${encodeURIComponent(data.invoice)}`;
    
    const body = document.getElementById('detail-items-body');
    body.innerHTML = ''; // Kosongkan
    
    data.items.forEach(item => {
        const tr = `
            <tr class="text-slate-600 hover:bg-slate-50/50">
                <td class="py-2 px-4 font-semibold text-slate-700">${item.nama_produk}</td>
                <td class="py-2 px-4 text-center">${formatRupiah(parseFloat(item.harga_jual))}</td>
                <td class="py-2 px-4 text-center font-bold">${item.qty} Pcs</td>
                <td class="py-2 px-4 text-right font-extrabold text-slate-800">${formatRupiah(parseFloat(item.subtotal))}</td>
            </tr>
        `;
        body.insertAdjacentHTML('beforeend', tr);
    });
    
    openModal('modal-detail');
}

// Format number helper
function formatRupiah(number) {
    return 'Rp ' + number.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

// SweetAlert2 Void Confirmation
function confirmVoid(id, invoice) {
    Swal.fire({
        title: 'Membatalkan Transaksi?',
        text: `Transaksi #${invoice} akan dibatalkan, data dihapus, dan stok produk dikembalikan!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Batalkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `riwayat.php?action=hapus&id=${id}`;
        }
    });
}
</script>

<?php include '../layouts/footer.php'; ?>
