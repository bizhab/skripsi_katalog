<?php
// viewer.php — FR04: PDF Viewer + NFR02: File PDF tidak bisa diakses via URL langsung
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); exit('Invalid request.'); }

$skripsi = getSkripsiById($id);
if (!$skripsi || !$skripsi['file_pdf']) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$filePath = UPLOAD_PATH . basename($skripsi['file_pdf']); // basename cegah path traversal
if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File PDF tidak ada di server.');
}

// Stream PDF secara aman — URL asli tidak pernah terekspos (NFR02)
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="skripsi-' . $id . '.pdf"');
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
// Cegah download langsung
header('X-Frame-Options: SAMEORIGIN');

readfile($filePath);
exit;
