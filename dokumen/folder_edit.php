<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = intval($_SESSION['user_id']);

// Helper redirect
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

// Ambil parameter
$folder_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$current_folder = isset($_GET['current']) ? intval($_GET['current']) : 0;

if ($folder_id <= 0) {
    redirect_list($current_folder, 'Folder tidak valid', true);
}

// Cek apakah folder ada
$folder = db_select_one("SELECT * FROM folders WHERE id = ?", [$folder_id]);
if (!$folder) {
    redirect_list($current_folder, 'Folder tidak ditemukan', true);
}

// Cek permission: admin bisa edit semua, operator hanya milik sendiri
if ($role != 'admin' && $folder['created_by'] != $user_id) {
    redirect_list($current_folder, 'Anda tidak memiliki izin mengedit folder ini', true);
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_folder = trim($_POST['nama_folder'] ?? '');
    
    if (empty($nama_folder)) {
        $error = "Nama folder tidak boleh kosong";
    } else {
        // Cek nama duplikat di level yang sama (kecuali folder ini sendiri)
        $parent_id = $folder['parent_id'];
        if ($parent_id) {
            $existing = db_select(
                "SELECT id FROM folders WHERE nama_folder = ? AND parent_id = ? AND id != ? AND created_by = ?", 
                [$nama_folder, $parent_id, $folder_id, $user_id]
            );
        } else {
            $existing = db_select(
                "SELECT id FROM folders WHERE nama_folder = ? AND parent_id IS NULL AND id != ? AND created_by = ?", 
                [$nama_folder, $folder_id, $user_id]
            );
        }
        
        if (!empty($existing)) {
            $error = "Folder dengan nama tersebut sudah ada di lokasi ini";
        } else {
            // Update folder
            $result = db_update('folders', ['nama_folder' => $nama_folder], 'id = ?', [$folder_id]);
            
            if ($result !== false) {
                redirect_list($current_folder, 'Folder berhasil diperbarui');
            } else {
                $error = "Gagal memperbarui folder";
            }
        }
    }
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Folder - SPMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .edit-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .edit-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 2.5rem;
            width: 100%;
            max-width: 480px;
        }
        .folder-icon-big {
            width: 80px;
            height: 80px;
            background: #e8f0fe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .folder-icon-big i {
            font-size: 2.5rem;
            color: #f4b400;
        }
        .btn-rounded {
            border-radius: 24px;
            padding: 10px 24px;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="edit-card">
            <div class="folder-icon-big">
                <i class="bi bi-folder-fill"></i>
            </div>
            
            <h4 class="text-center mb-1">Edit Folder</h4>
            <p class="text-muted text-center mb-4">Ubah nama folder</p>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger rounded-3" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-medium">Nama Folder</label>
                    <input type="text" name="nama_folder" class="form-control form-control-lg" 
                           value="<?php echo e($folder['nama_folder']); ?>" 
                           placeholder="Masukkan nama folder" required autofocus>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-rounded">
                        <i class="bi bi-check-lg me-2"></i>Simpan Perubahan
                    </button>
                    <a href="list.php<?php 
                        $cancel_params = [];
                        if ($current_folder > 0) $cancel_params['folder'] = $current_folder;
                        echo !empty($cancel_params) ? '?' . http_build_query($cancel_params) : '';
                    ?>" class="btn btn-light btn-rounded">
                        <i class="bi bi-x-lg me-2"></i>Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>