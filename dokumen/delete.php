<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman - hanya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data dokumen untuk hapus file
$dokumen = query("SELECT * FROM dokumen WHERE id = $id")->fetch_assoc();

if ($dokumen) {
    // Hapus file fisik
    $file_path = '../uploads/' . $dokumen['nama_file'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Hapus dari database
    query("DELETE FROM dokumen WHERE id = $id");
}

header("Location: list.php?success=Dokumen berhasil dihapus");
exit();
?>