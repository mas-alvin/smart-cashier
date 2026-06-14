<?php
// transaksi/kasir.php
// Halaman POS (Point of Sale) Kasir - Hanya untuk Admin dan Kasir

include '../layouts/header.php';
include '../layouts/sidebar.php';
include '../layouts/navbar.php';

// Validasi Hak Akses: Admin dan Kasir
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kasir') {
    set_flash('error', 'Akses Ditolak', 'Halaman ini hanya dapat diakses oleh Administrator atau Kasir!');
    echo "<script>window.location.href='/smart-cashier/dashboard/index.php';</script>";
    exit();
}

// --- PROSES CHECKOUT TRANSAKSI ---
if (isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $total_harga = (float)$_POST['total_harga'];
    $bayar = (float)$_POST['bayar'];
    $kembalian = $bayar - $total_harga;
    $id_kasir = $_SESSION['user_id'];
    $tanggal = date('Y-m-d H:i:s');
    
    // Generate Invoice ID unik: TRX-YYYYMMDD-XXXX
    $date_prefix = date('Ymd');
    $inv_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM penjualan WHERE DATE(tanggal) = CURDATE()");
    $inv_count = mysqli_fetch_assoc($inv_query)['total'] + 1;
    $invoice = "TRX-" . $date_prefix . "-" . sprintf("%04d", $inv_count);
    
    // Mulai Transaksi Database secara aman
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Simpan Transaksi Penjualan Utama
        $query_sales = "INSERT INTO penjualan (invoice, tanggal, id_kasir, total_harga, bayar, kembalian) 
                        VALUES ('$invoice', '$tanggal', $id_kasir, $total_harga, $bayar, $kembalian)";
        if (!mysqli_query($conn, $query_sales)) {
            throw new Exception("Gagal menyimpan data penjualan: " . mysqli_error($conn));
        }
        $id_penjualan = mysqli_insert_id($conn);
        
        // 2. Simpan Detail Item Penjualan
        if (isset($_POST['produk_id']) && is_array($_POST['produk_id'])) {
            for ($i = 0; $i < count($_POST['produk_id']); $i++) {
                $id_produk = (int)$_POST['produk_id'][$i];
                $qty = (int)$_POST['qty'][$i];
                
                // Ambil harga beli, harga jual, dan stok produk saat ini
                $prod_query = mysqli_query($conn, "SELECT harga_beli, harga_jual, stok, nama_produk FROM produk WHERE id = $id_produk");
                $prod = mysqli_fetch_assoc($prod_query);
                
                if (!$prod) {
                    throw new Exception("Produk dengan ID $id_produk tidak ditemukan!");
                }
                
                // Validasi kecukupan stok
                if ($prod['stok'] < $qty) {
                    throw new Exception("Stok produk '" . $prod['nama_produk'] . "' tidak mencukupi! Tersisa: " . $prod['stok']);
                }
                
                $harga_beli = $prod['harga_beli'];
                $harga_jual = $prod['harga_jual'];
                $subtotal = $harga_jual * $qty;
                
                // Simpan detail ke DB
                $query_detail = "INSERT INTO penjualan_detail (id_penjualan, id_produk, qty, harga_beli, harga_jual, subtotal) 
                                 VALUES ($id_penjualan, $id_produk, $qty, $harga_beli, $harga_jual, $subtotal)";
                if (!mysqli_query($conn, $query_detail)) {
                    throw new Exception("Gagal menyimpan detail penjualan: " . mysqli_error($conn));
                }
                
                // Potong Stok Produk
                $new_stok = $prod['stok'] - $qty;
                $query_stok = "UPDATE produk SET stok = $new_stok WHERE id = $id_produk";
                if (!mysqli_query($conn, $query_stok)) {
                    throw new Exception("Gagal memperbarui stok produk: " . mysqli_error($conn));
                }
            }
        } else {
            throw new Exception("Keranjang belanja kosong!");
        }
        
        // Commit transaksi database
        mysqli_commit($conn);
        
        // Redirect dan tampilkan struk cetak
        set_flash('success', 'Transaksi Berhasil', 'Kembalian: ' . rupiah($kembalian));
        echo "<script>
            window.open('cetak_struk.php?invoice=$invoice', '_blank', 'width=450,height=700');
            window.location.href = 'kasir.php';
        </script>";
        exit();
        
    } catch (Exception $e) {
        // Rollback jika terjadi kesalahan
        mysqli_rollback($conn);
        set_flash('error', 'Transaksi Gagal', $e->getMessage());
        echo "<script>window.location.href='kasir.php';</script>";
        exit();
    }
}
?>

