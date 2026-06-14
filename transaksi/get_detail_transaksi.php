<?php
// transaksi/get_detail_transaksi.php
// API Handler untuk mengambil detail transaksi via AJAX (JSON)

session_start();
header('Content-Type: application/json');

// Proteksi akses
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesi kedaluwarsa. Silakan login kembali.']);
    exit();
}

require_once '../config/database.php';
require_once '../helpers/format.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$invoice = isset($_GET['invoice']) ? trim(mysqli_real_escape_string($conn, $_GET['invoice'])) : '';

if ($id <= 0 && empty($invoice)) {
    echo json_encode(['success' => false, 'message' => 'Parameter ID atau Invoice tidak valid.']);
    exit();
}

// 1. Dapatkan data transaksi utama
if ($id > 0) {
    $sales_query = mysqli_query($conn, "
        SELECT p.*, u.nama_lengkap AS kasir_nama 
        FROM penjualan p 
        JOIN pengguna u ON p.id_kasir = u.id 
        WHERE p.id = $id 
        LIMIT 1
    ");
} else {
    $sales_query = mysqli_query($conn, "
        SELECT p.*, u.nama_lengkap AS kasir_nama 
        FROM penjualan p 
        JOIN pengguna u ON p.id_kasir = u.id 
        WHERE p.invoice = '$invoice' 
        LIMIT 1
    ");
}

$sales = mysqli_fetch_assoc($sales_query);

if (!$sales) {
    echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan.']);
    exit();
}

// Format tanggal untuk dikirim
$sales['tanggal_indo'] = tanggal_indo($sales['tanggal'], true);

// 2. Dapatkan data detail produk terjual
$id_penjualan = $sales['id'];
$items_query = mysqli_query($conn, "
    SELECT pd.*, pr.nama_produk 
    FROM penjualan_detail pd 
    JOIN produk pr ON pd.id_produk = pr.id 
    WHERE pd.id_penjualan = $id_penjualan
");

$items = [];
while ($row = mysqli_fetch_assoc($items_query)) {
    $items[] = $row;
}

$sales['items'] = $items;

// Kirim response JSON sukses
echo json_encode([
    'success' => true,
    'data' => $sales
]);
exit();
?>
