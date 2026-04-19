<?php
// admin/login.php — FR07: Sistem Autentikasi Admin
require_once __DIR__ . '/../includes/auth.php';

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
<body>
<div class="login-wrapper">
  <div class="login-card">
    <h2>🔑 Login Admin</h2>
    <p style="text-align:center;color:var(--text-muted);font-size:.85rem;margin-bottom:1.5rem">
      <?= APP_NAME ?>
    </p>
    <?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif ?>
    <form method="POST">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username"
               value="<?= e($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:.75rem">
        Masuk
      </button>
    </form>
    <p style="text-align:center;font-size:.78rem;color:var(--text-muted);margin-top:1.5rem">
      <a href="../index.php">← Kembali ke Katalog</a>
    </p>
  </div>
</div>
</body>
</html>