<!-- Halaman Kasir Layout -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 h-[calc(100vh-140px)]">
    <!-- KOLOM KIRI: Daftar Produk Grid (7 dari 12 kolom) -->
    <div class="lg:col-span-7 flex flex-col h-full overflow-hidden space-y-6">
        <!-- Header, Cari, dan Filter Kategori -->
        <div class="space-y-4 flex-shrink-0">
            <div>
                <nav class="flex text-xs text-slate-400 space-x-2 mb-1 font-medium">
                    <a href="/smart-cashier/dashboard/index.php" class="hover:text-slate-600">Dashboard</a>
                    <span>/</span>
                    <span class="text-indigo-600">Kasir POS</span>
                </nav>
                <h1 class="text-xl font-bold text-slate-800 tracking-tight">Kasir Penjualan</h1>
            </div>
            
            <!-- Pencarian & Kategori Filter -->
            <div class="flex flex-col md:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" id="search-produk" placeholder="Cari produk berdasarkan nama..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-600 transition-colors">
                </div>
                
                <select id="kategori-filter" 
                    class="bg-white border border-slate-200 rounded-xl text-xs py-2 px-3 focus:outline-none focus:border-indigo-600">
                    <option value="">Semua Kategori</option>
                    <?php
                    $kat_query = mysqli_query($conn, "SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
                    while ($kat = mysqli_fetch_assoc($kat_query)) {
                        echo "<option value='" . htmlspecialchars($kat['nama_kategori']) . "'>" . htmlspecialchars($kat['nama_kategori']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Grid Container Produk (Scrollable) -->
        <div class="flex-1 overflow-y-auto pr-1">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="produk-grid">
                <?php
                $prod_query = mysqli_query($conn, "
                    SELECT p.*, k.nama_kategori 
                    FROM produk p 
                    JOIN kategori k ON p.id_kategori = k.id 
                    WHERE p.status = 'aktif'
                    ORDER BY p.nama_produk ASC
                ");
                while ($prod = mysqli_fetch_assoc($prod_query)):
                    $foto_path = '../assets/uploads/' . $prod['foto'];
                    $foto_url = (!empty($prod['foto']) && file_exists($foto_path)) ? '/assets/uploads/' . $prod['foto'] : '';
                ?>
                    <!-- Card Produk -->
                    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden produk-card"
                         data-nama="<?= strtolower(htmlspecialchars($prod['nama_produk'])) ?>"
                         data-kategori="<?= htmlspecialchars($prod['nama_kategori']) ?>">
                        <div>
                            <!-- Foto Produk -->
                            <div class="w-full h-28 bg-slate-50 border border-slate-100 rounded-lg flex items-center justify-center text-slate-300 mb-3 overflow-hidden">
                                <?php if ($foto_url): ?>
                                    <img src="<?= $foto_url ?>" alt="Foto" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i data-lucide="image" class="w-8 h-8"></i>
                                <?php endif; ?>
                            </div>
                            <!-- Detail Produk -->
                            <span class="text-[9px] font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded uppercase tracking-wider"><?= htmlspecialchars($prod['nama_kategori']) ?></span>
                            <h4 class="text-xs font-bold text-slate-800 mt-1.5 line-clamp-2 leading-tight"><?= htmlspecialchars($prod['nama_produk']) ?></h4>
                        </div>
                        
                        <div class="mt-4">
                            <!-- Stok -->
                            <div class="flex items-center justify-between text-[10px] text-slate-400 mb-2 font-medium">
                                <span>Stok:</span>
                                <?php if ($prod['stok'] <= 0): ?>
                                    <span class="text-red-500 font-bold">Habis</span>
                                <?php elseif ($prod['stok'] <= 5): ?>
                                    <span class="text-amber-500 font-bold"><?= $prod['stok'] ?> Pcs</span>
                                <?php else: ?>
                                    <span class="text-slate-600 font-bold"><?= $prod['stok'] ?> Pcs</span>
                                <?php endif; ?>
                            </div>
                            <!-- Harga & Tambah Button -->
                            <div class="flex items-center justify-between pt-1">
                                <span class="text-xs font-extrabold text-slate-800"><?= rupiah($prod['harga_jual']) ?></span>
                                <?php if ($prod['stok'] > 0): ?>
                                    <button onclick="addToCart(<?= htmlspecialchars(json_encode([
                                        'id' => $prod['id'],
                                        'nama' => $prod['nama_produk'],
                                        'harga' => (float)$prod['harga_jual'],
                                        'stok' => (int)$prod['stok']
                                    ])) ?>)"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white p-1.5 rounded-lg transition-colors cursor-pointer shadow-sm shadow-indigo-100 flex items-center justify-center">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    </button>
                                <?php else: ?>
                                    <button disabled class="bg-slate-100 text-slate-400 p-1.5 rounded-lg cursor-not-allowed">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN: Keranjang Belanja & Pembayaran (5 dari 12 kolom) -->
    <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between h-full shadow-sm overflow-hidden">
        <!-- Form Utama POS -->
        <form action="kasir.php" method="POST" id="checkout-form" class="flex flex-col h-full justify-between">
            <input type="hidden" name="action" value="checkout">
            
            <!-- Keranjang Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <i data-lucide="shopping-cart" class="w-4 h-4 text-indigo-600"></i>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Keranjang Belanja</h3>
                </div>
                <button type="button" onclick="clearCart()" class="text-[10px] text-red-500 hover:text-red-700 font-semibold uppercase flex items-center space-x-1 cursor-pointer">
                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                    <span>Kosongkan</span>
                </button>
            </div>

            <!-- List Item Cart (Scrollable) -->
            <div class="flex-1 overflow-y-auto py-4 space-y-3 pr-1" id="cart-list">
                <!-- Data Keranjang Kosong State -->
                <div id="cart-empty" class="h-full flex flex-col items-center justify-center text-center text-slate-400 py-12">
                    <i data-lucide="shopping-bag" class="w-10 h-10 text-slate-300 mb-3"></i>
                    <p class="text-xs font-semibold">Keranjang masih kosong.</p>
                    <p class="text-[10px] text-slate-400 mt-1">Klik tombol (+) pada produk untuk menambahkannya.</p>
                </div>
            </div>

            <!-- Panel Pembayaran & Ringkasan Belanja (Sticky Bottom inside Right Col) -->
            <div class="border-t border-slate-100 pt-4 space-y-4 flex-shrink-0 bg-white">
                <!-- Info Ringkasan Tagihan -->
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between text-slate-500">
                        <span>Total Item:</span>
                        <span class="font-bold text-slate-700" id="total-qty-text">0 Pcs</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-800 pt-1 border-t border-slate-50">
                        <span class="font-bold">Total Pembayaran:</span>
                        <span class="text-lg font-extrabold text-indigo-600" id="total-harga-text">Rp 0</span>
                        <input type="hidden" name="total_harga" id="total-harga-input" value="0">
                    </div>
                </div>

                <!-- Input Nominal Bayar -->
                <div class="space-y-2">
                    <label for="bayar-input" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Uang Dibayarkan (Cash)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none font-bold text-xs text-slate-400">
                            Rp
                        </div>
                        <input type="number" name="bayar" id="bayar-input" required min="0" placeholder="0"
                            class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
                    </div>
                </div>

                <!-- Perhitungan Kembalian -->
                <div class="flex items-center justify-between bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Uang Kembalian:</span>
                    <span class="text-sm font-black text-slate-700" id="kembalian-text">Rp 0</span>
                </div>

                <!-- Button Checkout -->
                <button type="submit" id="btn-submit-checkout" disabled
                    class="w-full py-3 bg-slate-100 text-slate-400 text-xs font-bold rounded-xl transition-all flex items-center justify-center space-x-2 cursor-not-allowed">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span>Simpan & Cetak Struk</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// State Keranjang POS (Local Array Memory)
