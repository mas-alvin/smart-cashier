<?php
// index.php
// Halaman entry point utama: Cek session dan arahkan ke login atau dashboard

session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard/index.php");
    exit();
} else {
    header("Location: /login.php");
    exit();
}
?>
