-- Database creation and schema setup for SMARTPOS UMKM
CREATE DATABASE IF NOT EXISTS smart_cashier;
USE smart_cashier;

-- 1. Pengguna (Users)
CREATE TABLE IF NOT EXISTS pengguna (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kasir', 'owner') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Kategori (Categories)
CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Supplier
CREATE TABLE IF NOT EXISTS supplier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_supplier VARCHAR(100) NOT NULL,
    telepon VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    alamat TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Produk (Products)
CREATE TABLE IF NOT EXISTS produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    foto VARCHAR(255) NULL,
    nama_produk VARCHAR(100) NOT NULL,
    id_kategori INT NOT NULL,
    harga_beli DECIMAL(15,2) NOT NULL,
    harga_jual DECIMAL(15,2) NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    status ENUM('aktif', 'non-aktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Pengeluaran (Expenses)
CREATE TABLE IF NOT EXISTS pengeluaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pengeluaran VARCHAR(150) NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    tanggal DATE NOT NULL,
    keterangan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. Penjualan (Sales)
CREATE TABLE IF NOT EXISTS penjualan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice VARCHAR(50) UNIQUE NOT NULL,
    tanggal DATETIME NOT NULL,
    id_kasir INT NOT NULL,
    total_harga DECIMAL(15,2) NOT NULL,
    bayar DECIMAL(15,2) NOT NULL,
    kembalian DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kasir) REFERENCES pengguna(id)
) ENGINE=InnoDB;

-- 7. Penjualan Detail (Sale Details)
CREATE TABLE IF NOT EXISTS penjualan_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_penjualan INT NOT NULL,
    id_produk INT NOT NULL,
    qty INT NOT NULL,
    harga_beli DECIMAL(15,2) NOT NULL,
    harga_jual DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (id_penjualan) REFERENCES penjualan(id) ON DELETE CASCADE,
    FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Retur Penjualan (Returns)
CREATE TABLE IF NOT EXISTS retur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_penjualan VARCHAR(50) NOT NULL,
    id_produk INT NOT NULL,
    qty INT NOT NULL,
    alasan TEXT NOT NULL,
    tanggal DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Profil Toko (Store Profile)
CREATE TABLE IF NOT EXISTS profil_toko (
    id INT PRIMARY KEY DEFAULT 1,
    nama_toko VARCHAR(100) NOT NULL,
    alamat TEXT NOT NULL,
    nomor_hp VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    logo VARCHAR(255) NULL
) ENGINE=InnoDB;

-- CLEAR OLD DATA
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE penjualan_detail;
TRUNCATE TABLE penjualan;
TRUNCATE TABLE retur;
TRUNCATE TABLE pengeluaran;
TRUNCATE TABLE produk;
TRUNCATE TABLE supplier;
TRUNCATE TABLE kategori;
TRUNCATE TABLE pengguna;
TRUNCATE TABLE profil_toko;
SET FOREIGN_KEY_CHECKS = 1;

-- SEED USERS
INSERT INTO pengguna (id, nama_lengkap, username, password, role) VALUES
(1, 'Administrator POS', 'admin', '$2y$10$m.HVImorn/MCBk8Fgg14n.jKL2aGQcairlGAIbdLmkLSm12zzGcq2', 'admin'),
(2, 'Kasir Utama', 'kasir', '$2y$10$6kyN/yZwP.4A.3amfLz9ieYV.79jgAPmDjZQfKObMrK0A3kDBWeFm', 'kasir'),
(3, 'Owner UMKM', 'owner', '$2y$10$7RsgkU0j9DO2ptR9B5SiJ.uVwsZU8lmGlFtN8ilbKD/8wa1qqmV6e', 'owner');

-- SEED CATEGORIES
INSERT INTO kategori (id, nama_kategori) VALUES
(1, 'Makanan Ringan'),
(2, 'Minuman Segar'),
(3, 'Bahan Pokok'),
(4, 'Elektronik & Gadget'),
(5, 'Alat Tulis Kantor');

-- SEED SUPPLIERS
INSERT INTO supplier (id, nama_supplier, telepon, email, alamat) VALUES
(1, 'PT. Sinar Distribusi', '08122334455', 'sales@sinardistribusi.com', 'Kawasan Industri Candi Blok B, Semarang'),
(2, 'CV. Maju Jaya Grosir', '08577665544', 'admin@majujayagrosir.co.id', 'Jl. Merdeka No. 45, Bandung'),
(3, 'Toko Berkah Utama', '08988776655', 'berkah.toko@gmail.com', 'Pasar Induk Kramat Jati Kios A1, Jakarta');

-- SEED PRODUCTS
INSERT INTO produk (id, foto, nama_produk, id_kategori, harga_beli, harga_jual, stok, status) VALUES
(1, NULL, 'Indomie Goreng Rasa Ayam Panggang', 1, 2600.00, 3100.00, 150, 'aktif'),
(2, NULL, 'Chitato Sapi Panggang 68g', 1, 9500.00, 11500.00, 45, 'aktif'),
(3, NULL, 'Coca-Cola Zero Sugar 390ml', 2, 4800.00, 6000.00, 80, 'aktif'),
(4, NULL, 'Teh Botol Sosro Kotak 250ml', 2, 2700.00, 3500.00, 120, 'aktif'),
(5, NULL, 'Beras Pandan Wangi Cianjur 5kg', 3, 62000.00, 72000.00, 30, 'aktif'),
(6, NULL, 'Minyak Goreng Filma Refill 2L', 3, 28500.00, 32500.00, 25, 'aktif'),
(7, NULL, 'Gula Pasir Rose Brand 1kg', 3, 13500.00, 16000.00, 60, 'aktif'),
(8, NULL, 'Mouse Wireless Logitech M170', 4, 110000.00, 135000.00, 12, 'aktif'),
(9, NULL, 'Kabel Data Type-C Robot 1m', 4, 12000.00, 20000.00, 4, 'aktif'), -- Menipis status
(10, NULL, 'Buku Tulis Kiky A5 38 Lembar', 5, 2500.00, 3500.00, 0, 'aktif'); -- Habis status

