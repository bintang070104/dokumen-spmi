<?php
session_start();
require_once '../config/database.php';

// Proteksi - hanya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Ambil ID dari URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: list.php?error=ID kategori tidak valid");
    exit();
}

// Ambil data kategori yang mau diedit
$kategori = db_select("SELECT * FROM kategori WHERE id = ?", [$id]);

if (empty($kategori)) {
    header("Location: list.php?error=Kategori tidak ditemukan");
    exit();
}

$data = $kategori[0];
$error = '';
$success = '';

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    
    if (empty($nama_kategori)) {
        $error = 'Nama kategori wajib diisi!';
    } elseif (strlen($nama_kategori) > 100) {
        $error = 'Nama kategori maksimal 100 karakter!';
    } elseif ($nama_kategori === $data['nama_kategori']) {
        $success = 'Tidak ada perubahan data.';
    } else {
        // Cek duplikat (selain dirinya sendiri)
        $check = db_select("SELECT id FROM kategori WHERE nama_kategori = ? AND id != ?", [$nama_kategori, $id]);
        
        if (!empty($check)) {
            $error = 'Kategori "' . e($nama_kategori) . '" sudah ada!';
        } else {
            $update = db_update('kategori', ['nama_kategori' => $nama_kategori], 'id = ?', [$id]);
            
            if ($update) {
                header("Location: list.php?success=Kategori berhasil diperbarui");
                exit();
            } else {
                $error = 'Gagal memperbarui kategori!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kategori - SPMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
            border-radius: 8px;
        }

        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
            color: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 12px 20px;
            border-radius: 0 25px 25px 0;
            margin-right: 12px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }

        .sidebar .nav-link i {
            font-size: 1.1rem;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-4 text-center border-bottom border-light border-opacity-25">
                    <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo mb-2">
                    <h5 class="mb-1 fw-bold">SPMI System</h5>
                    <small class="opacity-75">Admin Panel</small>
                </div>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/admin.php">
                            <i class="bi bi-speedometer2 me-3"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../dokumen/list.php">
                            <i class="bi bi-folder me-3"></i> Daftar Dokumen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../users/list.php">
                            <i class="bi bi-people me-3"></i> Kelola User
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../kategori/list.php">
                            <i class="bi bi-tags me-3"></i> Kelola Kategori
                        </a>
                    </li>
                    <li class="nav-item mt-auto">
                        <a class="nav-link text-danger" href="../auth/logout.php">
                            <i class="bi bi-box-arrow-right me-3"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-0">
                <nav class="navbar navbar-expand-lg p-3">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1">Edit Kategori</span>
                    </div>
                </nav>

                <div class="p-4">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-pencil-square me-2"></i>
                                        Edit Kategori: <?php echo e($data['nama_kategori']); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($error)): ?>
                                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($success)): ?>
                                        <div class="alert alert-info"><?php echo $success; ?></div>
                                    <?php endif; ?>

                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="nama_kategori" class="form-label">Nama Kategori</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="nama_kategori" 
                                                   name="nama_kategori" 
                                                   maxlength="100"
                                                   value="<?php echo e($data['nama_kategori']); ?>"
                                                   required 
                                                   placeholder="Masukkan nama kategori">
                                            <div class="form-text">Maksimal 100 karakter</div>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save me-2"></i>Simpan Perubahan
                                            </button>
                                            <a href="list.php" class="btn btn-secondary">
                                                <i class="bi bi-arrow-left me-2"></i>Kembali
                                            </a>
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