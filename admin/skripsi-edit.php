<?php
// admin/skripsi-edit.php — FR03: Edit Skripsi
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: skripsi.php'); exit; }

$db      = getDB();
$skripsi = $db->prepare("SELECT * FROM skripsi WHERE id = ? LIMIT 1");
$skripsi->execute([$id]);
$skripsi = $skripsi->fetch();
if (!$skripsi) { http_response_code(404); die('Data tidak ditemukan.'); }

$errors = [];

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

    if (!$data['nim'])              $errors[] = 'NIM wajib diisi.';
    if (!$data['nama_penulis'])     $errors[] = 'Nama penulis wajib diisi.';
    if (!$data['judul'])            $errors[] = 'Judul wajib diisi.';
    if (!$data['program_studi_id']) $errors[] = 'Program studi wajib dipilih.';

    // Upload PDF baru (opsional)
    if (!empty($_FILES['file_pdf']['name'])) {
        try {
            $upload = uploadPdf($_FILES['file_pdf']);
            $data['file_pdf']       = $upload['file_pdf'];
            $data['ukuran_file_kb'] = $upload['ukuran_file_kb'];
            // Hapus file lama
            if ($skripsi['file_pdf'] && file_exists(UPLOAD_PATH . $skripsi['file_pdf'])) {
                unlink(UPLOAD_PATH . $skripsi['file_pdf']);
            }
        } catch (RuntimeException $e) {
            $errors[] = 'Upload PDF: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            editSkripsi($id, $data);
            header('Location: skripsi.php?msg=edit');
            exit;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
    // Re-populate dari POST jika error
    $skripsi = array_merge($skripsi, $data);
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
<title>Edit Skripsi — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header">
      <h1>Edit Skripsi</h1>
      <p><a href="skripsi.php">← Kembali ke daftar</a></p>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $err): ?>&bull; <?= e($err) ?><br><?php endforeach ?>
    </div>
    <?php endif ?>

    <div class="form-card">
      <form method="POST" enctype="multipart/form-data">

        <div class="form-row">
          <div class="form-group">
            <label>NIM *</label>
            <input type="text" name="nim" required value="<?= e($skripsi['nim']) ?>">
          </div>
          <div class="form-group">
            <label>Nama Penulis *</label>
            <input type="text" name="nama_penulis" required value="<?= e($skripsi['nama_penulis']) ?>">
          </div>
        </div>

        <div class="form-group">
          <label>Judul *</label>
          <input type="text" name="judul" required value="<?= e($skripsi['judul']) ?>">
        </div>

        <div class="form-group">
          <label>Abstrak</label>
          <textarea name="abstrak"><?= e($skripsi['abstrak']) ?></textarea>
        </div>

        <div class="form-group">
          <label>Kata Kunci</label>
          <input type="text" name="kata_kunci" value="<?= e($skripsi['kata_kunci']) ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Tahun Lulus *</label>
            <select name="tahun_lulus" required>
              <?php for ($y = $tahunNow; $y >= $tahunNow - 15; $y--): ?>
              <option value="<?= $y ?>" <?= $skripsi['tahun_lulus'] == $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor ?>
            </select>
          </div>
          <div class="form-group">
            <label>Program Studi *</label>
            <select name="program_studi_id" required>
              <?php foreach ($prodis as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $skripsi['program_studi_id'] == $p['id'] ? 'selected' : '' ?>>
                <?= e($p['nama']) ?>
              </option>
              <?php endforeach ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Kategori</label>
            <select name="kategori_id">
              <option value="0">-- Tidak Ada --</option>
              <?php foreach ($kategoris as $k): ?>
              <option value="<?= $k['id'] ?>" <?= $skripsi['kategori_id'] == $k['id'] ? 'selected' : '' ?>>
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
              <option value="<?= $d['id'] ?>" <?= $skripsi['dosen_pembimbing1_id'] == $d['id'] ? 'selected' : '' ?>>
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
              <option value="<?= $d['id'] ?>" <?= $skripsi['dosen_pembimbing2_id'] == $d['id'] ? 'selected' : '' ?>>
                <?= e($d['nama']) ?>
              </option>
              <?php endforeach ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Ganti File PDF</label>
          <?php if ($skripsi['file_pdf']): ?>
          <div class="alert alert-info" style="margin-bottom:.6rem;font-size:.82rem">
            📄 File PDF saat ini tersedia. Upload baru akan menggantikan file lama.
          </div>
          <?php endif ?>
          <input type="file" name="file_pdf" accept="application/pdf">
          <div class="form-hint">Kosongkan jika tidak ingin mengganti file PDF.</div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">💾 Perbarui Skripsi</button>
          <a href="skripsi.php" class="btn btn-outline">Batal</a>
        </div>
      </form>
    </div>
  </main>
</div>
</body>
</html>
