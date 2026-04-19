<?php
// admin/index.php — Dashboard Admin
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$stats = getStatistikDashboard();
$db    = getDB();
// 10 skripsi terbaru
$latest = $db->query(
    "SELECT s.id, s.nim, s.nama_penulis, s.judul, s.tahun_lulus, p.kode AS kode_prodi
     FROM skripsi s JOIN program_studi p ON p.id = s.program_studi_id
     WHERE s.status='aktif' ORDER BY s.created_at DESC LIMIT 10"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Admin — <?= APP_NAME ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="admin-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header">
      <h1>Dashboard</h1>
      <p>Selamat datang, <strong><?= e($_SESSION['admin_nama']) ?></strong></p>
    </div>

    <div class="stats-grid">
      <div class="stat-card"><div class="number"><?= number_format($stats['total']) ?></div><div class="label">Total Skripsi</div></div>
      <div class="stat-card"><div class="number"><?= count($stats['topKategori']) ?></div><div class="label">Kategori Aktif</div></div>
      <div class="stat-card"><div class="number"><?= count($stats['perTahun']) ?></div><div class="label">Tahun Tercakup</div></div>
      <div class="stat-card"><div class="number"><?= array_sum(array_column($stats['terpopuler'], 'view_count')) ?></div><div class="label">Total Views</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
      <div class="chart-card">
        <h3>📈 Skripsi per Tahun</h3>
        <canvas id="cTahun"></canvas>
      </div>
      <div class="chart-card">
        <h3>🏷️ Distribusi Topik</h3>
        <canvas id="cTopik"></canvas>
      </div>
    </div>

    <div class="chart-card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
        <h3 style="margin:0">📋 Skripsi Terbaru</h3>
        <a href="skripsi.php" class="btn btn-outline btn-sm">Lihat Semua</a>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>NIM</th><th>Nama Penulis</th><th>Judul</th><th>Prodi</th><th>Tahun</th><th>Aksi</th></tr></thead>
          <tbody>
          <?php foreach ($latest as $s): ?>
          <tr>
            <td><?= e($s['nim']) ?></td>
            <td><?= e($s['nama_penulis']) ?></td>
            <td class="truncate"><?= e($s['judul']) ?></td>
            <td><?= e($s['kode_prodi']) ?></td>
            <td><?= $s['tahun_lulus'] ?></td>
            <td><a href="skripsi-edit.php?id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">Edit</a></td>
          </tr>
          <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<script>
const pT = <?= json_encode(array_column($stats['perTahun'],'tahun')) ?>;
const jT = <?= json_encode(array_column($stats['perTahun'],'jumlah')) ?>;
const pK = <?= json_encode(array_column($stats['topKategori'],'nama')) ?>;
const jK = <?= json_encode(array_column($stats['topKategori'],'jumlah')) ?>;
const wK = <?= json_encode(array_column($stats['topKategori'],'warna_hex')) ?>;

new Chart(document.getElementById('cTahun'),{type:'bar',data:{labels:pT,datasets:[{label:'Skripsi',data:jT,backgroundColor:'#3B82F6',borderRadius:6}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});
new Chart(document.getElementById('cTopik'),{type:'doughnut',data:{labels:pK,datasets:[{data:jK,backgroundColor:wK,borderWidth:2}]},options:{responsive:true,plugins:{legend:{position:'bottom',labels:{font:{size:11}}}}}});
</script>
</body>
</html>