let cart = [];

// Tambah ke Keranjang
function addToCart(produk) {
    // Cek apakah produk sudah ada di keranjang
    const index = cart.findIndex(item => item.id === produk.id);
    
    if (index !== -1) {
        // Jika stok masih cukup, tambah qty
        if (cart[index].qty < produk.stok) {
            cart[index].qty++;
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Stok Terbatas',
                text: `Stok produk "${produk.nama}" hanya tersedia ${produk.stok} pcs.`,
                confirmButtonColor: '#4f46e5'
            });
        }
    } else {
        // Tambah produk baru ke keranjang
        cart.push({
            id: produk.id,
            nama: produk.nama,
            harga: produk.harga,
            stok: produk.stok,
            qty: 1
        });
    }
    
    updateCartUI();
}

// Ubah Quantity
function updateQty(id, delta) {
    const index = cart.findIndex(item => item.id === id);
    if (index !== -1) {
        const item = cart[index];
        const newQty = item.qty + delta;
        
        if (newQty <= 0) {
            cart.splice(index, 1); // Hapus jika qty 0
        } else if (newQty > item.stok) {
            Swal.fire({
                icon: 'warning',
                title: 'Stok Terbatas',
                text: `Stok produk "${item.nama}" hanya tersedia ${item.stok} pcs.`,
                confirmButtonColor: '#4f46e5'
            });
        } else {
            item.qty = newQty;
        }
    }
    updateCartUI();
}

