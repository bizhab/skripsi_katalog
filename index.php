<?php
// index.php — FR01 Pencarian Global + FR02 Filter Lanjutan
require_once __DIR__ . '/includes/functions.php';

$params = [
    'q'       => sanitizeStr($_GET['q'] ?? ''),
    'tahun'   => (int)($_GET['tahun']   ?? 0),
    'prodi'   => (int)($_GET['prodi']   ?? 0),
    'dosen'   => (int)($_GET['dosen']   ?? 0),
    'kategori'=> (int)($_GET['kategori']?? 0),
    'page'    => max(1, (int)($_GET['page'] ?? 1)),
];

$result   = searchSkripsi($params);
$prodis   = getAllProdi();
$dosens   = getAllDosen();
$kategoris= getAllKategori();
$tahunList= getTahunList();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
  <a class="navbar-brand" href="index.php">📚 <?= APP_NAME ?></a>
  <div class="nav-links">
    <a href="statistik.php">📊 Statistik</a>
    <a href="admin/login.php">🔑 Admin</a>
  </div>
</nav>

<!-- Hero + Search (FR01) -->
<section class="hero">
  <h1>Katalog Skripsi &amp; Tugas Akhir</h1>
  <p>Temukan referensi tugas akhir dari angkatan sebelumnya</p>
  <form method="GET" action="index.php">
    <div class="search-box">
      <input type="text" name="q" placeholder="Cari judul, penulis, NIM, atau kata kunci…"
             value="<?= e($params['q']) ?>" autocomplete="off">
      <button type="submit">🔍 Cari</button>
    </div>
  </form>
</section>

<!-- Filter Lanjutan (FR02) -->
<div class="filter-bar">
  <form method="GET" action="index.php" style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:center;width:100%">
    <input type="hidden" name="q" value="<?= e($params['q']) ?>">

    <select name="tahun">
      <option value="0">Semua Tahun</option>
      <?php foreach ($tahunList as $t): ?>
      <option value="<?= $t ?>" <?= $params['tahun'] == $t ? 'selected' : '' ?>><?= $t ?></option>
      <?php endforeach ?>
    </select>

    <select name="prodi">
      <option value="0">Semua Prodi</option>
      <?php foreach ($prodis as $p): ?>
      <option value="<?= $p['id'] ?>" <?= $params['prodi'] == $p['id'] ? 'selected' : '' ?>>
        <?= e($p['nama']) ?>
      </option>
      <?php endforeach ?>
    </select>

    <select name="dosen">
      <option value="0">Semua Dosen Pembimbing</option>
      <?php foreach ($dosens as $d): ?>
      <option value="<?= $d['id'] ?>" <?= $params['dosen'] == $d['id'] ? 'selected' : '' ?>>
        <?= e($d['nama']) ?>
      </option>
      <?php endforeach ?>
    </select>

    <select name="kategori">
      <option value="0">Semua Topik</option>
      <?php foreach ($kategoris as $k): ?>
      <option value="<?= $k['id'] ?>" <?= $params['kategori'] == $k['id'] ? 'selected' : '' ?>>
        <?= e($k['nama']) ?>
      </option>
      <?php endforeach ?>
    </select>

    <button type="submit" class="btn-filter">Terapkan Filter</button>
    <a href="index.php" class="btn-filter btn-reset">Reset</a>
  </form>
</div>

<!-- Hasil -->
<div class="main-content">
  <div class="results-header">
    <p class="results-count">
      Menampilkan <strong><?= number_format($result['total']) ?></strong> skripsi
      <?= $params['q'] ? ' untuk "<strong>' . e($params['q']) . '</strong>"' : '' ?>
    </p>
    <small style="color:var(--text-muted)">Halaman <?= $result['page'] ?> / <?= max(1,$result['total_pages']) ?></small>
  </div>

  <?php if (empty($result['data'])): ?>
  <div class="empty-state">
    <div class="icon">🔍</div>
    <h3>Tidak ada hasil ditemukan</h3>
    <p>Coba kata kunci lain atau hapus beberapa filter.</p>
  </div>
  <?php else: ?>
  <div class="skripsi-grid">
    <?php foreach ($result['data'] as $s): ?>
    <div class="card">
      <?php if ($s['kategori']): ?>
      <span class="card-badge" style="background:<?= e($s['warna_hex']) ?>"><?= e($s['kategori']) ?></span>
      <?php endif ?>
      <div class="card-title">
        <a href="detail.php?id=<?= $s['id'] ?>"><?= e($s['judul']) ?></a>
      </div>
      <div class="card-author">✍️ <?= e($s['nama_penulis']) ?> &mdash; NIM <?= e($s['nim']) ?></div>
      <div class="card-meta">
        <span>📅 <?= $s['tahun_lulus'] ?></span>
        <span>🎓 <?= e($s['prodi']) ?></span>
        <?php if ($s['pembimbing1']): ?>
        <span>👨‍🏫 <?= e($s['pembimbing1']) ?></span>
        <?php endif ?>
        <span>👁️ <?= number_format($s['view_count']) ?></span>
      </div>
      <?php if ($s['kata_kunci']): ?>
      <div style="font-size:.76rem;color:var(--text-muted)">🏷️ <?= e($s['kata_kunci']) ?></div>
      <?php endif ?>
      <div class="card-footer">
        <a href="detail.php?id=<?= $s['id'] ?>" class="btn btn-primary btn-sm">Lihat Detail</a>
        <?php if ($s['file_pdf']): ?>
        <a href="viewer.php?id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">📄 Preview PDF</a>
        <?php endif ?>
      </div>
    </div>
    <?php endforeach ?>
  </div>

  <?php
  $paginationParams = array_filter($params, fn($v) => $v !== '' && $v !== 0);
  echo pagination($result['total'], $result['page'], $result['total_pages'], $paginationParams);
  ?>
  <?php endif ?>
</div>

</body>
</html>
