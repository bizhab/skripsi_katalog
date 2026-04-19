<?php
// statistik.php — FR06: Dashboard Statistik (publik)
require_once __DIR__ . '/includes/functions.php';
$stats = getStatistikDashboard();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Statistik — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<nav class="navbar">
  <a class="navbar-brand" href="index.php">📚 <?= APP_NAME ?></a>
  <div class="nav-links">
    <a href="index.php">← Kembali</a>
  </div>
</nav>

<div class="main-content">
  <h2 style="font-size:1.5rem;font-weight:800;margin-bottom:1.5rem">📊 Statistik Repositori</h2>

  <!-- KPI -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="number"><?= number_format($stats['total']) ?></div>
      <div class="label">Total Skripsi Terdaftar</div>
    </div>
    <div class="stat-card">
      <div class="number"><?= count($stats['perTahun']) ?></div>
      <div class="label">Tahun Akademik Tercakup</div>
    </div>
    <div class="stat-card">
      <div class="number"><?= count($stats['topKategori']) ?></div>
      <div class="label">Kategori Topik Aktif</div>
    </div>
    <div class="stat-card">
      <div class="number"><?= $stats['terpopuler'][0]['view_count'] ?? 0 ?></div>
      <div class="label">Views Skripsi Terpopuler</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:3fr 2fr;gap:1.5rem;flex-wrap:wrap">
    <!-- Grafik Per Tahun -->
    <div class="chart-card">
      <h3>📈 Jumlah Skripsi per Tahun</h3>
      <canvas id="chartTahun"></canvas>
    </div>

    <!-- Grafik Topik Populer -->
    <div class="chart-card">
      <h3>🏷️ Distribusi Topik</h3>
      <canvas id="chartTopik"></canvas>
    </div>
  </div>

  <!-- Top 5 Terpopuler -->
  <div class="chart-card" style="margin-top:0">
    <h3>🔥 Skripsi Paling Banyak Dilihat</h3>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Judul</th><th>Penulis</th><th>Tahun</th><th>Views</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($stats['terpopuler'] as $i => $s): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td class="truncate"><?= e($s['judul']) ?></td>
            <td><?= e($s['nama_penulis']) ?></td>
            <td><?= $s['tahun_lulus'] ?></td>
            <td><strong><?= number_format($s['view_count']) ?></strong></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
// Data dari PHP
const tahunData = <?= json_encode(array_column($stats['perTahun'], 'tahun')) ?>;
const tahunJumlah = <?= json_encode(array_column($stats['perTahun'], 'jumlah')) ?>;
const topikNama = <?= json_encode(array_column($stats['topKategori'], 'nama')) ?>;
const topikJumlah = <?= json_encode(array_column($stats['topKategori'], 'jumlah')) ?>;
const topikWarna = <?= json_encode(array_column($stats['topKategori'], 'warna_hex')) ?>;

// Chart: Per Tahun
new Chart(document.getElementById('chartTahun'), {
  type: 'bar',
  data: {
    labels: tahunData,
    datasets: [{
      label: 'Jumlah Skripsi',
      data: tahunJumlah,
      backgroundColor: '#3B82F6',
      borderRadius: 6,
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

// Chart: Topik
new Chart(document.getElementById('chartTopik'), {
  type: 'doughnut',
  data: {
    labels: topikNama,
    datasets: [{ data: topikJumlah, backgroundColor: topikWarna, borderWidth: 2 }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
});
</script>
</body>
</html>
