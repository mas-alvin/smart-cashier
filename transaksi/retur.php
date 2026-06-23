<?php
// transaksi/retur.php
// Halaman Retur Penjualan - Hanya untuk Admin dan Kasir

include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';

// Validasi Hak Akses: Admin dan Kasir
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kasir') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator atau Kasir!');
    echo "<script>window.location.href='/dashboard/index.php';</script>";
    exit();
}

$invoice_input = isset($_GET['invoice']) ? trim(mysqli_real_escape_string($conn, $_GET['invoice'])) : '';
$transaction_data = null;
$purchased_items = [];

// Cari data transaksi jika invoice diinput
if (!empty($invoice_input)) {
    $sales_query = mysqli_query($conn, "
        SELECT p.*, u.nama_lengkap AS kasir_nama 
        FROM penjualan p 
        JOIN pengguna u ON p.id_kasir = u.id 
        WHERE p.invoice = '$invoice_input' 
        LIMIT 1
    ");
    $transaction_data = mysqli_fetch_assoc($sales_query);
    
    if ($transaction_data) {
        $id_penjualan = $transaction_data['id'];
        // Dapatkan item produk dari transaksi
        $items_query = mysqli_query($conn, "
            SELECT pd.*, pr.nama_produk, pr.id AS id_prod
            FROM penjualan_detail pd
            JOIN produk pr ON pd.id_produk = pr.id
            WHERE pd.id_penjualan = $id_penjualan
        ");
        while ($row = mysqli_fetch_assoc($items_query)) {
            // Hitung jumlah yang sudah pernah diretur sebelumnya
            $retur_query = mysqli_query($conn, "
                SELECT SUM(qty) AS total_retur 
                FROM retur 
                WHERE invoice_penjualan = '$invoice_input' AND id_produk = {$row['id_prod']}
            ");
            $ret_data = mysqli_fetch_assoc($retur_query);
            $already_returned = (int)($ret_data['total_retur'] ?? 0);
            
            // Simpan data sisa qty yang bisa diretur
            $row['sisa_qty'] = $row['qty'] - $already_returned;
            $purchased_items[] = $row;
        }
    } else {
        set_flash('error', 'Tidak Ditemukan', "Transaksi dengan nomor invoice '$invoice_input' tidak terdaftar!");
    }
}

// Proses submit retur produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_retur') {
    $invoice_penjualan = trim(mysqli_real_escape_string($conn, $_POST['invoice_penjualan']));
    $id_produk = (int)$_POST['id_produk'];
    $qty_retur = (int)$_POST['qty_retur'];
    $alasan = trim(mysqli_real_escape_string($conn, $_POST['alasan']));
    $tanggal_retur = date('Y-m-d');
    
    if (!empty($invoice_penjualan) && $id_produk > 0 && $qty_retur > 0 && !empty($alasan)) {
        // Ambil data untuk validasi qty
        $sales_det_query = mysqli_query($conn, "
            SELECT pd.qty, pr.nama_produk, pr.id AS id_prod 
            FROM penjualan_detail pd
            JOIN penjualan p ON pd.id_penjualan = p.id
            JOIN produk pr ON pd.id_produk = pr.id
            WHERE p.invoice = '$invoice_penjualan' AND pd.id_produk = $id_produk
            LIMIT 1
        ");
        $det = mysqli_fetch_assoc($sales_det_query);
        
        if ($det) {
            // Hitung total retur sebelumnya
            $ret_query = mysqli_query($conn, "
                SELECT SUM(qty) AS total_retur 
                FROM retur 
                WHERE invoice_penjualan = '$invoice_penjualan' AND id_produk = $id_produk
            ");
            $ret_data = mysqli_fetch_assoc($ret_query);
            $already_returned = (int)($ret_data['total_retur'] ?? 0);
            $sisa_qty = $det['qty'] - $already_returned;
            
            if ($qty_retur > $sisa_qty) {
                set_flash('error', 'Jumlah Retur Tidak Valid', "Jumlah retur melebihi sisa pembelian! Sisa yang bisa diretur: $sisa_qty Pcs.");
            } else {
                // Jalankan proses retur aman menggunakan transaction
                mysqli_begin_transaction($conn);
                try {
                    // 1. Catat ke tabel retur
                    $query_insert = "INSERT INTO retur (invoice_penjualan, id_produk, qty, alasan, tanggal) 
                                     VALUES ('$invoice_penjualan', $id_produk, $qty_retur, '$alasan', '$tanggal_retur')";
                    if (!mysqli_query($conn, $query_insert)) {
                        throw new Exception("Gagal menyimpan retur: " . mysqli_error($conn));
                    }
                    
                    // 2. Kembalikan stok produk ke gudang
                    $query_stok = "UPDATE produk SET stok = stok + $qty_retur WHERE id = $id_produk";
                    if (!mysqli_query($conn, $query_stok)) {
                        throw new Exception("Gagal mengembalikan stok produk: " . mysqli_error($conn));
                    }
                    
                    mysqli_commit($conn);
                    set_flash('success', 'Retur Berhasil', "Produk '" . $det['nama_produk'] . "' sebanyak $qty_retur pcs telah dikembalikan.");
                    echo "<script>window.location.href='retur.php?invoice=" . urlencode($invoice_penjualan) . "';</script>";
                    exit();
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    set_flash('error', 'Gagal', $e->getMessage());
                }
            }
        } else {
            set_flash('error', 'Gagal', 'Produk tidak ditemukan dalam transaksi tersebut.');
        }
    } else {
        set_flash('warning', 'Peringatan', 'Silakan isi seluruh kolom dengan lengkap.');
    }
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
                <span class="text-slate-600">Transaksi</span>
                <span>/</span>
                <span class="text-indigo-600">Retur Penjualan</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Retur Penjualan</h1>
            <p class="text-xs text-slate-400 font-medium">Proses pengembalian produk rusak atau salah beli dari pelanggan untuk mengembalikan stok barang.</p>
        </div>
    </div>

    <!-- Grid Layout Cari & Input Retur -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Panel Pencarian & Detail Transaksi (Left col - 7/12) -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Pencarian Invoice Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-4 flex items-center space-x-1.5">
                    <i data-lucide="search" class="w-4 h-4 text-indigo-600"></i>
                    <span>Cari Invoice Penjualan</span>
                </h3>
                <form action="retur.php" method="GET" class="flex gap-3 text-xs">
                    <input type="text" name="invoice" required value="<?= htmlspecialchars($invoice_input) ?>"
                        placeholder="Contoh: TRX-20260614-0001"
                        class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-600 focus:bg-white transition-colors">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 rounded-xl transition-all cursor-pointer shadow-sm">
                        Cari Invoice
                    </button>
                </form>
            </div>

            <!-- Tampilkan detail transaksi jika ditemukan -->
            <?php if ($transaction_data): ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Detail Transaksi Penjualan</h3>
                        <span class="text-[10px] font-bold bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded">TERVERIFIKASI</span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 font-medium">Tanggal Penjualan:</span>
                            <p class="font-bold text-slate-700 mt-0.5"><?= tanggal_indo($transaction_data['tanggal'], true) ?></p>
                        </div>
                        <div>
                            <span class="text-slate-400 font-medium">Kasir Penginput:</span>
                            <p class="font-bold text-slate-700 mt-0.5"><?= htmlspecialchars($transaction_data['kasir_nama']) ?></p>
                        </div>
                    </div>
                    
                    <div class="border border-slate-100 rounded-xl overflow-hidden text-xs">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 font-semibold uppercase">
                                    <th class="py-2.5 px-4">Produk</th>
                                    <th class="py-2.5 px-4 text-center">Qty Beli</th>
                                    <th class="py-2.5 px-4 text-center">Bisa Retur</th>
                                    <th class="py-2.5 px-4 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-slate-600">
                                <?php foreach ($purchased_items as $item): ?>
                                    <tr>
                                        <td class="py-3 px-4 font-semibold text-slate-700"><?= htmlspecialchars($item['nama_produk']) ?></td>
                                        <td class="py-3 px-4 text-center font-medium"><?= $item['qty'] ?> Pcs</td>
                                        <td class="py-3 px-4 text-center">
                                            <?php if ($item['sisa_qty'] <= 0): ?>
                                                <span class="text-red-500 font-bold">0 Pcs (Sudah Retur)</span>
                                            <?php else: ?>
                                                <span class="text-slate-700 font-bold"><?= $item['sisa_qty'] ?> Pcs</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-right font-bold"><?= rupiah($item['subtotal']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Panel Form Input Retur (Right col - 5/12) -->
        <div class="lg:col-span-5">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm h-full">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-6 pb-3 border-b border-slate-100">
                    Input Pengembalian Barang
                </h3>
                
                <?php if ($transaction_data): ?>
                    <form action="retur.php" method="POST" class="space-y-5 text-xs">
                        <input type="hidden" name="action" value="submit_retur">
                        <input type="hidden" name="invoice_penjualan" value="<?= htmlspecialchars($invoice_input) ?>">
                        
                        <div>
                            <label for="id_produk" class="block font-semibold text-slate-500 uppercase tracking-wider mb-2">Pilih Produk untuk Diretur</label>
                            <select name="id_produk" id="id_produk" required onchange="updateMaxQty()"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-600 focus:bg-white transition-all">
                                <option value="" disabled selected>-- Pilih Produk --</option>
                                <?php foreach ($purchased_items as $item): ?>
                                    <?php if ($item['sisa_qty'] > 0): ?>
                                        <option value="<?= $item['id_prod'] ?>" data-max="<?= $item['sisa_qty'] ?>">
                                            <?= htmlspecialchars($item['nama_produk']) ?> (Maks: <?= $item['sisa_qty'] ?> Pcs)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="qty_retur" class="block font-semibold text-slate-500 uppercase tracking-wider mb-2">Jumlah Barang Diretur</label>
                            <input type="number" name="qty_retur" id="qty_retur" required min="1" placeholder="Masukkan jumlah qty"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-600 focus:bg-white transition-all">
                            <span class="text-[10px] text-slate-400 mt-1 block" id="max-qty-helper">Pilih produk terlebih dahulu</span>
                        </div>
                        
                        <div>
                            <label for="alasan" class="block font-semibold text-slate-500 uppercase tracking-wider mb-2">Alasan Pengembalian (Retur)</label>
                            <textarea name="alasan" id="alasan" required placeholder="Contoh: Barang cacat, sobek, kemasan bocor..." rows="4"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-600 focus:bg-white transition-all resize-none"></textarea>
                        </div>
                        
                        <button type="submit" 
                            class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-100 flex items-center justify-center space-x-2 cursor-pointer mt-4">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                            <span>Simpan Retur & Update Stok</span>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="h-full flex flex-col items-center justify-center text-center text-slate-400 py-12">
                        <i data-lucide="undo-2" class="w-10 h-10 text-slate-300 mb-3"></i>
                        <p class="font-semibold text-xs">Belum ada transaksi terpilih.</p>
                        <p class="text-[10px] text-slate-400 mt-1 max-w-[200px] mx-auto">Cari nomor invoice transaksi terlebih dahulu pada panel kiri.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tabel Catatan Riwayat Retur -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-50">
            Log Riwayat Retur Penjualan Terakhir
        </h3>
        <div class="overflow-x-auto">
            <table id="table-retur" class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-12">No</th>
                        <th class="py-3 px-4">Invoice</th>
                        <th class="py-3 px-4">Nama Produk</th>
                        <th class="py-3 px-4 text-center">Qty Retur</th>
                        <th class="py-3 px-4">Alasan Retur</th>
                        <th class="py-3 px-4">Tanggal Retur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    <?php
                    $ret_query = mysqli_query($conn, "
                        SELECT r.*, pr.nama_produk 
                        FROM retur r
                        JOIN produk pr ON r.id_produk = pr.id
                        ORDER BY r.id DESC
                    ");
                    $no = 1;
                    while ($ret = mysqli_fetch_assoc($ret_query)):
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 px-4 font-medium text-slate-400"><?= $no++ ?></td>
                            <td class="py-3 px-4 font-bold text-indigo-600">#<?= htmlspecialchars($ret['invoice_penjualan']) ?></td>
                            <td class="py-3 px-4 font-semibold text-slate-700"><?= htmlspecialchars($ret['nama_produk']) ?></td>
                            <td class="py-3 px-4 text-center font-bold text-red-600"><?= $ret['qty'] ?> Pcs</td>
                            <td class="py-3 px-4"><?= htmlspecialchars($ret['alasan']) ?></td>
                            <td class="py-3 px-4 text-slate-400"><?= tanggal_indo($ret['tanggal']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#table-retur').DataTable({
        language: {
            search: "Cari Log:",
            lengthMenu: "Tampilkan _MENU_ log per halaman",
            zeroRecords: "Belum ada log retur pengembalian",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada log retur",
            infoFiltered: "(difilter dari _MAX_ total data)"
        }
    });
});

// Update batas maksimal input qty retur berdasarkan produk terpilih
function updateMaxQty() {
    const select = document.getElementById('id_produk');
    const selectedOption = select.options[select.selectedIndex];
    const maxQty = parseInt(selectedOption.getAttribute('data-max')) || 0;
    
    const qtyInput = document.getElementById('qty_retur');
    const helper = document.getElementById('max-qty-helper');
    
    qtyInput.max = maxQty;
    qtyInput.placeholder = `Batas: ${maxQty} Pcs`;
    qtyInput.value = ''; // Reset
    helper.innerText = `Jumlah barang yang bisa dikembalikan maksimal ${maxQty} Pcs.`;
}
</script>

<?php include '../layouts/footer.php'; ?>
