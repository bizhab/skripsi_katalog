<?php
// admin/log.php — Log Aktivitas Admin
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db   = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 30;
$off  = ($page - 1) * $per;

$total = (int)$db->query("SELECT COUNT(*) FROM log_aktivitas")->fetchColumn();
$pages = (int)ceil($total / $per);

$logs = $db->query(
    "SELECT l.*, a.username
     FROM log_aktivitas l
     LEFT JOIN admin a ON a.id = l.admin_id
     ORDER BY l.created_at DESC
     LIMIT $per OFFSET $off"
)->fetchAll();

$aksiIcon = [
    'login'  => '🔑',
    'logout' => '🚪',
    'tambah' => '➕',
    'edit'   => '✏️',
    'hapus'  => '🗑️',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Log Aktivitas — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header">
      <h1>Log Aktivitas</h1>
      <p>Rekam jejak semua aksi admin — total <?= number_format($total) ?> entri</p>
    </div>

    <div class="chart-card" style="padding:0;overflow:hidden">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>Waktu</th><th>Admin</th><th>Aksi</th><th>Tabel</th><th>ID Record</th><th>Keterangan</th><th>IP</th></tr>
          </thead>
          <tbody>
          <?php foreach ($logs as $l): ?>
          <tr>
            <td style="white-space:nowrap;font-size:.78rem"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
            <td style="font-size:.82rem"><?= e($l['username'] ?? 'Sistem') ?></td>
            <td>
              <span style="font-size:.8rem">
                <?= $aksiIcon[$l['aksi']] ?? '⚙️' ?> <?= e($l['aksi']) ?>
              </span>
            </td>
            <td style="font-size:.78rem;color:var(--text-muted)"><?= e($l['tabel'] ?? '-') ?></td>
            <td style="text-align:center;font-size:.82rem"><?= $l['record_id'] ?? '-' ?></td>
            <td class="truncate" style="max-width:200px;font-size:.82rem"><?= e($l['keterangan']) ?></td>
            <td style="font-size:.78rem;color:var(--text-muted)"><?= e($l['ip_address'] ?? '') ?></td>
          </tr>
          <?php endforeach ?>
          <?php if (empty($logs)): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted)">Belum ada log.</td></tr>
          <?php endif ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <nav class="pagination-nav"><ul class="pagination">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <li class="page-item <?= $i === $page ? 'active' : '' ?>">
        <a class="page-link" href="log.php?page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor ?>
    </ul></nav>
    <?php endif ?>
  </main>
</div>
</body>
</html>
