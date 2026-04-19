<?php
// admin/skripsi.php — Daftar + Hapus Skripsi (FR03 CRUD)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// Handle hapus (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $hapusId = (int)$_POST['hapus_id'];
    hapusSkripsi($hapusId);
    header('Location: skripsi.php?msg=hapus');
    exit;
}

$msg = $_GET['msg'] ?? '';

// Pagination + filter admin
$params = [
    'q'    => sanitizeStr($_GET['q'] ?? ''),
    'prodi'=> (int)($_GET['prodi'] ?? 0),
    'page' => max(1, (int)($_GET['page'] ?? 1)),
];
$result  = searchSkripsi($params);
$prodis  = getAllProdi();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Data Skripsi — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header">
      <h1>Data Skripsi</h1>
      <p>Kelola seluruh data skripsi &amp; tugas akhir</p>
    </div>

    <?php if ($msg === 'simpan'): ?><div class="alert alert-success">✅ Skripsi berhasil disimpan.</div><?php endif ?>
    <?php if ($msg === 'hapus'):  ?><div class="alert alert-success">🗑️ Skripsi berhasil dihapus.</div><?php endif ?>
    <?php if ($msg === 'edit'):   ?><div class="alert alert-success">✏️ Skripsi berhasil diperbarui.</div><?php endif ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;flex-wrap:wrap;gap:.8rem">
      <form method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap">
        <input type="text" name="q" placeholder="Cari skripsi…" value="<?= e($params['q']) ?>" style="border:1px solid var(--border);border-radius:8px;padding:.45rem .9rem;font-size:.88rem">
        <select name="prodi" style="border:1px solid var(--border);border-radius:8px;padding:.45rem .9rem;font-size:.88rem">
          <option value="0">Semua Prodi</option>
          <?php foreach ($prodis as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $params['prodi']==$p['id']?'selected':'' ?>><?= e($p['nama']) ?></option>
          <?php endforeach ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="skripsi.php" class="btn btn-outline btn-sm">Reset</a>
      </form>
      <a href="skripsi-tambah.php" class="btn btn-success">➕ Tambah Skripsi</a>
    </div>

    <div class="chart-card" style="padding:0;overflow:hidden">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>NIM</th><th>Nama Penulis</th><th>Judul</th><th>Prodi</th><th>Tahun</th><th>Views</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($result['data'])): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted)">Tidak ada data</td></tr>
          <?php else: ?>
          <?php foreach ($result['data'] as $s): ?>
          <tr>
            <td><?= e($s['nim']) ?></td>
            <td><?= e($s['nama_penulis']) ?></td>
            <td class="truncate" title="<?= e($s['judul']) ?>"><?= e($s['judul']) ?></td>
            <td><?= e($s['prodi']) ?></td>
            <td><?= $s['tahun_lulus'] ?></td>
            <td><?= number_format($s['view_count']) ?></td>
            <td style="white-space:nowrap">
              <a href="skripsi-edit.php?id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">✏️ Edit</a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Hapus skripsi ini?')">
                <input type="hidden" name="hapus_id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
              </form>
            </td>
          </tr>
          <?php endforeach ?>
          <?php endif ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php
    $pp = ['q' => $params['q'], 'prodi' => $params['prodi']];
    echo pagination($result['total'], $result['page'], $result['total_pages'], $pp);
    ?>
  </main>
</div>
</body>
</html>
