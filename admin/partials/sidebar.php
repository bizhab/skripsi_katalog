<?php
// admin/partials/sidebar.php
$current = basename($_SERVER['PHP_SELF']);
function activeClass(string $page, string $current): string {
    return $page === $current ? ' active' : '';
}
?>
<aside class="sidebar">
  <div class="sidebar-brand">📚 Admin Panel</div>
  <nav>
    <a href="index.php" class="<?= activeClass('index.php',$current) ?>">
      <span class="icon">📊</span> Dashboard
    </a>
    <a href="skripsi.php" class="<?= activeClass('skripsi.php',$current) ?>">
      <span class="icon">📚</span> Data Skripsi
    </a>
    <a href="skripsi-tambah.php" class="<?= activeClass('skripsi-tambah.php',$current) ?>">
      <span class="icon">➕</span> Tambah Skripsi
    </a>
    <a href="kategori.php" class="<?= activeClass('kategori.php',$current) ?>">
      <span class="icon">🏷️</span> Kategori
    </a>
    <a href="prodi.php" class="<?= activeClass('prodi.php',$current) ?>">
      <span class="icon">🎓</span> Program Studi
    </a>
    <a href="dosen.php" class="<?= activeClass('dosen.php',$current) ?>">
      <span class="icon">👨‍🏫</span> Dosen
    </a>
    <a href="log.php" class="<?= activeClass('log.php',$current) ?>">
      <span class="icon">📋</span> Log Aktivitas
    </a>
    <a href="../index.php" style="margin-top:1rem;border-top:1px solid #334155">
      <span class="icon">🌐</span> Lihat Katalog
    </a>
    <a href="logout.php">
      <span class="icon">🚪</span> Logout
    </a>
  </nav>
</aside>
