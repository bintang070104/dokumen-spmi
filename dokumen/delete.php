<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman - hanya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Validasi ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: list.php?error=ID dokumen tidak valid");
    exit();
}

try {
    // Ambil data dokumen dengan prepared statement
    $dokumen = db_select_one("SELECT * FROM dokumen WHERE id = ?", [$id]);
    
    if (!$dokumen) {
        header("Location: list.php?error=Dokumen tidak ditemukan");
        exit();
    }
    
    // Hapus file fisik
    $file_path = '../uploads/' . $dokumen['nama_file'];
    if (file_exists($file_path) && is_file($file_path)) {
        if (!unlink($file_path)) {
            // Log error tapi tetap lanjut hapus database
            error_log("Gagal menghapus file: " . $file_path);
        }
    }
    
    // Hapus dari database dengan prepared statement
    $deleted = db_delete('dokumen', 'id = ?', [$id]);
    
    if ($deleted) {
        // Log aktivitas (opsional)
        db_insert('log_aktivitas', [
            'user_id' => $_SESSION['user_id'],
            'aksi' => 'hapus_dokumen',
            'detail' => 'Menghapus dokumen: ' . $dokumen['judul_dokumen'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        header("Location: list.php?success=Dokumen berhasil dihapus");
    } else {
        header("Location: list.php?error=Gagal menghapus dokumen dari database");
    }
    exit();
    
} catch (Exception $e) {
    error_log("Error hapus dokumen: " . $e->getMessage());
    header("Location: list.php?error=Terjadi kesalahan sistem");
    exit();
}
?>