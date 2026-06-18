<?php
// layouts/footer.php
// Footer layout utama, penutup tag html, dan inisialisasi script JS

$current_page = basename($_SERVER['PHP_SELF']);
?>
        </main>
        
        <!-- Footer Info Toko (Hanya jika bukan halaman login) -->
        <?php if ($current_page !== 'login.php'): ?>
        <footer class="bg-white border-t border-slate-100 py-4 px-8 text-center text-xs text-slate-400">
            <p>&copy; <?= date('Y') ?> <strong><?= htmlspecialchars($nama_toko) ?></strong>. Dikembangkan untuk Expo Jurusan Rekayasa Perangkat Lunak SMK.</p>
        </footer>
        <?php endif; ?>
        
    </div> <!-- Tutup div flex-1 flex flex-col dari sidebar.php -->
    <?php if ($current_page !== 'login.php'): ?>
    </div> <!-- Tutup div flex w-full min-h-screen dari header.php -->
    <?php endif; ?>

    <!-- Inisialisasi Lucide Icons & SweetAlert Flash Messages -->
    <script>
        // Inisialisasi ikon Lucide
        lucide.createIcons();
    </script>

    <!-- Tampilkan Flash Alert SweetAlert2 -->
    <?= show_flash() ?>

</body>
</html>
<?php
// Tutup koneksi database di akhir setiap halaman secara rapi
if (isset($conn)) {
    mysqli_close($conn);
}
?>
