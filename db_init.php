<?php
// db_init.php
// Script inisialisasi database melalui PHP

$host = '127.0.0.1';
$username = 'root';
$password = '';
$socket = '/opt/lampp/var/mysql/mysql.sock';

// Koneksi awal ke MySQL server (tanpa nama database)
$conn = @mysqli_connect($host, $username, $password, '', 3306, $socket);
if (!$conn) {
    $conn = @mysqli_connect($host, $username, $password);
}

if (!$conn) {
    die("Koneksi ke MySQL Server gagal: " . mysqli_connect_error() . "\n");
}

echo "Berhasil terhubung ke MySQL Server.\n";

// Baca file database.sql
$sqlFile = __DIR__ . '/database.sql';
if (!file_exists($sqlFile)) {
    die("File database.sql tidak ditemukan!\n");
}

$sqlContent = file_get_contents($sqlFile);

// Jalankan multi query
echo "Sedang mengimpor database.sql...\n";
if (mysqli_multi_query($conn, $sqlContent)) {
    do {
        // Bersihkan hasil query agar tidak error sync
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($conn));
    
    echo "Inisialisasi database 'smart_cashier' berhasil selesai!\n";
} else {
    echo "Gagal mengimpor database: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>
