<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman - hanya operator dan admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil data kategori
$kategori = query("SELECT * FROM kategori ORDER BY nama_kategori");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = escape($_POST['judul_dokumen']);
    $kategori_id = escape($_POST['kategori_id']);
    $versi = escape($_POST['versi']);
    $tanggal_kadaluarsa = escape($_POST['tanggal_kadaluarsa']);
    $uploaded_by = $_SESSION['user_id'];
    $tanggal_upload = date('Y-m-d');
    
    // Handle file upload
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($_FILES["file"]["name"]);
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Validasi file type
    $allowed_types = ['pdf', 'doc', 'docx'];
    if (!in_array($file_type, $allowed_types)) {
        $error = "Hanya file PDF, DOC, dan DOCX yang diizinkan.";
    } else {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO dokumen (judul_dokumen, kategori_id, nama_file, versi, tanggal_upload, tanggal_kadaluarsa, status, uploaded_by) 
                    VALUES ('$judul', '$kategori_id', '$file_name', '$versi', '$tanggal_upload', '$tanggal_kadaluarsa', 'aktif', '$uploaded_by')";
            
            if (query($sql)) {
                header("Location: list.php?success=Dokumen berhasil diupload");
                exit();
            } else {
                $error = "Gagal menyimpan data ke database.";
            }
        } else {
            $error = "Gagal mengupload file.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Dokumen - SPMI</title>
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
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #2ecc71;
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
                    <small>Operator Panel</small>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/operator.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="upload.php">
                            <i class="bi bi-upload me-2"></i> Upload Dokumen
                        </a>
                    </li>
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
                <nav class="navbar navbar-expand-lg navbar-light bg-light p-3">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1">Upload Dokumen Baru</span>
                    </div>
                </nav>

                <div class="p-4">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Form Upload Dokumen</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($error)): ?>
                                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                    <?php endif; ?>

                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label class="form-label">Judul Dokumen</label>
                                            <input type="text" name="judul_dokumen" class="form-control" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Kategori</label>
                                            <select name="kategori_id" class="form-select" required>
                                                <option value="">Pilih Kategori</option>
                                                <?php while ($kat = $kategori->fetch_assoc()): ?>
                                                <option value="<?php echo $kat['id']; ?>"><?php echo $kat['nama_kategori']; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Versi</label>
                                            <input type="text" name="versi" class="form-control" value="1.0" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Kadaluarsa</label>
                                            <input type="date" name="tanggal_kadaluarsa" class="form-control" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">File Dokumen (PDF, DOC, DOCX)</label>
                                            <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx" required>
                                            <small class="text-muted">Maksimal 10MB</small>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-upload me-2"></i>Upload Dokumen
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