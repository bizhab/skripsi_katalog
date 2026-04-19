# 📚 Katalog Skripsi & Tugas Akhir Digital

Implementasi PHP berdasarkan SRS (Software Requirements Specification) proyek katalog skripsi digital.

---

## 🗂️ Struktur Folder

```
katalog-skripsi/
├── index.php              ← Halaman utama + pencarian (FR01, FR02)
├── detail.php             ← Detail skripsi
├── viewer.php             ← PDF Viewer aman (FR04, NFR02)
├── statistik.php          ← Dashboard statistik publik (FR06)
├── database.sql           ← Script SQL lengkap
├── .htaccess              ← Keamanan server (NFR02)
│
├── config/
│   ├── app.php            ← Konstanta aplikasi
│   └── database.php       ← Koneksi PDO
│
├── includes/
│   ├── auth.php           ← Autentikasi + session (FR07)
│   └── functions.php      ← Semua fungsi CRUD & helper
│
├── assets/
│   └── css/style.css      ← Stylesheet responsif (NFR03)
│
├── admin/
│   ├── login.php          ← Halaman login admin
│   ├── logout.php
│   ├── index.php          ← Dashboard admin (FR06)
│   ├── skripsi.php        ← Daftar + hapus skripsi (FR03)
│   ├── skripsi-tambah.php ← Tambah skripsi + upload PDF
│   ├── skripsi-edit.php   ← Edit skripsi
│   ├── kategori.php       ← Manajemen kategori (FR05)
│   ├── prodi.php          ← Manajemen program studi
│   ├── dosen.php          ← Manajemen dosen pembimbing
│   ├── log.php            ← Log aktivitas admin
│   └── partials/
│       └── sidebar.php
│
└── uploads/
    └── pdf/               ← Folder penyimpanan PDF (dilindungi .htaccess)
```

---

## ⚡ Cara Instalasi

### 1. Persyaratan Server
- PHP >= 8.0
- MySQL >= 5.7 / MariaDB >= 10.3
- Apache dengan mod_rewrite aktif
- Extension PHP: `pdo_mysql`, `fileinfo`, `mbstring`

### 2. Import Database
```bash
mysql -u root -p < database.sql
```
Atau import via phpMyAdmin.

### 3. Konfigurasi
Edit `config/app.php`:
```php
define('APP_URL', 'http://localhost/katalog-skripsi'); // Sesuaikan URL
```

Edit `config/database.php`:
```php
define('DB_USER', 'root');   // Username database
define('DB_PASS', '');       // Password database
```

### 4. Izin Folder Upload
```bash
chmod 755 uploads/
chmod 755 uploads/pdf/
```

### 5. Ganti Password Admin
Password default: `Admin@1234`

Untuk menggantinya, jalankan query berikut:
```sql
UPDATE admin
SET password = '$2y$12$HASH_BARU'
WHERE username = 'superadmin';
```

Generate hash baru dengan PHP:
```php
echo password_hash('PasswordBaru123!', PASSWORD_BCRYPT, ['cost' => 12]);
```

---

## 🔐 Akun Default Admin

| Field    | Value           |
|----------|-----------------|
| Username | `superadmin`    |
| Password | `Admin@1234`    |
| URL      | `/admin/login.php` |

> **⚠️ Ganti password segera setelah instalasi!**

---

## ✅ Fitur yang Diimplementasikan

| ID   | Fitur                   | Status |
|------|-------------------------|--------|
| FR01 | Pencarian Global        | ✅     |
| FR02 | Filter Lanjutan         | ✅     |
| FR03 | Manajemen Data (CRUD)   | ✅     |
| FR04 | PDF Viewer Interaktif   | ✅     |
| FR05 | Manajemen Kategori      | ✅     |
| FR06 | Dashboard Statistik     | ✅     |
| FR07 | Sistem Autentikasi      | ✅     |
| NFR01| Performance (Indexing)  | ✅     |
| NFR02| Security (Bcrypt + PDF) | ✅     |
| NFR03| Usability (Responsive)  | ✅     |
| NFR05| Backup (struktur siap)  | ✅     |

---

## 🛡️ Catatan Keamanan

- Password admin dienkripsi dengan **Bcrypt cost 12** (NFR02)
- File PDF **tidak dapat diakses via URL langsung** — hanya melalui `viewer.php` (NFR02)
- Semua input divalidasi dan di-sanitasi dengan `htmlspecialchars` + prepared statements
- Session timeout 1 jam dengan rolling timer
- Audit log mencatat semua aksi admin (IP, waktu, aksi)
- Upload PDF dibatasi 10MB dan hanya tipe `application/pdf`
- Deteksi duplikasi NIM dan Judul sebelum simpan (Risiko 2)
- Full-text index pada kolom judul, abstrak, kata kunci (Risiko 3)

---

## 🗄️ Backup Database (NFR05)

Tambahkan cronjob mingguan di server:
```bash
# Buka crontab
crontab -e

# Tambahkan baris ini (backup setiap Minggu pukul 02:00)
0 2 * * 0 mysqldump -u root -pPASSWORD katalog_skripsi | gzip > /backup/katalog_$(date +\%Y\%m\%d).sql.gz
```
