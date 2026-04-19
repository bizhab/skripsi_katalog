<?php
// admin/skripsi-tambah.php — FR03: Tambah Skripsi + Upload PDF
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$errors  = [];
$success = false;
$data    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nim'                  => sanitizeStr($_POST['nim'] ?? ''),
        'nama_penulis'         => sanitizeStr($_POST['nama_penulis'] ?? ''),
        'judul'                => sanitizeStr($_POST['judul'] ?? ''),
        'abstrak'              => sanitizeStr($_POST['abstrak'] ?? ''),
        'kata_kunci'           => sanitizeStr($_POST['kata_kunci'] ?? ''),
        'tahun_lulus'          => (int)($_POST['tahun_lulus'] ?? 0),
        'program_studi_id'     => (int)($_POST['program_studi_id'] ?? 0),
        'kategori_id'          => (int)($_POST['kategori_id'] ?? 0),
        'dosen_pembimbing1_id' => (int)($_POST['dosen_pembimbing1_id'] ?? 0),
        'dosen_pembimbing2_id' => (int)($_POST['dosen_pembimbing2_id'] ?? 0),
    ];

    // Validasi
    if (!$data['nim'])               $errors[] = 'NIM wajib diisi.';
    if (!$data['nama_penulis'])      $errors[] = 'Nama penulis wajib diisi.';
    if (!$data['judul'])             $errors[] = 'Judul wajib diisi.';
    if (!$data['tahun_lulus'])       $errors[] = 'Tahun lulus wajib diisi.';
    if (!$data['program_studi_id'])  $errors[] = 'Program studi wajib dipilih.';

    // Upload PDF
    if (!empty($_FILES['file_pdf']['name'])) {
        try {
            $upload = uploadPdf($_FILES['file_pdf']);
            $data['file_pdf']       = $upload['file_pdf'];
            $data['ukuran_file_kb'] = $upload['ukuran_file_kb'];
        } catch (RuntimeException $e) {
            $errors[] = 'Upload PDF: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            tambahSkripsi($data);
            header('Location: skripsi.php?msg=simpan');
            exit;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$prodis    = getAllProdi();
$kategoris = getAllKategori();
$dosens    = getAllDosen();
$tahunNow  = (int)date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tambah Skripsi — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header">
      <h1>Tambah Skripsi</h1>
      <p><a href="skripsi.php">← Kembali ke daftar</a></p>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-error">
      <strong>Terjadi kesalahan:</strong><br>
      <?php foreach ($errors as $err): ?>&bull; <?= e($err) ?><br><?php endforeach ?>
    </div>
    <?php endif ?>

    <div class="form-card">
      <form method="POST" enctype="multipart/form-data">

        <div class="form-row">
          <div class="form-group">
            <label>NIM *</label>
            <input type="text" name="nim" required value="<?= e($data['nim'] ?? '') ?>" placeholder="Contoh: 20101001">
          </div>
          <div class="form-group">
            <label>Nama Penulis *</label>
            <input type="text" name="nama_penulis" required value="<?= e($data['nama_penulis'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label>Judul Skripsi *</label>
          <input type="text" name="judul" required value="<?= e($data['judul'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Abstrak</label>
          <textarea name="abstrak"><?= e($data['abstrak'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label>Kata Kunci</label>
          <input type="text" name="kata_kunci" value="<?= e($data['kata_kunci'] ?? '') ?>" placeholder="Pisahkan dengan koma. Contoh: AI, deep learning, CNN">
          <div class="form-hint">Digunakan untuk pencarian. Pisahkan tiap kata kunci dengan koma.</div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Tahun Lulus *</label>
            <select name="tahun_lulus" required>
              <option value="">-- Pilih Tahun --</option>
              <?php for ($y = $tahunNow; $y >= $tahunNow - 15; $y--): ?>
              <option value="<?= $y ?>" <?= ($data['tahun_lulus'] ?? 0) == $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor ?>
            </select>
          </div>
          <div class="form-group">
            <label>Program Studi *</label>
            <select name="program_studi_id" required>
              <option value="">-- Pilih Prodi --</option>
              <?php foreach ($prodis as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($data['program_studi_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>>
                <?= e($p['nama']) ?>
              </option>
              <?php endforeach ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Kategori Topik</label>
            <select name="kategori_id">
              <option value="0">-- Tidak Ada --</option>
              <?php foreach ($kategoris as $k): ?>
              <option value="<?= $k['id'] ?>" <?= ($data['kategori_id'] ?? 0) == $k['id'] ? 'selected' : '' ?>>
                <?= e($k['nama']) ?>
              </option>
              <?php endforeach ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Dosen Pembimbing I</label>
            <select name="dosen_pembimbing1_id">
              <option value="0">-- Tidak Ada --</option>
              <?php foreach ($dosens as $d): ?>
              <option value="<?= $d['id'] ?>" <?= ($data['dosen_pembimbing1_id'] ?? 0) == $d['id'] ? 'selected' : '' ?>>
                <?= e($d['nama']) ?>
              </option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="form-group">
            <label>Dosen Pembimbing II</label>
            <select name="dosen_pembimbing2_id">
              <option value="0">-- Tidak Ada --</option>
              <?php foreach ($dosens as $d): ?>
              <option value="<?= $d['id'] ?>" <?= ($data['dosen_pembimbing2_id'] ?? 0) == $d['id'] ? 'selected' : '' ?>>
                <?= e($d['nama']) ?>
              </option>
              <?php endforeach ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>File PDF Abstrak</label>
          <input type="file" name="file_pdf" accept="application/pdf">
          <div class="form-hint">Format: PDF. Maks <?= MAX_FILE_SIZE_KB / 1024 ?> MB. File disimpan aman di server.</div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">💾 Simpan Skripsi</button>
          <a href="skripsi.php" class="btn btn-outline">Batal</a>
        </div>
      </form>
    </div>
  </main>
</div>
</body>
</html>
