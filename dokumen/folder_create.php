<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = intval($_SESSION['user_id']);

if ($role != 'operator') {
    header("Location: list.php?error=Anda tidak memiliki izin untuk membuat folder");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_folder = trim($_POST['nama_folder'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    
    if (empty($nama_folder)) {
        $params = [];
        if ($parent_id > 0) $params['folder'] = $parent_id;
        $params['error'] = 'Nama folder tidak boleh kosong';
        header("Location: list.php?" . http_build_query($params));
        exit();
    }
    
    // Cek duplikat
    if ($parent_id > 0) {
        $existing = db_select(
            "SELECT id FROM folders WHERE nama_folder = ? AND parent_id = ? AND created_by = ?", 
            [$nama_folder, $parent_id, $user_id]
        );
    } else {
        $existing = db_select(
            "SELECT id FROM folders WHERE nama_folder = ? AND parent_id IS NULL AND created_by = ?", 
            [$nama_folder, $user_id]
        );
    }
    
    if (!empty($existing)) {
        $params = [];
        if ($parent_id > 0) $params['folder'] = $parent_id;
        $params['error'] = 'Folder dengan nama tersebut sudah ada';
        header("Location: list.php?" . http_build_query($params));
        exit();
    }
    
    // Insert
    $data = [
        'nama_folder' => $nama_folder,
        'created_by' => $user_id
    ];
    
    if ($parent_id > 0) {
        $data['parent_id'] = $parent_id;
    }
    
    $result = db_insert('folders', $data);
    
    $params = [];
    if ($parent_id > 0) $params['folder'] = $parent_id;
    $params[$result ? 'success' : 'error'] = $result ? 'Folder berhasil dibuat' : 'Gagal membuat folder';
    header("Location: list.php?" . http_build_query($params));
    exit();
}

header("Location: list.php");
exit();