<?php
// admin/kategori.php — FR05: Manajemen Kategori
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db     = getDB();
$errors = [];
$msg    = $_GET['msg'] ?? '';

// Handle POST (tambah / edit / hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $nama      = sanitizeStr($_POST['nama'] ?? '');
        $slug      = sanitizeStr($_POST['slug'] ?? '');
        $deskripsi = sanitizeStr($_POST['deskripsi'] ?? '');
        $warna     = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['warna_hex'] ?? '') ? $_POST['warna_hex'] : '#3B82F6';

        if (!$nama) $errors[] = 'Nama kategori wajib diisi.';
        if (!$slug) $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nama));

        if (empty($errors)) {
            try {
                if ($action === 'tambah') {
                    $db->prepare("INSERT INTO kategori (nama, slug, deskripsi, warna_hex) VALUES (?,?,?,?)")
                       ->execute([$nama, $slug, $deskripsi, $warna]);
                    logAktivitas('tambah', 'kategori', (int)$db->lastInsertId(), 'Tambah kategori: ' . $nama);
                } else {
                    $kid = (int)($_POST['id'] ?? 0);
                    $db->prepare("UPDATE kategori SET nama=?, slug=?, deskripsi=?, warna_hex=? WHERE id=?")
                       ->execute([$nama, $slug, $deskripsi, $warna, $kid]);
                    logAktivitas('edit', 'kategori', $kid, 'Edit kategori: ' . $nama);
                }
                header('Location: kategori.php?msg=simpan'); exit;
            } catch (PDOException $e) {
                $errors[] = 'Slug sudah digunakan. Gunakan slug yang berbeda.';
            }
        }
    }

    if ($action === 'hapus') {
        $kid = (int)($_POST['id'] ?? 0);
        // Set NULL dulu di skripsi supaya FK tidak error
        $db->prepare("UPDATE skripsi SET kategori_id = NULL WHERE kategori_id = ?")->execute([$kid]);
        $db->prepare("DELETE FROM kategori WHERE id = ?")->execute([$kid]);
        logAktivitas('hapus', 'kategori', $kid, 'Hapus kategori id=' . $kid);
        header('Location: kategori.php?msg=hapus'); exit;
    }
}

$kategoris = $db->query(
    "SELECT k.*, COUNT(s.id) AS jumlah_skripsi
     FROM kategori k
     LEFT JOIN skripsi s ON s.kategori_id = k.id AND s.status = 'aktif'
     GROUP BY k.id ORDER BY k.nama"
)->fetchAll();

// Edit mode
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM kategori WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['edit']]);
    $editData = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manajemen Kategori — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-header">
      <h1>Manajemen Kategori</h1>
      <p>Kelola pengelompokan topik skripsi</p>
    </div>

    <?php if ($msg === 'simpan'): ?><div class="alert alert-success">✅ Kategori berhasil disimpan.</div><?php endif ?>
    <?php if ($msg === 'hapus'):  ?><div class="alert alert-success">🗑️ Kategori berhasil dihapus.</div><?php endif ?>
    <?php if ($errors): ?>
    <div class="alert alert-error"><?php foreach ($errors as $e) echo '&bull; ' . e($e) . '<br>'; ?></div>
    <?php endif ?>

    <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:1.5rem;align-items:start">

      <!-- Form Tambah/Edit -->
      <div class="form-card">
        <h3 style="margin-bottom:1.2rem;font-size:1rem">
          <?= $editData ? '✏️ Edit Kategori' : '➕ Tambah Kategori' ?>
        </h3>
        <form method="POST">
          <input type="hidden" name="action" value="<?= $editData ? 'edit' : 'tambah' ?>">
          <?php if ($editData): ?>
          <input type="hidden" name="id" value="<?= $editData['id'] ?>">
          <?php endif ?>

          <div class="form-group">
            <label>Nama Kategori *</label>
            <input type="text" name="nama" required value="<?= e($editData['nama'] ?? '') ?>"
                   id="namaInput" oninput="autoSlug()">
          </div>
          <div class="form-group">
            <label>Slug (URL)</label>
            <input type="text" name="slug" id="slugInput" value="<?= e($editData['slug'] ?? '') ?>"
                   placeholder="otomatis dari nama">
            <div class="form-hint">Huruf kecil, angka, dan tanda hubung saja.</div>
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="deskripsi" style="min-height:70px"><?= e($editData['deskripsi'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Warna Badge</label>
            <div style="display:flex;gap:.6rem;align-items:center">
              <input type="color" name="warna_hex" value="<?= e($editData['warna_hex'] ?? '#3B82F6') ?>"
                     style="width:48px;height:38px;padding:2px;border-radius:6px;border:1px solid var(--border);cursor:pointer">
              <span style="font-size:.82rem;color:var(--text-muted)">Pilih warna untuk badge kategori</span>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Simpan</button>
            <?php if ($editData): ?>
            <a href="kategori.php" class="btn btn-outline">Batal</a>
            <?php endif ?>
          </div>
        </form>
      </div>

      <!-- Daftar Kategori -->
      <div class="chart-card" style="padding:0;overflow:hidden">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>Nama</th><th>Slug</th><th>Skripsi</th><th>Warna</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php foreach ($kategoris as $k): ?>
            <tr>
              <td><strong><?= e($k['nama']) ?></strong></td>
              <td style="font-size:.78rem;color:var(--text-muted)"><?= e($k['slug']) ?></td>
              <td><?= $k['jumlah_skripsi'] ?></td>
              <td>
                <span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:<?= e($k['warna_hex']) ?>"></span>
              </td>
              <td style="white-space:nowrap">
                <a href="kategori.php?edit=<?= $k['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus kategori ini? Skripsi yang terkait tidak akan dihapus.')">
                  <input type="hidden" name="action" value="hapus">
                  <input type="hidden" name="id" value="<?= $k['id'] ?>">
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
<script>
function autoSlug() {
  const nama = document.getElementById('namaInput').value;
  const slug = nama.toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
  document.getElementById('slugInput').value = slug;
}
</script>
</body>
</html>
