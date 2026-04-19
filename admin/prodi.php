<?php
// admin/prodi.php — Manajemen Program Studi
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db  = getDB();
$msg = $_GET['msg'] ?? '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $kode     = strtoupper(sanitizeStr($_POST['kode'] ?? ''));
    $nama     = sanitizeStr($_POST['nama'] ?? '');
    $fakultas = sanitizeStr($_POST['fakultas'] ?? '');

    if ($action === 'hapus') {
        $pid = (int)($_POST['id'] ?? 0);
        $count = (int)$db->prepare("SELECT COUNT(*) FROM skripsi WHERE program_studi_id = ?")->execute([$pid]) ? 
                 $db->query("SELECT COUNT(*) FROM skripsi WHERE program_studi_id = $pid")->fetchColumn() : 0;
        if ($count > 0) {
            $errors[] = "Tidak bisa menghapus: ada $count skripsi yang terkait.";
        } else {
            $db->prepare("DELETE FROM program_studi WHERE id = ?")->execute([$pid]);
            logAktivitas('hapus', 'program_studi', $pid, 'Hapus prodi id=' . $pid);
            header('Location: prodi.php?msg=hapus'); exit;
        }
    } elseif ($action === 'tambah' || $action === 'edit') {
        if (!$kode) $errors[] = 'Kode wajib diisi.';
        if (!$nama) $errors[] = 'Nama wajib diisi.';
        if (empty($errors)) {
            try {
                if ($action === 'tambah') {
                    $db->prepare("INSERT INTO program_studi (kode, nama, fakultas) VALUES (?,?,?)")
                       ->execute([$kode, $nama, $fakultas]);
                    logAktivitas('tambah', 'program_studi', (int)$db->lastInsertId(), 'Tambah prodi: ' . $nama);
                } else {
                    $pid = (int)($_POST['id'] ?? 0);
                    $db->prepare("UPDATE program_studi SET kode=?, nama=?, fakultas=? WHERE id=?")
                       ->execute([$kode, $nama, $fakultas, $pid]);
                    logAktivitas('edit', 'program_studi', $pid, 'Edit prodi: ' . $nama);
                }
                header('Location: prodi.php?msg=simpan'); exit;
            } catch (PDOException $e) {
                $errors[] = 'Kode prodi sudah digunakan.';
            }
        }
    }
}

$prodis = $db->query(
    "SELECT p.*, COUNT(s.id) AS jumlah_skripsi
     FROM program_studi p
     LEFT JOIN skripsi s ON s.program_studi_id = p.id AND s.status='aktif'
     GROUP BY p.id ORDER BY p.nama"
)->fetchAll();

$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM program_studi WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['edit']]);
    $editData = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Program Studi — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header">
      <h1>Program Studi</h1>
      <p>Kelola data program studi / jurusan</p>
    </div>

    <?php if ($msg === 'simpan'): ?><div class="alert alert-success">✅ Program studi berhasil disimpan.</div><?php endif ?>
    <?php if ($msg === 'hapus'):  ?><div class="alert alert-success">🗑️ Program studi berhasil dihapus.</div><?php endif ?>
    <?php if ($errors): ?>
    <div class="alert alert-error"><?php foreach ($errors as $err) echo '&bull; ' . e($err) . '<br>'; ?></div>
    <?php endif ?>

    <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:1.5rem;align-items:start">
      <div class="form-card">
        <h3 style="margin-bottom:1.2rem;font-size:1rem">
          <?= $editData ? '✏️ Edit Program Studi' : '➕ Tambah Program Studi' ?>
        </h3>
        <form method="POST">
          <input type="hidden" name="action" value="<?= $editData ? 'edit' : 'tambah' ?>">
          <?php if ($editData): ?>
          <input type="hidden" name="id" value="<?= $editData['id'] ?>">
          <?php endif ?>
          <div class="form-group">
            <label>Kode *</label>
            <input type="text" name="kode" required value="<?= e($editData['kode'] ?? '') ?>" placeholder="Contoh: TIF">
          </div>
          <div class="form-group">
            <label>Nama Program Studi *</label>
            <input type="text" name="nama" required value="<?= e($editData['nama'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Fakultas</label>
            <input type="text" name="fakultas" value="<?= e($editData['fakultas'] ?? '') ?>">
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Simpan</button>
            <?php if ($editData): ?>
            <a href="prodi.php" class="btn btn-outline">Batal</a>
            <?php endif ?>
          </div>
        </form>
      </div>

      <div class="chart-card" style="padding:0;overflow:hidden">
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Kode</th><th>Nama</th><th>Fakultas</th><th>Skripsi</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($prodis as $p): ?>
            <tr>
              <td><code><?= e($p['kode']) ?></code></td>
              <td><?= e($p['nama']) ?></td>
              <td style="font-size:.82rem;color:var(--text-muted)"><?= e($p['fakultas']) ?></td>
              <td><?= $p['jumlah_skripsi'] ?></td>
              <td style="white-space:nowrap">
                <a href="prodi.php?edit=<?= $p['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus program studi ini?')">
                  <input type="hidden" name="action" value="hapus">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                </form>
              </td>
            </tr>
            <?php endforeach ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
</body>
</html>
