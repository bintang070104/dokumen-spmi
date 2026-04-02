<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Validasi ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: list.php?error=ID dokumen tidak valid");
    exit();
}

// Ambil data dokumen dengan prepared statement
$dokumen = db_select_one("SELECT * FROM dokumen WHERE id = ?", [$id]);

if (!$dokumen) {
    header("Location: list.php?error=Dokumen tidak ditemukan");
    exit();
}

// Cek hak akses - operator hanya bisa download dokumen sendiri (kecuali admin)
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role !== 'admin' && $dokumen['uploaded_by'] != $user_id) {
    header("Location: list.php?error=Anda tidak memiliki akses untuk mendownload dokumen ini");
    exit();
}

// Sanitasi nama file untuk keamanan
$filename = basename($dokumen['nama_file']);
$filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

// Path file dengan validasi
$file_path = '../uploads/' . $dokumen['nama_file'];

// Normalisasi path untuk mencegah path traversal
$real_path = realpath($file_path);
$upload_dir = realpath('../uploads/');

if ($real_path === false || strpos($real_path, $upload_dir) !== 0 || !is_file($real_path)) {
    header("Location: list.php?error=File tidak ditemukan atau tidak valid");
    exit();
}

// Cek ukuran file
$file_size = filesize($real_path);
if ($file_size === false || $file_size <= 0) {
    header("Location: list.php?error=File rusak atau kosong");
    exit();
}

// Log download (opsional)
db_insert('log_aktivitas', [
    'user_id' => $user_id,
    'aksi' => 'download_dokumen',
    'detail' => 'Download: ' . $dokumen['judul_dokumen'] . ' (' . $filename . ')',
    'created_at' => date('Y-m-d H:i:s')
]);

// Set headers untuk download yang lebih aman
$mime_type = mime_content_type($real_path) ?: 'application/octet-stream';

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $file_size);
header('Expires: 0');
header('Cache-Control: private, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

// Disable output buffering untuk file besar
if (ob_get_level()) {
    ob_end_clean();
}

// Baca file dengan chunks untuk memory efficiency
$chunk_size = 1024 * 1024; // 1MB chunks
$handle = fopen($real_path, 'rb');

if (!$handle) {
    header("Location: list.php?error=Gagal membaca file");
    exit();
}

while (!feof($handle)) {
    echo fread($handle, $chunk_size);
    flush();
}

fclose($handle);
exit();
?>