// Hapus Item
function removeItem(id) {
    cart = cart.filter(item => item.id !== id);
    updateCartUI();
}

// Kosongkan Keranjang
function clearCart() {
    cart = [];
    updateCartUI();
}

// Sinkronisasi Cart Array ke Tampilan HTML (DOM)
function updateCartUI() {
    const cartList = document.getElementById('cart-list');
    const cartEmpty = document.getElementById('cart-empty');
    
    // Hapus data item render sebelumnya
    const items = cartList.querySelectorAll('.cart-item-render');
    items.forEach(el => el.remove());
    
    if (cart.length === 0) {
        cartEmpty.classList.remove('hidden');
        document.getElementById('total-qty-text').innerText = '0 Pcs';
        document.getElementById('total-harga-text').innerText = 'Rp 0';
        document.getElementById('total-harga-input').value = 0;
        document.getElementById('kembalian-text').innerText = 'Rp 0';
        document.getElementById('bayar-input').value = '';
        toggleCheckoutButton(false);
        return;
    }
    
    cartEmpty.classList.add('hidden');
    
    let totalQty = 0;
    let totalHarga = 0;
    
    cart.forEach(item => {
        totalQty += item.qty;
        const subtotal = item.harga * item.qty;
        totalHarga += subtotal;
        
        const itemHTML = `
            <div class="cart-item-render flex items-center justify-between border-b border-slate-50 pb-3">
                <!-- Input Hidden untuk diposting ke PHP -->
                <input type="hidden" name="produk_id[]" value="${item.id}">
                <input type="hidden" name="qty[]" value="${item.qty}">
                
                <div class="overflow-hidden flex-1 pr-3">
                    <h4 class="text-xs font-bold text-slate-800 truncate">${item.nama}</h4>
                    <span class="text-[10px] text-slate-400 font-medium">${formatRupiah(item.harga)}</span>
                </div>
                
                <!-- Qty Controller & Subtotal -->
                <div class="flex items-center space-x-3.5 flex-shrink-0">
                    <div class="flex items-center border border-slate-200 rounded-lg bg-slate-50">
                        <button type="button" onclick="updateQty(${item.id}, -1)" class="px-2 py-1 text-slate-400 hover:text-slate-700 font-bold">-</button>
                        <span class="px-2 text-xs font-bold text-slate-700 select-none">${item.qty}</span>
                        <button type="button" onclick="updateQty(${item.id}, 1)" class="px-2 py-1 text-slate-400 hover:text-slate-700 font-bold">+</button>
                    </div>
                    <span class="text-xs font-bold text-slate-800 w-16 text-right">${formatRupiah(subtotal)}</span>
                    <button type="button" onclick="removeItem(${item.id})" class="text-slate-300 hover:text-red-500 transition-colors">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
        `;
        cartList.insertAdjacentHTML('beforeend', itemHTML);
    });
    
    // Update Ringkasan Total
    document.getElementById('total-qty-text').innerText = totalQty + ' Pcs';
    document.getElementById('total-harga-text').innerText = formatRupiah(totalHarga);
    document.getElementById('total-harga-input').value = totalHarga;
    
    // Trigger perhitungan kembalian
    hitungKembalian();
    
    // Redraw Lucide Icons
    lucide.createIcons();
}

