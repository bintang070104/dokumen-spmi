<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Fungsi helper
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = intval($_SESSION['user_id']);
$role = $_SESSION['role'];

// Validasi ID
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

// Hanya admin atau pemilik dokumen yang bisa edit
if ($role !== 'admin' && $dokumen['uploaded_by'] != $user_id) {
    header("Location: list.php?error=Anda tidak memiliki akses untuk mengedit dokumen ini");
    exit();
}

// Ambil data kategori
$kategori = db_select("SELECT * FROM kategori ORDER BY nama_kategori ASC");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    $judul = trim($_POST['judul_dokumen'] ?? '');
    $kategori_id = intval($_POST['kategori_id'] ?? 0);
    $versi = trim($_POST['versi'] ?? '');
    $tanggal_kadaluarsa = $_POST['tanggal_kadaluarsa'] ?? '';
    $status = $_POST['status'] ?? '';
    
    // Validasi
    $errors = [];
    
    if (empty($judul)) {
        $errors[] = "Judul dokumen wajib diisi.";
    } elseif (strlen($judul) > 255) {
        $errors[] = "Judul maksimal 255 karakter.";
    }
    
    if ($kategori_id <= 0) {
        $errors[] = "Kategori harus dipilih.";
    }
    
    if (empty($versi)) {
        $errors[] = "Versi wajib diisi.";
    }
    
    if (empty($tanggal_kadaluarsa)) {
        $errors[] = "Tanggal kadaluarsa wajib diisi.";
    }
    
    if (!in_array($status, ['aktif', 'kadaluarsa'])) {
        $errors[] = "Status tidak valid.";
    }
    
    if (empty($errors)) {
        // Update dengan prepared statement
        $updated = db_update('dokumen', [
            'judul_dokumen' => $judul,
            'kategori_id' => $kategori_id,
            'versi' => $versi,
            'tanggal_kadaluarsa' => $tanggal_kadaluarsa,
            'status' => $status
        ], 'id = ?', [$id]);
        
        if ($updated !== false) {
            // Log aktivitas
            db_insert('log_aktivitas', [
                'user_id' => $user_id,
                'aksi' => 'edit_dokumen',
                'detail' => 'Mengedit dokumen: ' . $judul,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            header("Location: list.php?success=Dokumen berhasil diupdate");
            exit();
        } else {
            $error = "Gagal mengupdate dokumen.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Dokumen - SPMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            color: white;
        }
        .sidebar-admin { background: #2c3e50; }
        .sidebar-operator { background: #27ae60; }
        .sidebar-pimpinan { background: #8e44ad; }
        .sidebar .nav-link {
            color: white;
            padding: 15px 20px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            opacity: 0.9;
            background: rgba(255,255,255,0.1);
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .file-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0 sidebar-<?php echo e($role); ?>">
                <div class="p-3 text-center border-bottom">
                    <h5>SPMI System</h5>
                    <small><?php echo e(ucfirst($role)); ?> Panel</small>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/<?php echo e($role); ?>.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <?php if ($role === 'operator'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="upload.php">
                            <i class="bi bi-upload me-2"></i> Upload Dokumen
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="list.php">
                            <i class="bi bi-folder me-2"></i> Daftar Dokumen
                        </a>
                    </li>
                    <li class="nav-item mt-auto">
                        <a class="nav-link text-danger" href="../auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-0">
                <nav class="navbar navbar-expand-lg p-3">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1">Edit Dokumen</span>
                    </div>
                </nav>

                <div class="p-4">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Form Edit Dokumen</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($error)): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                            <?php echo $error; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <form action="" method="POST">
                                        <div class="mb-3">
                                            <label for="judul_dokumen" class="form-label">Judul Dokumen <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   id="judul_dokumen"
                                                   name="judul_dokumen" 
                                                   class="form-control" 
                                                   value="<?php echo e($dokumen['judul_dokumen']); ?>" 
                                                   maxlength="255"
                                                   required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="kategori_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                            <select id="kategori_id" name="kategori_id" class="form-select" required>
                                                <option value="">-- Pilih Kategori --</option>
                                                <?php foreach ($kategori as $kat): ?>
                                                <option value="<?php echo (int)$kat['id']; ?>" 
                                                    <?php echo $kat['id'] == $dokumen['kategori_id'] ? 'selected' : ''; ?>>
                                                    <?php echo e($kat['nama_kategori']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="versi" class="form-label">Versi <span class="text-danger">*</span></label>
                                                <input type="text" 
                                                       id="versi"
                                                       name="versi" 
                                                       class="form-control" 
                                                       value="<?php echo e($dokumen['versi']); ?>" 
                                                       required>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="tanggal_kadaluarsa" class="form-label">Tanggal Kadaluarsa <span class="text-danger">*</span></label>
                                                <input type="date" 
                                                       id="tanggal_kadaluarsa"
                                                       name="tanggal_kadaluarsa" 
                                                       class="form-control" 
                                                       value="<?php echo e($dokumen['tanggal_kadaluarsa']); ?>" 
                                                       required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                            <select id="status" name="status" class="form-select" required>
                                                <option value="aktif" <?php echo $dokumen['status'] === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                                <option value="kadaluarsa" <?php echo $dokumen['status'] === 'kadaluarsa' ? 'selected' : ''; ?>>Kadaluarsa</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">File Saat Ini</label>
                                            <div class="file-info">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-file-earmark-text fs-3 me-3 text-primary"></i>
                                                    <div>
                                                        <h6 class="mb-1"><?php echo e(basename($dokumen['nama_file'])); ?></h6>
                                                        <a href="download.php?id=<?php echo (int)$dokumen['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           target="_blank">
                                                            <i class="bi bi-download me-1"></i>Download File
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-text text-muted mt-2">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Untuk mengganti file, hapus dokumen ini dan upload baru.
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                            <a href="list.php" class="btn btn-secondary">
                                                <i class="bi bi-x-circle me-2"></i>Batal
                                            </a>
                                            <button type="submit" class="btn btn-warning">
                                                <i class="bi bi-save me-2"></i>Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>