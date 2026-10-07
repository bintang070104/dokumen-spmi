<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman - hanya operator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
    header("Location: ../auth/login.php");
    exit();
}

// Fungsi helper untuk escaping output
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

$user_id = intval($_SESSION['user_id']);
$error = '';

// Statistik - pakai db_select (prepared statement)
$stats = db_select("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif,
    SUM(CASE WHEN status = 'kadaluarsa' THEN 1 ELSE 0 END) as kadaluarsa
FROM dokumen WHERE uploaded_by = ?", [$user_id]);

if (!empty($stats)) {
    $total_dokumen = $stats[0]['total'] ?? 0;
    $dokumen_aktif = $stats[0]['aktif'] ?? 0;
    $dokumen_kadaluarsa = $stats[0]['kadaluarsa'] ?? 0;
} else {
    $total_dokumen = $dokumen_aktif = $dokumen_kadaluarsa = 0;
}

// Dokumen terbaru - pakai db_select (prepared statement)
$recent = db_select("SELECT d.*, k.nama_kategori 
                     FROM dokumen d 
                     JOIN kategori k ON d.kategori_id = k.id 
                     WHERE d.uploaded_by = ?
                     ORDER BY d.created_at DESC 
                     LIMIT 5", [$user_id]);

if ($recent === false) {
    $recent = [];
    $error = 'Gagal memuat data dokumen';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Operator - SPMI</title>
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
            background: rgba(255,255,255,0.15);
        }
        .stat-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table td {
            vertical-align: middle;
        }
   .sidebar-logo {
    width: 60px;
    height: 60px;
    object-fit: contain;
    display: block;
    margin: 0 auto;
}
</style>
</head>

<body>

<div class="container-fluid">
<div class="row">

<!-- Sidebar -->
<div class="col-md-2 sidebar p-0">

<div class="p-3 text-center border-bottom">
<!-- Tambahkan logo di sini -->
<img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo mb-2">
<h5>SPMI System</h5>
<small>operator Panel</small>
</div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="operator.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../dokumen/upload.php">
                            <i class="bi bi-upload me-2"></i> Upload Dokumen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../dokumen/list.php">
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
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg p-3">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1">Dashboard Operator</span>
                        <div class="d-flex align-items-center">
                            <span class="me-3">Selamat datang, <strong><?php echo e($_SESSION['nama'] ?? 'Operator'); ?></strong></span>
                        </div>
                    </div>
                </nav>

                <!-- Content -->
                <div class="p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo e($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <h4 class="mb-4">Statistik Dokumen Saya</h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card stat-card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Total Dokumen</h6>
                                            <h3><?php echo (int)$total_dokumen; ?></h3>
                                        </div>
                                        <i class="bi bi-files fs-1 opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Dokumen Aktif</h6>
                                            <h3><?php echo (int)$dokumen_aktif; ?></h3>
                                        </div>
                                        <i class="bi bi-check-circle fs-1 opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Dokumen Kadaluarsa</h6>
                                            <h3><?php echo (int)$dokumen_kadaluarsa; ?></h3>
                                        </div>
                                        <i class="bi bi-exclamation-triangle fs-1 opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Aksi Cepat</h5>
                                </div>
                                <div class="card-body">
                                    <a href="../dokumen/upload.php" class="btn btn-success me-2">
                                        <i class="bi bi-upload me-2"></i>Upload Dokumen Baru
                                    </a>
                                    <a href="../dokumen/list.php" class="btn btn-primary">
                                        <i class="bi bi-list me-2"></i>Lihat Semua Dokumen
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Documents -->
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Dokumen Terbaru Saya</h5>
                            <a href="../dokumen/list.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Judul Dokumen</th>
                                            <th>Kategori</th>
                                            <th width="10%">Versi</th>
                                            <th width="12%">Status</th>
                                            <th width="15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recent)): ?>
                                            <?php $no = 1; foreach ($recent as $row): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo e($row['judul_dokumen']); ?></td>
                                                    <td><?php echo e($row['nama_kategori']); ?></td>
                                                    <td><?php echo e($row['versi']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $badge_class = ($row['status'] === 'aktif') ? 'success' : 'warning';
                                                        $status_text = ucfirst(e($row['status']));
                                                        ?>
                                                        <span class="badge bg-<?php echo $badge_class; ?>">
                                                            <?php echo $status_text; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="../dokumen/edit.php?id=<?php echo (int)$row['id']; ?>" 
                                                               class="btn btn-sm btn-warning" 
                                                               title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="../dokumen/download.php?id=<?php echo (int)$row['id']; ?>" 
                                                               class="btn btn-sm btn-info" 
                                                               title="Download">
                                                                <i class="bi bi-download"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                                    <p class="text-muted mt-2 mb-0">Belum ada dokumen</p>
                                                    <a href="../dokumen/upload.php" class="btn btn-sm btn-success mt-2">
                                                        <i class="bi bi-upload me-1"></i>Upload sekarang
                                                    </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>