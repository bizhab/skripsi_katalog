<?php
// detail.php — Detail Skripsi + FR04 PDF Viewer Interaktif
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$skripsi = getSkripsiById($id);
if (!$skripsi) { http_response_code(404); die('Skripsi tidak ditemukan.'); }

incrementViewCount($id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($skripsi['judul']) ?> — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
  <a class="navbar-brand" href="index.php">📚 <?= APP_NAME ?></a>
  <div class="nav-links">
    <a href="index.php">← Kembali ke Pencarian</a>
  </div>
</nav>

<div class="detail-container">
  <div class="detail-card">
    <div class="detail-header">
      <?php if ($skripsi['kategori']): ?>
      <span class="card-badge" style="background:<?= e($skripsi['warna_hex']) ?>;margin-bottom:.8rem;display:inline-block">
        <?= e($skripsi['kategori']) ?>
      </span>
      <?php endif ?>
      <h1><?= e($skripsi['judul']) ?></h1>
      <div style="display:flex;gap:1.2rem;flex-wrap:wrap;margin-top:.8rem;font-size:.88rem;opacity:.85">
        <span>✍️ <?= e($skripsi['nama_penulis']) ?></span>
        <span>🆔 NIM: <?= e($skripsi['nim']) ?></span>
        <span>📅 <?= $skripsi['tahun_lulus'] ?></span>
        <span>👁️ <?= number_format($skripsi['view_count']) ?> kali dilihat</span>
      </div>
    </div>

    <div class="detail-body">
      <div class="detail-grid">
        <div class="detail-field">
          <label>Program Studi</label>
          <p><?= e($skripsi['prodi'] . ' (' . $skripsi['kode_prodi'] . ')') ?></p>
        </div>
        <?php if ($skripsi['pembimbing1']): ?>
        <div class="detail-field">
          <label>Pembimbing I</label>
          <p><?= e($skripsi['pembimbing1']) ?></p>
        </div>
        <?php endif ?>
        <?php if ($skripsi['pembimbing2']): ?>
        <div class="detail-field">
          <label>Pembimbing II</label>
          <p><?= e($skripsi['pembimbing2']) ?></p>
        </div>
        <?php endif ?>
        <?php if ($skripsi['kata_kunci']): ?>
        <div class="detail-field">
          <label>Kata Kunci</label>
          <p><?= e($skripsi['kata_kunci']) ?></p>
        </div>
        <?php endif ?>
      </div>

      <?php if ($skripsi['abstrak']): ?>
      <h3 style="font-size:.95rem;margin-bottom:.5rem;font-weight:700">Abstrak</h3>
      <div class="abstrak-box"><?= nl2br(e($skripsi['abstrak'])) ?></div>
      <?php endif ?>

      <!-- FR04: PDF Viewer Interaktif -->
      <?php if ($skripsi['file_pdf']): ?>
      <div style="margin-top:2rem">
        <h3 style="font-size:.95rem;font-weight:700;margin-bottom:.8rem">📄 Preview Abstrak PDF</h3>
        <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:.8rem">
          Menampilkan preview PDF langsung di browser tanpa perlu mengunduh.
        </p>
        <iframe class="pdf-viewer"
                src="viewer.php?id=<?= $id ?>"
                title="Preview PDF Skripsi">
        </iframe>
        <p style="font-size:.78rem;color:var(--text-muted);margin-top:.5rem">
          Ukuran file: <?= number_format($skripsi['ukuran_file_kb']) ?> KB
        </p>
      </div>
      <?php else: ?>
      <div class="alert alert-info" style="margin-top:1.5rem">
        📌 File PDF belum tersedia untuk skripsi ini.
      </div>
      <?php endif ?>
    </div>
  </div>
</div>

</body>
</html>
