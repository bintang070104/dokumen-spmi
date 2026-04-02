<?php
session_start();
require_once '../config/database.php';

// Proteksi - hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Fungsi helper untuk escaping output
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

$error = '';
$success = '';

// Tambah kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    
    // Validasi input
    if (empty($nama_kategori)) {
        $error = 'Nama kategori wajib diisi!';
    } elseif (strlen($nama_kategori) > 100) {
        $error = 'Nama kategori maksimal 100 karakter!';
    } else {
        // Cek duplikat dengan prepared statement
        $check = db_select("SELECT id FROM kategori WHERE nama_kategori = ?", [$nama_kategori]);
        
        if (!empty($check)) {
            $error = 'Kategori "' . e($nama_kategori) . '" sudah ada!';
        } else {
            // Insert dengan prepared statement
            $insert = db_insert('kategori', ['nama_kategori' => $nama_kategori]);
            
            if ($insert) {
                header("Location: list.php?success=Kategori berhasil ditambahkan");
                exit();
            } else {
                $error = 'Gagal menambahkan kategori!';
            }
        }
    }
}

// Hapus kategori
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Validasi ID
    if ($id <= 0) {
        header("Location: list.php?error=ID tidak valid");
        exit();
    }
    
    // Cek apakah kategori sedang digunakan di tabel dokumen
    $cek_penggunaan = db_select("SELECT COUNT(*) as total FROM dokumen WHERE kategori_id = ?", [$id]);
    
    if (!empty($cek_penggunaan) && $cek_penggunaan[0]['total'] > 0) {
        header("Location: list.php?error=Kategori tidak bisa dihapus karena masih digunakan oleh " . $cek_penggunaan[0]['total'] . " dokumen");
        exit();
    }
    
    // Hapus dengan prepared statement
    $delete = db_delete('kategori', 'id = ?', [$id]);
    
    if ($delete) {
        header("Location: list.php?success=Kategori berhasil dihapus");
        exit();
    } else {
        header("Location: list.php?error=Gagal menghapus kategori");
        exit();
    }
}

// Ambil data kategori
$kategori = db_select("SELECT * FROM kategori ORDER BY nama_kategori ASC");

// Fallback jika query gagal
if ($kategori === false) {
    $kategori = [];
}

// Ambil pesan dari URL
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - SPMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
            color: white;
        }
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 text-center border-bottom">
                    <h5>SPMI System</h5>
                    <small>Admin Panel</small>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/admin.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../dokumen/list.php">
                            <i class="bi bi-folder me-2"></i> Daftar Dokumen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../users/list.php">
                            <i class="bi bi-people me-2"></i> Kelola User
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../kategori/list.php">
                            <i class="bi bi-tags me-2"></i> Kelola Kategori
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
                        <span class="navbar-brand mb-0 h1">Kelola Kategori</span>
                    </div>
                </nav>

                <div class="p-4">
                    <!-- Alert -->
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo e($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo e($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Form Tambah Kategori -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Tambah Kategori</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="tambah">
                                        <div class="mb-3">
                                            <label for="nama_kategori" class="form-label">Nama Kategori</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="nama_kategori" 
                                                   name="nama_kategori" 
                                                   maxlength="100"
                                                   value="<?php echo isset($_POST['nama_kategori']) ? e($_POST['nama_kategori']) : ''; ?>"
                                                   required 
                                                   placeholder="Masukkan nama kategori">
                                            <div class="form-text">Maksimal 100 karakter</div>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-save me-2"></i>Simpan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel Kategori -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Kategori</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th width="10%">No</th>
                                                    <th>Nama Kategori</th>
                                                    <th width="20%">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($kategori)): ?>
                                                    <?php $no = 1; foreach ($kategori as $row): ?>
                                                        <tr>
                                                            <td><?php echo $no++; ?></td>
                                                            <td><?php echo e($row['nama_kategori']); ?></td>
                                                            <td>
                                                                <a href="edit.php?id=<?php echo (int)$row['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" 
                                                                   title="Edit">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>
                                                                <a href="?delete=<?php echo (int)$row['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Yakin ingin menghapus kategori &quot;<?php echo e(addslashes($row['nama_kategori'])); ?>&quot;?')"
                                                                   title="Hapus">
                                                                    <i class="bi bi-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center py-4">
                                                            <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                                            <p class="text-muted mt-2">Belum ada kategori</p>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
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