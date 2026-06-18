<?php
// transaksi/cetak_struk.php
// Halaman struk penjualan thermal - Auto Print

session_start();
require_once '../config/database.php';
require_once '../helpers/format.php';

// Validasi Login & Parameter Invoice
if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak! Silakan login terlebih dahulu.");
}

$invoice = isset($_GET['invoice']) ? trim(mysqli_real_escape_string($conn, $_GET['invoice'])) : '';

if (empty($invoice)) {
    die("Parameter invoice tidak ditemukan!");
}

// 1. Ambil data penjualan & kasir
$sales_query = mysqli_query($conn, "
    SELECT p.*, u.nama_lengkap AS kasir_nama 
    FROM penjualan p 
    JOIN pengguna u ON p.id_kasir = u.id 
    WHERE p.invoice = '$invoice' 
    LIMIT 1
");
$sales = mysqli_fetch_assoc($sales_query);

if (!$sales) {
    die("Data transaksi dengan invoice '$invoice' tidak ditemukan!");
}

// 2. Ambil data profil toko
$toko_query = mysqli_query($conn, "SELECT * FROM profil_toko LIMIT 1");
$toko = mysqli_fetch_assoc($toko_query);
$nama_toko = $toko['nama_toko'] ?? 'SMARTPOS UMKM';
$alamat_toko = $toko['alamat'] ?? '-';
$hp_toko = $toko['nomor_hp'] ?? '-';
$email_toko = $toko['email'] ?? '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Struk #<?= htmlspecialchars($invoice) ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
            width: 300px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .bold {
            font-weight: bold;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .header {
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0 0 4px 0;
        }
        .header p {
            margin: 2px 0;
            font-size: 10px;
        }
        .meta-table, .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td, .items-table td {
            padding: 2px 0;
        }
        .items-table th {
            text-align: left;
            border-bottom: 1px dashed #000;
            padding-bottom: 4px;
        }
        .footer {
            margin-top: 20px;
            font-size: 10px;
        }
        /* CSS print overrides */
        @media print {
            body {
                padding: 0;
                margin: 0;
                width: 100%;
            }
            @page {
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Header Toko -->
    <div class="header text-center">
        <h1><?= htmlspecialchars($nama_toko) ?></h1>
        <p><?= htmlspecialchars($alamat_toko) ?></p>
        <p>Telp: <?= htmlspecialchars($hp_toko) ?> | Email: <?= htmlspecialchars($email_toko) ?></p>
    </div>

    <div class="divider"></div>

    <!-- Meta Transaksi -->
    <table class="meta-table">
        <tr>
            <td>No. Invoice:</td>
            <td class="text-right"><?= htmlspecialchars($sales['invoice']) ?></td>
        </tr>
        <tr>
            <td>Tanggal:</td>
            <td class="text-right"><?= date('d/m/Y H:i', strtotime($sales['tanggal'])) ?></td>
        </tr>
        <tr>
            <td>Kasir:</td>
            <td class="text-right"><?= htmlspecialchars($sales['kasir_nama']) ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Daftar Item Belanja -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Item</th>
                <th style="width: 15%; text-align: center;">Qty</th>
                <th style="width: 35%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $details_query = mysqli_query($conn, "
                SELECT pd.*, p.nama_produk 
                FROM penjualan_detail pd 
                JOIN produk p ON pd.id_produk = p.id 
                WHERE pd.id_penjualan = {$sales['id']}
            ");
            while ($item = mysqli_fetch_assoc($details_query)):
            ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($item['nama_produk']) ?><br>
                        <small>@<?= number_format($item['harga_jual'], 0, ',', '.') ?></small>
                    </td>
                    <td style="text-align: center; vertical-align: top;"><?= $item['qty'] ?></td>
                    <td class="text-right" style="vertical-align: top;"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <!-- Ringkasan Total Pembayaran -->
    <table class="meta-table">
        <tr class="bold">
            <td>GRAND TOTAL:</td>
            <td class="text-right">Rp <?= number_format($sales['total_harga'], 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td>TUNAI/BAYAR:</td>
            <td class="text-right">Rp <?= number_format($sales['bayar'], 0, ',', '.') ?></td>
        </tr>
        <tr class="bold">
            <td>KEMBALIAN:</td>
            <td class="text-right">Rp <?= number_format($sales['kembalian'], 0, ',', '.') ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Footer Struk -->
    <div class="footer text-center">
        <p class="bold">TERIMA KASIH</p>
        <p>Sudah berbelanja di tempat kami.</p>
        <p>Struk ini dicetak otomatis menggunakan <?= htmlspecialchars($nama_toko) ?>.</p>
    </div>

    <!-- Trigger Auto Print -->
    <script>
        window.onload = function() {
            window.print();
            // Tutup window struk setelah print dialog selesai (opsional)
            // setTimeout(function() { window.close(); }, 500);
        }
    </script>

</body>
</html>
