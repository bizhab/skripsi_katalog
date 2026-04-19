<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    $password_baru = 'Admin@1234';
    $hash_baru = password_hash($password_baru, PASSWORD_BCRYPT, ['cost' => 12]);
    
    $stmt = $db->prepare("UPDATE admin SET password = ? WHERE username = 'superadmin'");
    $stmt->execute([$hash_baru]);
    
    echo "<h1>✅ Berhasil!</h1>";
    echo "<p>Password admin telah direset menjadi: <b>Admin@1234</b></p>";
    echo "<p>Silakan kembali ke <a href='admin/login.php'>Halaman Login</a>.</p>";
} catch (Exception $e) {
    echo "Gagal: " . $e->getMessage();
}
?>