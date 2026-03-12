<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Cek akses
$dokumen = query("SELECT * FROM dokumen WHERE id = $id")->fetch_assoc();
if (!$dokumen) {
    header("Location: list.php");
    exit();
}

// Hanya admin atau pemilik dokumen yang bisa edit
if ($role != 'admin' && $dokumen['uploaded_by'] != $user_id) {
    header("Location: list.php");
    exit();
}

// Ambil data kategori
$kategori = query("SELECT * FROM kategori ORDER BY nama_kategori");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = escape($_POST['judul_dokumen']);
    $kategori_id = escape($_POST['kategori_id']);
    $versi = escape($_POST['versi']);
    $tanggal_kadaluarsa = escape($_POST['tanggal_kadaluarsa']);
    $status = escape($_POST['status']);
    
    $sql = "UPDATE dokumen SET 
            judul_dokumen = '$judul',
            kategori_id = '$kategori_id',
            versi = '$versi',
            tanggal_kadaluarsa = '$tanggal_kadaluarsa',
            status = '$status'
            WHERE id = $id";
    
    if (query($sql)) {
        header("Location: list.php?success=Dokumen berhasil diupdate");
        exit();
    } else {
        $error = "Gagal mengupdate dokumen.";
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
            background: #27ae60;
            color: white;
        }
        .sidebar .nav-link {
            color: white;
            padding: 15px 20px;
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
                    <small><?php echo ucfirst($role); ?> Panel</small>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/<?php echo $role; ?>.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <?php if ($role == 'operator'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="upload.php">
                            <i class="bi bi-upload me-2"></i> Upload Dokumen
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="list.php">
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
                <nav class="navbar navbar-expand-lg navbar-light bg-light p-3">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1">Edit Dokumen</span>
                    </div>
                </nav>

                <div class="p-4">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Form Edit Dokumen</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($error)): ?>
                                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                    <?php endif; ?>

                                    <form action="" method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Judul Dokumen</label>
                                            <input type="text" name="judul_dokumen" class="form-control" value="<?php echo $dokumen['judul_dokumen']; ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Kategori</label>
                                            <select name="kategori_id" class="form-select" required>
                                                <?php 
                                                $kategori = query("SELECT * FROM kategori ORDER BY nama_kategori");
                                                while ($kat = $kategori->fetch_assoc()): 
                                                ?>
                                                <option value="<?php echo $kat['id']; ?>" <?php echo $kat['id'] == $dokumen['kategori_id'] ? 'selected' : ''; ?>>
                                                    <?php echo $kat['nama_kategori']; ?>
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Versi</label>
                                            <input type="text" name="versi" class="form-control" value="<?php echo $dokumen['versi']; ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Kadaluarsa</label>
                                            <input type="date" name="tanggal_kadaluarsa" class="form-control" value="<?php echo $dokumen['tanggal_kadaluarsa']; ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select" required>
                                                <option value="aktif" <?php echo $dokumen['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                                <option value="kadaluarsa" <?php echo $dokumen['status'] == 'kadaluarsa' ? 'selected' : ''; ?>>Kadaluarsa</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">File Saat Ini</label>
                                            <p class="form-control-static">
                                                <a href="../uploads/<?php echo $dokumen['nama_file']; ?>" target="_blank">
                                                    <?php echo $dokumen['nama_file']; ?>
                                                </a>
                                            </p>
                                            <small class="text-muted">Untuk mengganti file, hapus dokumen ini dan upload baru.</small>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-warning">
                                                <i class="bi bi-save me-2"></i>Simpan Perubahan
                                            </button>
                                            <a href="list.php" class="btn btn-secondary">Batal</a>
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