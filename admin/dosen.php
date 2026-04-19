<?php
// admin/dosen.php — Manajemen Dosen Pembimbing
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db  = getDB();
$msg = $_GET['msg'] ?? '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'hapus') {
        $did = (int)($_POST['id'] ?? 0);
        // Lepas referensi di skripsi
        $db->prepare("UPDATE skripsi SET dosen_pembimbing1_id = NULL WHERE dosen_pembimbing1_id = ?")->execute([$did]);
        $db->prepare("UPDATE skripsi SET dosen_pembimbing2_id = NULL WHERE dosen_pembimbing2_id = ?")->execute([$did]);
        $db->prepare("DELETE FROM dosen WHERE id = ?")->execute([$did]);
        logAktivitas('hapus', 'dosen', $did, 'Hapus dosen id=' . $did);
        header('Location: dosen.php?msg=hapus'); exit;
    }

    $nip   = sanitizeStr($_POST['nip'] ?? '');
    $nama  = sanitizeStr($_POST['nama'] ?? '');
    $email = sanitizeStr($_POST['email'] ?? '');

    if (!$nama) $errors[] = 'Nama wajib diisi.';

    if (empty($errors)) {
        try {
            if ($action === 'tambah') {
                $db->prepare("INSERT INTO dosen (nip, nama, email) VALUES (?,?,?)")
                   ->execute([$nip ?: null, $nama, $email ?: null]);
                logAktivitas('tambah', 'dosen', (int)$db->lastInsertId(), 'Tambah dosen: ' . $nama);
            } else {
                $did = (int)($_POST['id'] ?? 0);
                $db->prepare("UPDATE dosen SET nip=?, nama=?, email=? WHERE id=?")
                   ->execute([$nip ?: null, $nama, $email ?: null, $did]);
                logAktivitas('edit', 'dosen', $did, 'Edit dosen: ' . $nama);
            }
            header('Location: dosen.php?msg=simpan'); exit;
        } catch (PDOException $e) {
            $errors[] = 'NIP sudah terdaftar.';
        }
    }
}

$dosens = $db->query(
    "SELECT d.*,
       (SELECT COUNT(*) FROM skripsi WHERE dosen_pembimbing1_id = d.id AND status='aktif') +
       (SELECT COUNT(*) FROM skripsi WHERE dosen_pembimbing2_id = d.id AND status='aktif') AS jumlah_bimbingan
     FROM dosen d ORDER BY d.nama"
)->fetchAll();

$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM dosen WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['edit']]);
    $editData = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dosen Pembimbing — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header">
      <h1>Dosen Pembimbing</h1>
      <p>Kelola data dosen pembimbing skripsi</p>
    </div>

    <?php if ($msg === 'simpan'): ?><div class="alert alert-success">✅ Data dosen berhasil disimpan.</div><?php endif ?>
    <?php if ($msg === 'hapus'):  ?><div class="alert alert-success">🗑️ Dosen berhasil dihapus.</div><?php endif ?>
    <?php if ($errors): ?>
    <div class="alert alert-error"><?php foreach ($errors as $err) echo '&bull; ' . e($err) . '<br>'; ?></div>
    <?php endif ?>

    <div style="display:grid;grid-template-columns:1fr 1.8fr;gap:1.5rem;align-items:start">
      <div class="form-card">
        <h3 style="margin-bottom:1.2rem;font-size:1rem">
          <?= $editData ? '✏️ Edit Dosen' : '➕ Tambah Dosen' ?>
        </h3>
        <form method="POST">
          <input type="hidden" name="action" value="<?= $editData ? 'edit' : 'tambah' ?>">
          <?php if ($editData): ?>
          <input type="hidden" name="id" value="<?= $editData['id'] ?>">
          <?php endif ?>
          <div class="form-group">
            <label>NIP</label>
            <input type="text" name="nip" value="<?= e($editData['nip'] ?? '') ?>" placeholder="Opsional">
          </div>
          <div class="form-group">
            <label>Nama Lengkap + Gelar *</label>
            <input type="text" name="nama" required value="<?= e($editData['nama'] ?? '') ?>"
                   placeholder="Contoh: Dr. Budi Santoso, M.Kom">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= e($editData['email'] ?? '') ?>" placeholder="Opsional">
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Simpan</button>
            <?php if ($editData): ?><a href="dosen.php" class="btn btn-outline">Batal</a><?php endif ?>
          </div>
        </form>
      </div>

      <div class="chart-card" style="padding:0;overflow:hidden">
        <div class="table-wrapper">
          <table>
            <thead><tr><th>NIP</th><th>Nama</th><th>Email</th><th>Bimbingan</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($dosens as $d): ?>
            <tr>
              <td style="font-size:.78rem"><?= e($d['nip'] ?? '-') ?></td>
              <td><?= e($d['nama']) ?></td>
              <td style="font-size:.82rem;color:var(--text-muted)"><?= e($d['email'] ?? '-') ?></td>
              <td><?= $d['jumlah_bimbingan'] ?> skripsi</td>
              <td style="white-space:nowrap">
                <a href="dosen.php?edit=<?= $d['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus dosen ini?')">
                  <input type="hidden" name="action" value="hapus">
                  <input type="hidden" name="id" value="<?= $d['id'] ?>">
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
