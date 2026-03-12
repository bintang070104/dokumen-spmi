<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data dokumen
$dokumen = query("SELECT * FROM dokumen WHERE id = $id")->fetch_assoc();

if (!$dokumen) {
    header("Location: list.php");
    exit();
}

$file_path = '../uploads/' . $dokumen['nama_file'];

if (file_exists($file_path)) {
    // Set headers untuk download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($dokumen['nama_file']) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    
    // Baca dan output file
    readfile($file_path);
    exit();
} else {
    header("Location: list.php?error=File tidak ditemukan");
    exit();
}
?>