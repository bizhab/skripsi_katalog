<?php
// admin/login.php — FR07: Sistem Autentikasi Admin
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php'; // <-- Baris ini yang memperbaiki masalahnya

if (isAdmin()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = sanitizeStr($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($user && $pass) {
        if (adminLogin($user, $pass)) {
            $redirect = sanitizeStr($_GET['redirect'] ?? '');
            // Validasi redirect aman
            if (!$redirect || !str_starts_with($redirect, '/')) {
                $redirect = APP_URL . '/admin/index.php';
            }
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Harap isi semua field.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Admin — <?= APP_NAME ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: var(--bg);">

<nav class="navbar">
  <a class="navbar-brand" href="../index.php">📚 <?= APP_NAME ?></a>
  <div class="nav-links">
    <a href="../index.php">← Kembali ke Katalog</a>
  </div>
</nav>

<div class="login-wrapper" style="min-height: calc(100vh - 65px); align-items: flex-start; padding-top: 10vh; background: transparent;">
  
  <div class="login-card" style="box-shadow: 0 10px 25px rgba(0,0,0,.08); border: none; border-top: 5px solid var(--primary); border-radius: 12px; padding: 2.5rem 2rem;">
    
    <div style="text-align:center; margin-bottom: 2rem;">
      <h2 style="font-weight: 800; color: var(--text); margin-bottom: 0.3rem;">Masuk Panel Admin</h2>
      <p style="color:var(--text-muted);font-size:.9rem;">
        Kelola data skripsi dan tugas akhir.
      </p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error" style="display:flex; gap:0.5rem; align-items:center; font-weight:600;">
      <span>⚠️</span> <?= e($error) ?>
    </div>
    <?php endif ?>

    <form method="POST">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username"
               value="<?= e($_POST['username'] ?? '') ?>" placeholder="Ketik username Anda..."
               style="padding: .75rem 1rem;">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password" 
               placeholder="••••••••" style="padding: .75rem 1rem;">
      </div>
      
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:.85rem; font-size: 1rem; margin-top: 1rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);">
        Masuk ➔
      </button>
    </form>
    
  </div>
</div>

</body>
</html>