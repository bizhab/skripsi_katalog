<?php
// includes/functions.php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// ── Sanitasi ───────────────────────────────────────────────
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sanitizeStr(?string $s): string {
    return trim(strip_tags($s ?? ''));
}

// ── FR01 & FR02: Pencarian + Filter ───────────────────────
function searchSkripsi(array $params): array {
    $db         = getDB();
    $keyword    = sanitizeStr($params['q'] ?? '');
    $tahun      = (int)($params['tahun']    ?? 0);
    $prodiId    = (int)($params['prodi']    ?? 0);
    $dosenId    = (int)($params['dosen']    ?? 0);
    $kategoriId = (int)($params['kategori'] ?? 0);
    $page       = max(1, (int)($params['page'] ?? 1));
    $offset     = ($page - 1) * ITEMS_PER_PAGE;

    $where  = ["s.status = 'aktif'"];
    $binds  = [];

    // Full-text search (Judul, Abstrak, Kata Kunci, Nama Penulis, NIM)
    if ($keyword !== '') {
        $where[] = "MATCH(s.judul, s.abstrak, s.kata_kunci, s.nama_penulis) AGAINST(? IN BOOLEAN MODE)";
        $binds[] = '+' . implode(' +', array_map('trim', explode(' ', $keyword)));
    }
    if ($tahun > 0)      { $where[] = "s.tahun_lulus = ?";              $binds[] = $tahun; }
    if ($prodiId > 0)    { $where[] = "s.program_studi_id = ?";         $binds[] = $prodiId; }
    if ($dosenId > 0)    { $where[] = "(s.dosen_pembimbing1_id = ? OR s.dosen_pembimbing2_id = ?)"; $binds[] = $dosenId; $binds[] = $dosenId; }
    if ($kategoriId > 0) { $where[] = "s.kategori_id = ?";              $binds[] = $kategoriId; }

    $whereClause = 'WHERE ' . implode(' AND ', $where);

    // Total rows (untuk pagination)
    $countSql = "SELECT COUNT(*) FROM skripsi s $whereClause";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($binds);
    $total = (int)$countStmt->fetchColumn();

    // Data utama
    $sql = "SELECT s.id, s.nim, s.nama_penulis, s.judul, s.tahun_lulus, s.kata_kunci,
                   s.file_pdf, s.view_count,
                   p.nama AS prodi,
                   k.nama AS kategori, k.warna_hex,
                   d1.nama AS pembimbing1, d2.nama AS pembimbing2
            FROM skripsi s
            LEFT JOIN program_studi p ON p.id = s.program_studi_id
            LEFT JOIN kategori      k ON k.id = s.kategori_id
            LEFT JOIN dosen        d1 ON d1.id = s.dosen_pembimbing1_id
            LEFT JOIN dosen        d2 ON d2.id = s.dosen_pembimbing2_id
            $whereClause
            ORDER BY s.tahun_lulus DESC, s.view_count DESC
            LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset";

    $stmt = $db->prepare($sql);
    $stmt->execute($binds);

    return [
        'data'        => $stmt->fetchAll(),
        'total'       => $total,
        'page'        => $page,
        'total_pages' => (int)ceil($total / ITEMS_PER_PAGE),
    ];
}