// Hitung Kembalian & Validasi Input Nominal Bayar
function hitungKembalian() {
    const totalHarga = parseFloat(document.getElementById('total-harga-input').value) || 0;
    const bayar = parseFloat(document.getElementById('bayar-input').value) || 0;
    const kembalianText = document.getElementById('kembalian-text');
    
    if (totalHarga <= 0) {
        kembalianText.innerText = 'Rp 0';
        toggleCheckoutButton(false);
        return;
    }
    
    const kembalian = bayar - totalHarga;
    
    if (bayar >= totalHarga) {
        kembalianText.innerText = formatRupiah(kembalian);
        kembalianText.classList.remove('text-slate-700', 'text-red-500');
        kembalianText.classList.add('text-indigo-600');
        toggleCheckoutButton(true);
    } else {
        kembalianText.innerText = 'Uang kurang ' + formatRupiah(Math.abs(kembalian));
        kembalianText.classList.remove('text-slate-700', 'text-indigo-600');
        kembalianText.classList.add('text-red-500');
        toggleCheckoutButton(false);
    }
}

// Aktifkan / Matikan Button Checkout
function toggleCheckoutButton(active) {
    const btn = document.getElementById('btn-submit-checkout');
    if (active) {
        btn.disabled = false;
        btn.classList.remove('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
        btn.classList.add('bg-indigo-600', 'hover:bg-indigo-700', 'text-white', 'cursor-pointer', 'shadow-md', 'shadow-indigo-100');
    } else {
        btn.disabled = true;
        btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700', 'text-white', 'cursor-pointer', 'shadow-md', 'shadow-indigo-100');
        btn.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
    }
}

// Format number to Indonesian Rupiah (Client Side)
function formatRupiah(number) {
    return 'Rp ' + number.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

// Event Listeners: Real-Time Input Uang Cash & Form Submit
document.getElementById('bayar-input').addEventListener('input', hitungKembalian);

document.getElementById('checkout-form').addEventListener('submit', function(e) {
    if (cart.length === 0) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Transaksi Gagal',
            text: 'Keranjang belanja Anda kosong!',
            confirmButtonColor: '#4f46e5'
        });
    }
});

// Event Listeners: Search dan Category Filter
document.getElementById('search-produk').addEventListener('input', filterProduk);
document.getElementById('kategori-filter').addEventListener('change', filterProduk);

function filterProduk() {
    const query = document.getElementById('search-produk').value.toLowerCase().trim();
    const kategori = document.getElementById('kategori-filter').value;
    const cards = document.querySelectorAll('.produk-card');
    
    cards.forEach(card => {
        const cardNama = card.getAttribute('data-nama');
        const cardKategori = card.getAttribute('data-kategori');
        
        const matchNama = cardNama.includes(query);
        const matchKategori = !kategori || cardKategori === kategori;
        
        if (matchNama && matchKategori) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}
</script>

<?php include '../layouts/footer.php'; ?>
