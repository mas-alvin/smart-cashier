<?php
// config/database.php
// Konfigurasi & Auto-Bootstrap Database SMARTPOS UMKM

$host = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'smart_cashier';
$port = 3306;
$socket = '/opt/lampp/var/mysql/mysql.sock';

// 1. Koneksi awal ke MySQL Server (tanpa memilih database dulu)
$conn = @mysqli_connect($host, $username, $password, '', $port, $socket);
if (!$conn) {
    $conn = @mysqli_connect($host, $username, $password);
}

if (!$conn) {
    die("Koneksi MySQL Server gagal: " . mysqli_connect_error());
}

// 2. Cek apakah database 'smart_cashier' sudah ada, jika belum buat database
$db_check = mysqli_query($conn, "SHOW DATABASES LIKE '$database'");
if (mysqli_num_rows($db_check) == 0) {
    if (!mysqli_query($conn, "CREATE DATABASE $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        die("Gagal membuat database: " . mysqli_error($conn));
    }
}

// 3. Pilih database
if (!mysqli_select_db($conn, $database)) {
    die("Gagal memilih database: " . mysqli_error($conn));
}

// 4. Cek apakah tabel utama 'pengguna' sudah ada. Jika belum, jalankan auto-bootstrap
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'pengguna'");
if (mysqli_num_rows($table_check) == 0) {
    $sqlFile = dirname(__DIR__) . '/database.sql';
    if (file_exists($sqlFile)) {
        $sqlContent = file_get_contents($sqlFile);
        
        // Eksekusi semua query di database.sql menggunakan multi_query
        if (mysqli_multi_query($conn, $sqlContent)) {
            do {
                if ($result = mysqli_store_result($conn)) {
                    mysqli_free_result($result);
                }
            } while (mysqli_next_result($conn));
        } else {
            die("Gagal menginisialisasi tabel database: " . mysqli_error($conn));
        }
    } else {
        die("File database.sql tidak ditemukan untuk auto-bootstrap!");
    }
}

// Set charset ke utf8mb4
mysqli_set_charset($conn, 'utf8mb4');
?>
