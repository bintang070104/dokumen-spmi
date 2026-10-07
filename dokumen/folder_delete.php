<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = intval($_SESSION['user_id']);

// Ambil parameter
$folder_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$current_folder = isset($_GET['current']) ? intval($_GET['current']) : 0;

// Helper buat redirect
function redirect_list($current_folder, $message = null, $is_error = false) {
    $params = [];
    if ($current_folder > 0) {
        $params['folder'] = $current_folder;
    }
    if ($message !== null) {
        $params[$is_error ? 'error' : 'success'] = $message;
    }
    $query = !empty($params) ? '?' . http_build_query($params) : '';
    header("Location: list.php" . $query);
    exit();
}

if ($folder_id <= 0) {
    redirect_list($current_folder, 'Folder tidak valid', true);
}

// Cek apakah folder ada
$folder = db_select_one("SELECT * FROM folders WHERE id = ?", [$folder_id]);
if (!$folder) {
    redirect_list($current_folder, 'Folder tidak ditemukan', true);
}

// Cek permission: admin bisa hapus semua, operator hanya milik sendiri
if ($role != 'admin' && $folder['created_by'] != $user_id) {
    redirect_list($current_folder, 'Anda tidak memiliki izin menghapus folder ini', true);
}

// Cek apakah folder masih punya subfolder
$subfolders = db_select("SELECT id FROM folders WHERE parent_id = ?", [$folder_id]);
if (!empty($subfolders)) {
    redirect_list($current_folder, 'Folder masih berisi subfolder. Hapus subfolder terlebih dahulu.', true);
}

// Cek apakah folder masih punya dokumen
$documents = db_select("SELECT id FROM dokumen WHERE folder_id = ?", [$folder_id]);
if (!empty($documents)) {
    redirect_list($current_folder, 'Folder masih berisi dokumen. Pindahkan atau hapus dokumen terlebih dahulu.', true);
}

// Hapus folder
$result = db_delete('folders', 'id = ?', [$folder_id]);

if ($result) {
    redirect_list($current_folder, 'Folder berhasil dihapus');
} else {
    redirect_list($current_folder, 'Gagal menghapus folder', true);
}