-- SEED EXPENSES
INSERT INTO pengeluaran (id, nama_pengeluaran, kategori, nominal, tanggal, keterangan) VALUES
(1, 'Tagihan Listrik Toko Mei 2026', 'Operasional', 350000.00, '2026-05-15', 'Listrik prabayar token toko'),
(2, 'Pembelian Plastik Packing', 'Perlengkapan', 45000.00, '2026-06-02', 'Plastik HD tebal ukuran 28 & 35'),
(3, 'Sewa Wifi Toko Juni 2026', 'Operasional', 220000.00, '2026-06-05', 'Indihome 30 Mbps');

-- SEED STORE PROFILE
INSERT INTO profil_toko (id, nama_toko, alamat, nomor_hp, email, logo) VALUES
(1, 'SMARTPOS UMKM', 'Jl. Pendidikan No. 123, Komplek SMK Negeri 1, Jakarta Selatan', '0812-3456-7890', 'info@smartpos-umkm.id', NULL);

-- SEED TRANSACTIONS (PAST 7 DAYS)
-- Day -6
INSERT INTO penjualan (id, invoice, tanggal, id_kasir, total_harga, bayar, kembalian) VALUES
(1, 'TRX-20260608-001', DATE_SUB(NOW(), INTERVAL 6 DAY), 2, 75000.00, 100000.00, 25000.00);
INSERT INTO penjualan_detail (id_penjualan, id_produk, qty, harga_beli, harga_jual, subtotal) VALUES
(1, 6, 2, 28500.00, 32500.00, 65000.00),
(1, 4, 2, 2700.00, 3500.00, 7000.00),
(1, 1, 1, 2600.00, 3100.00, 3100.00);

-- Day -5
INSERT INTO penjualan (id, invoice, tanggal, id_kasir, total_harga, bayar, kembalian) VALUES
(2, 'TRX-20260609-001', DATE_SUB(NOW(), INTERVAL 5 DAY), 2, 143500.00, 150000.00, 6500.00);
INSERT INTO penjualan_detail (id_penjualan, id_produk, qty, harga_beli, harga_jual, subtotal) VALUES
(2, 8, 1, 110000.00, 135000.00, 135000.00),
(2, 7, 1, 13500.00, 16000.00, 16000.00),
(2, 4, 1, 2700.00, 3500.00, 3500.00);

-- Day -4
INSERT INTO penjualan (id, invoice, tanggal, id_kasir, total_harga, bayar, kembalian) VALUES
(3, 'TRX-20260610-001', DATE_SUB(NOW(), INTERVAL 4 DAY), 2, 98000.00, 100000.00, 2000.00);
INSERT INTO penjualan_detail (id_penjualan, id_produk, qty, harga_beli, harga_jual, subtotal) VALUES
(3, 2, 5, 9500.00, 11500.00, 57500.00),
(3, 3, 5, 4800.00, 6000.00, 30000.00),
(3, 4, 3, 2700.00, 3500.00, 10500.00);

-- Day -3
INSERT INTO penjualan (id, invoice, tanggal, id_kasir, total_harga, bayar, kembalian) VALUES
(4, 'TRX-20260611-001', DATE_SUB(NOW(), INTERVAL 3 DAY), 2, 216000.00, 220000.00, 4000.00);
INSERT INTO penjualan_detail (id_penjualan, id_produk, qty, harga_beli, harga_jual, subtotal) VALUES
(4, 5, 3, 62000.00, 72000.00, 216000.00);

-- Day -2
INSERT INTO penjualan (id, invoice, tanggal, id_kasir, total_harga, bayar, kembalian) VALUES
(5, 'TRX-20260612-001', DATE_SUB(NOW(), INTERVAL 2 DAY), 2, 85000.00, 100000.00, 15000.00);
INSERT INTO penjualan_detail (id_penjualan, id_produk, qty, harga_beli, harga_jual, subtotal) VALUES
(5, 6, 1, 28500.00, 32500.00, 32500.00),
(5, 7, 2, 13500.00, 16000.00, 32000.00),
(5, 9, 1, 12000.00, 20000.00, 20000.00);

-- Day -1
INSERT INTO penjualan (id, invoice, tanggal, id_kasir, total_harga, bayar, kembalian) VALUES
(6, 'TRX-20260613-001', DATE_SUB(NOW(), INTERVAL 1 DAY), 2, 160600.00, 200000.00, 39400.00);
INSERT INTO penjualan_detail (id_penjualan, id_produk, qty, harga_beli, harga_jual, subtotal) VALUES
(6, 5, 2, 62000.00, 72000.00, 144000.00),
(6, 1, 2, 2600.00, 3100.00, 6200.00),
(6, 3, 1, 4800.00, 6000.00, 6000.00),
(6, 4, 1, 2700.00, 3500.00, 3500.00),
(6, 10, 0, 2500.00, 3500.00, 0.00);

-- Today
INSERT INTO penjualan (id, invoice, tanggal, id_kasir, total_harga, bayar, kembalian) VALUES
(7, 'TRX-20260614-001', NOW(), 2, 34500.00, 50000.00, 15500.00);
INSERT INTO penjualan_detail (id_penjualan, id_produk, qty, harga_beli, harga_jual, subtotal) VALUES
(7, 2, 3, 9500.00, 11500.00, 34500.00);
