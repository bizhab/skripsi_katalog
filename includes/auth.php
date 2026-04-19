<?php
// includes/auth.php
// FR07: Sistem Autentikasi Admin — NFR02: Password Bcrypt

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ── Login ──────────────────────────────────────────────────
function adminLogin(string $username, string $password): bool {
    $db  = getDB();
    $sql = "SELECT id, password, nama_lengkap FROM admin WHERE username = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_nama'] = $admin['nama_lengkap'];
        $_SESSION['login_time'] = time();
        logAktivitas('login', null, null, 'Login berhasil');
        return true;
    }
    return false;
}

// ── Logout ─────────────────────────────────────────────────
function adminLogout(): void {
    logAktivitas('logout', null, null, 'Logout');
    session_unset();
    session_destroy();
    header('Location: ' . APP_URL . '/admin/login.php');
    exit;
}

// ── Guard (panggil di setiap halaman admin) ────────────────
function requireAdmin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . APP_URL . '/admin/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    // Session timeout
    if (time() - ($_SESSION['login_time'] ?? 0) > SESSION_TIMEOUT) {
        adminLogout();
    }
    $_SESSION['login_time'] = time(); // rolling
}

function isAdmin(): bool {
    return !empty($_SESSION['admin_id']);
}

// ── Audit Log ──────────────────────────────────────────────
function logAktivitas(string $aksi, ?string $tabel, ?int $recordId, string $ket = ''): void {
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            "INSERT INTO log_aktivitas (admin_id, aksi, tabel, record_id, keterangan, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $_SESSION['admin_id'] ?? null,
            $aksi,
            $tabel,
            $recordId,
            $ket,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) {
        // Jangan crash karena log gagal
        error_log('logAktivitas error: ' . $e->getMessage());
    }
}
