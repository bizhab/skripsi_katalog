-- ============================================================
-- DATABASE: Katalog Skripsi & Tugas Akhir Digital
-- Standar: IEEE Std 830-1998
-- ============================================================

CREATE DATABASE IF NOT EXISTS katalog_skripsi
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE katalog_skripsi;

-- ============================================================
-- TABEL: admin
-- Sesuai FR07: Sistem Autentikasi Admin
-- NFR02: Password dienkripsi dengan Bcrypt
-- ============================================================
CREATE TABLE IF NOT EXISTS admin (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(50)  NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed',
  nama_lengkap VARCHAR(100) NOT NULL,
  email       VARCHAR(100) NOT NULL UNIQUE,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL: program_studi
-- Sesuai FR02: Filter berdasarkan Program Studi
-- ============================================================
CREATE TABLE IF NOT EXISTS program_studi (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode      VARCHAR(20)  NOT NULL UNIQUE,
  nama      VARCHAR(100) NOT NULL,
  fakultas  VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL: kategori
-- Sesuai FR05: Manajemen Kategori (AI, Web, Jaringan, dll.)
-- ============================================================
CREATE TABLE IF NOT EXISTS kategori (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(100) NOT NULL,
  slug        VARCHAR(120) NOT NULL UNIQUE,
  deskripsi   TEXT,
  warna_hex   VARCHAR(7) DEFAULT '#3B82F6' COMMENT 'Warna badge kategori',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL: dosen
-- Sesuai FR02: Filter berdasarkan Dosen Pembimbing
-- ============================================================
CREATE TABLE IF NOT EXISTS dosen (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nip         VARCHAR(30) UNIQUE,
  nama        VARCHAR(150) NOT NULL,
  email       VARCHAR(100),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL: skripsi
-- Tabel utama — sesuai FR01, FR02, FR03
-- NFR05: Mendukung backup data integritas
-- ============================================================
CREATE TABLE IF NOT EXISTS skripsi (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nim               VARCHAR(20)  NOT NULL UNIQUE   COMMENT 'NIM unik—cegah duplikasi (Risiko 2)',
  nama_penulis      VARCHAR(150) NOT NULL,
  judul             VARCHAR(500) NOT NULL,
  judul_hash        CHAR(64) GENERATED ALWAYS AS (SHA2(LOWER(TRIM(judul)), 256)) STORED
                    COMMENT 'Hash untuk deteksi duplikasi judul (Risiko 2)',
  abstrak           TEXT,
  kata_kunci        VARCHAR(500) COMMENT 'Dipisah koma, di-index (Risiko 3)',
  tahun_lulus       YEAR        NOT NULL,
  program_studi_id  INT UNSIGNED NOT NULL,
  kategori_id       INT UNSIGNED,
  dosen_pembimbing1_id INT UNSIGNED,
  dosen_pembimbing2_id INT UNSIGNED,
  file_pdf          VARCHAR(255) COMMENT 'Path relatif file PDF',
  ukuran_file_kb    INT UNSIGNED COMMENT 'KB — maks 10240 (10MB) (Risiko 1)',
  status            ENUM('aktif','arsip','dihapus') DEFAULT 'aktif',
  view_count        INT UNSIGNED DEFAULT 0,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_skripsi_prodi
    FOREIGN KEY (program_studi_id) REFERENCES program_studi(id) ON DELETE RESTRICT,
  CONSTRAINT fk_skripsi_kategori
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL,
  CONSTRAINT fk_skripsi_dosen1
    FOREIGN KEY (dosen_pembimbing1_id) REFERENCES dosen(id) ON DELETE SET NULL,
  CONSTRAINT fk_skripsi_dosen2
    FOREIGN KEY (dosen_pembimbing2_id) REFERENCES dosen(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- INDEX — NFR01 & Risiko 3: Pencarian cepat < 2 detik
-- ============================================================
CREATE FULLTEXT INDEX idx_ft_skripsi
  ON skripsi (judul, abstrak, kata_kunci, nama_penulis);

CREATE INDEX idx_skripsi_nim         ON skripsi (nim);
CREATE INDEX idx_skripsi_tahun       ON skripsi (tahun_lulus);
CREATE INDEX idx_skripsi_prodi       ON skripsi (program_studi_id);
CREATE INDEX idx_skripsi_kategori    ON skripsi (kategori_id);
CREATE INDEX idx_skripsi_dosen1      ON skripsi (dosen_pembimbing1_id);
CREATE INDEX idx_skripsi_status      ON skripsi (status);

-- Deteksi duplikasi judul (Risiko 2)
CREATE UNIQUE INDEX idx_skripsi_judul_hash ON skripsi (judul_hash);

-- ============================================================
-- TABEL: log_aktivitas
-- Audit trail untuk semua aksi admin
-- ============================================================
CREATE TABLE IF NOT EXISTS log_aktivitas (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT UNSIGNED,
  aksi        VARCHAR(50)  NOT NULL COMMENT 'tambah, edit, hapus, login, logout',
  tabel       VARCHAR(50),
  record_id   INT UNSIGNED,
  keterangan  TEXT,
  ip_address  VARCHAR(45),
  user_agent  VARCHAR(255),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_admin
    FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATA AWAL (Seed)
-- ============================================================

-- Admin default (password: Admin@1234 — ganti segera!)
INSERT INTO admin (username, password, nama_lengkap, email) VALUES
('superadmin',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Super Administrator',
 'admin@kampus.ac.id');

-- Program Studi
INSERT INTO program_studi (kode, nama, fakultas) VALUES
('TIF', 'Teknik Informatika', 'Fakultas Teknik'),
('SI',  'Sistem Informasi',   'Fakultas Teknik'),
('TK',  'Teknik Komputer',    'Fakultas Teknik'),
('MTI', 'Magister Teknik Informatika', 'Pascasarjana');

-- Kategori Topik
INSERT INTO kategori (nama, slug, deskripsi, warna_hex) VALUES
('Kecerdasan Buatan',    'ai',         'Machine Learning, Deep Learning, NLP, Computer Vision', '#8B5CF6'),
('Pengembangan Web',     'web',        'Frontend, Backend, CMS, E-Commerce',                    '#3B82F6'),
('Jaringan Komputer',    'jaringan',   'Network Security, IoT, Wireless, Protokol',              '#10B981'),
('Basis Data',           'database',   'SQL, NoSQL, Data Warehouse, ETL',                        '#F59E0B'),
('Rekayasa Perangkat Lunak', 'rpl',    'Agile, Testing, DevOps, Software Architecture',          '#EF4444'),
('Sistem Keamanan',      'security',   'Kriptografi, Forensik Digital, Penetration Testing',     '#EC4899'),
('Mobile & Embedded',    'mobile',     'Android, iOS, Raspberry Pi, Arduino',                    '#06B6D4'),
('Data Science',         'data-science','Statistik, Visualisasi, Big Data, Business Intelligence','#84CC16');

-- Dosen
INSERT INTO dosen (nip, nama, email) VALUES
('197801012005011001', 'Dr. Budi Santoso, M.Kom',       'budi.santoso@kampus.ac.id'),
('198205152010012002', 'Siti Rahayu, S.T., M.T.',       'siti.rahayu@kampus.ac.id'),
('197503222003121003', 'Prof. Ahmad Fauzi, Ph.D.',       'ahmad.fauzi@kampus.ac.id'),
('198811102015041004', 'Dewi Kurniawati, M.Cs.',         'dewi.kurniawati@kampus.ac.id'),
('196912301997021005', 'Ir. Hendra Wijaya, M.T.',        'hendra.wijaya@kampus.ac.id');

-- Contoh Data Skripsi
INSERT INTO skripsi
  (nim, nama_penulis, judul, abstrak, kata_kunci, tahun_lulus,
   program_studi_id, kategori_id, dosen_pembimbing1_id, dosen_pembimbing2_id,
   file_pdf, ukuran_file_kb, status, view_count)
VALUES
('19101001', 'Andi Prasetyo',
 'Implementasi Convolutional Neural Network untuk Deteksi Penyakit Tanaman Padi',
 'Penelitian ini membangun sistem deteksi penyakit pada daun padi menggunakan CNN dengan akurasi 94.7%.',
 'CNN, deep learning, penyakit tanaman, citra digital', 2023, 1, 1, 1, 2,
 NULL, NULL, 'aktif', 120),

('19101002', 'Bela Maharani',
 'Sistem Rekomendasi Produk E-Commerce Berbasis Collaborative Filtering',
 'Membangun mesin rekomendasi produk menggunakan algoritma collaborative filtering dengan Precision@10 sebesar 87%.',
 'collaborative filtering, rekomendasi, e-commerce, machine learning', 2023, 2, 1, 3, 1,
 NULL, NULL, 'aktif', 95),

('20101010', 'Cahyo Nugroho',
 'Pengembangan Aplikasi Web Progressive (PWA) untuk Manajemen Inventaris UMKM',
 'Membangun Progressive Web App manajemen inventaris yang dapat bekerja offline untuk skala UMKM.',
 'PWA, service worker, offline-first, inventaris, UMKM', 2024, 1, 2, 2, 4,
 NULL, NULL, 'aktif', 78),

('20101020', 'Dina Fitriani',
 'Analisis Kerentanan Keamanan Aplikasi Mobile Banking dengan Metode OWASP',
 'Melakukan penetration testing pada 5 aplikasi mobile banking lokal menggunakan framework OWASP Mobile Top 10.',
 'keamanan, mobile banking, OWASP, penetration testing', 2024, 2, 6, 3, 5,
 NULL, NULL, 'aktif', 210);