// ── Detail Skripsi ─────────────────────────────────────────
function getSkripsiById(int $id): ?array {
    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT s.*, p.nama AS prodi, p.kode AS kode_prodi,
                k.nama AS kategori, k.warna_hex,
                d1.nama AS pembimbing1, d2.nama AS pembimbing2
         FROM skripsi s
         LEFT JOIN program_studi p ON p.id = s.program_studi_id
         LEFT JOIN kategori      k ON k.id = s.kategori_id
         LEFT JOIN dosen        d1 ON d1.id = s.dosen_pembimbing1_id
         LEFT JOIN dosen        d2 ON d2.id = s.dosen_pembimbing2_id
         WHERE s.id = ? AND s.status = 'aktif' LIMIT 1"
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// Tambah view count
function incrementViewCount(int $id): void {
    $db = getDB();
    $db->prepare("UPDATE skripsi SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
}

// ── FR03: CRUD Skripsi ─────────────────────────────────────
function tambahSkripsi(array $data): int {
    $db   = getDB();

    // Cek duplikasi NIM & Judul (Risiko 2)
    $cek = $db->prepare("SELECT COUNT(*) FROM skripsi WHERE nim = ? OR judul = ?");
    $cek->execute([$data['nim'], $data['judul']]);
    if ($cek->fetchColumn() > 0) {
        throw new RuntimeException('NIM atau Judul skripsi sudah ada di sistem.');
    }

    $stmt = $db->prepare(
        "INSERT INTO skripsi
         (nim, nama_penulis, judul, abstrak, kata_kunci, tahun_lulus,
          program_studi_id, kategori_id, dosen_pembimbing1_id, dosen_pembimbing2_id,
          file_pdf, ukuran_file_kb, status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'aktif')"
    );
    $stmt->execute([
        $data['nim'], $data['nama_penulis'], $data['judul'],
        $data['abstrak'], $data['kata_kunci'], $data['tahun_lulus'],
        $data['program_studi_id'], $data['kategori_id'] ?: null,
        $data['dosen_pembimbing1_id'] ?: null, $data['dosen_pembimbing2_id'] ?: null,
        $data['file_pdf'] ?? null, $data['ukuran_file_kb'] ?? null,
    ]);
    $newId = (int)$db->lastInsertId();
    logAktivitas('tambah', 'skripsi', $newId, 'Tambah: ' . $data['judul']);
    return $newId;
}

function editSkripsi(int $id, array $data): void {
    $db   = getDB();

    // Cek duplikasi NIM & Judul (kecuali record sendiri)
    $cek = $db->prepare("SELECT COUNT(*) FROM skripsi WHERE (nim = ? OR judul = ?) AND id != ?");
    $cek->execute([$data['nim'], $data['judul'], $id]);
    if ($cek->fetchColumn() > 0) {
        throw new RuntimeException('NIM atau Judul sudah digunakan oleh skripsi lain.');
    }

    $setPdf = '';
    $binds  = [
        $data['nim'], $data['nama_penulis'], $data['judul'], $data['abstrak'],
        $data['kata_kunci'], $data['tahun_lulus'], $data['program_studi_id'],
        $data['kategori_id'] ?: null, $data['dosen_pembimbing1_id'] ?: null,
        $data['dosen_pembimbing2_id'] ?: null,
    ];
    if (!empty($data['file_pdf'])) {
        $setPdf  = ', file_pdf = ?, ukuran_file_kb = ?';
        $binds[] = $data['file_pdf'];
        $binds[] = $data['ukuran_file_kb'];
    }
    $binds[] = $id;

    $db->prepare(
        "UPDATE skripsi SET
           nim=?, nama_penulis=?, judul=?, abstrak=?, kata_kunci=?, tahun_lulus=?,
           program_studi_id=?, kategori_id=?, dosen_pembimbing1_id=?, dosen_pembimbing2_id=?
           $setPdf
         WHERE id=?"
    )->execute($binds);

    logAktivitas('edit', 'skripsi', $id, 'Edit: ' . $data['judul']);
}

function hapusSkripsi(int $id): void {
    $db   = getDB();
    // Soft delete
    $db->prepare("UPDATE skripsi SET status='dihapus' WHERE id=?")->execute([$id]);
    logAktivitas('hapus', 'skripsi', $id, 'Soft delete skripsi id=' . $id);
}

// ── FR06: Dashboard Statistik ──────────────────────────────
function getStatistikDashboard(): array {
    $db = getDB();

    $total = (int)$db->query("SELECT COUNT(*) FROM skripsi WHERE status='aktif'")->fetchColumn();
    $tahunIni = date('Y');

    // Skripsi per tahun (5 tahun terakhir)
    $perTahun = $db->query(
        "SELECT tahun_lulus AS tahun, COUNT(*) AS jumlah
         FROM skripsi WHERE status='aktif' AND tahun_lulus >= " . ($tahunIni - 5) . "
         GROUP BY tahun_lulus ORDER BY tahun_lulus"
    )->fetchAll();

    // Top 6 kategori
    $topKategori = $db->query(
        "SELECT k.nama, COUNT(s.id) AS jumlah, k.warna_hex
         FROM skripsi s JOIN kategori k ON k.id = s.kategori_id
         WHERE s.status='aktif' GROUP BY k.id ORDER BY jumlah DESC LIMIT 6"
    )->fetchAll();

    // Top 5 skripsi terpopuler
    $terpopuler = $db->query(
        "SELECT judul, nama_penulis, tahun_lulus, view_count FROM skripsi
         WHERE status='aktif' ORDER BY view_count DESC LIMIT 5"
    )->fetchAll();

    return compact('total', 'perTahun', 'topKategori', 'terpopuler');
}

// ── Upload PDF ─────────────────────────────────────────────
// FR03 + NFR02 + Risiko 1
function uploadPdf(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload gagal, kode error: ' . $file['error']);
    }
    if ($file['size'] > MAX_FILE_SIZE_BYTE) {
        throw new RuntimeException('Ukuran file melebihi batas ' . MAX_FILE_SIZE_KB . ' KB.');
    }

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_MIME, true)) {
        throw new RuntimeException('Hanya file PDF yang diizinkan.');
    }

    // Nama file acak — hindari path traversal (NFR02)
    $ext      = 'pdf';
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest     = UPLOAD_PATH . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Gagal memindahkan file ke server.');
    }

    return [
        'file_pdf'       => $filename,
        'ukuran_file_kb' => (int)ceil($file['size'] / 1024),
    ];
}

// ── Helper Umum ────────────────────────────────────────────
function getAllProdi(): array {
    return getDB()->query("SELECT * FROM program_studi ORDER BY nama")->fetchAll();
}
function getAllKategori(): array {
    return getDB()->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();
}
function getAllDosen(): array {
    return getDB()->query("SELECT * FROM dosen ORDER BY nama")->fetchAll();
}
function getTahunList(): array {
    return getDB()->query(
        "SELECT DISTINCT tahun_lulus FROM skripsi WHERE status='aktif' ORDER BY tahun_lulus DESC"
    )->fetchAll(PDO::FETCH_COLUMN);
}

function pagination(int $total, int $page, int $totalPages, array $params = []): string {
    if ($totalPages <= 1) return '';
    $params['page'] = $page;
    $base = '?' . http_build_query($params);
    $html = '<nav class="pagination-nav" aria-label="Navigasi halaman"><ul class="pagination">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $params['page'] = $i;
        $url    = '?' . http_build_query($params);
        $active = ($i === $page) ? ' active' : '';
        $html  .= "<li class=\"page-item$active\"><a class=\"page-link\" href=\"$url\">$i</a></li>";
    }
    return $html . '</ul></nav>';
}
