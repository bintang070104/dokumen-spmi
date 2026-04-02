<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman - hanya pimpinan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') {
    header("Location: ../auth/login.php");
    exit();
}

// Fungsi helper
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Hitung statistik dengan prepared statement
$stats = db_select_one("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif,
    SUM(CASE WHEN status = 'kadaluarsa' THEN 1 ELSE 0 END) as kadaluarsa
FROM dokumen");

$total_dokumen = $stats['total'] ?? 0;
$dokumen_aktif = $stats['aktif'] ?? 0;
$dokumen_kadaluarsa = $stats['kadaluarsa'] ?? 0;

// Ambil dokumen terbaru
$recent_dokumen = db_select("SELECT d.*, k.nama_kategori, u.nama as uploader 
                             FROM dokumen d 
                             JOIN kategori k ON d.kategori_id = k.id 
                             JOIN users u ON d.uploaded_by = u.id 
                             ORDER BY d.created_at DESC 
                             LIMIT 10");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pimpinan - SPMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #8e44ad;
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 text-center border-bottom">
                    <h5>SPMI System</h5>
                    <small>Pimpinan Panel</small>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="pimpinan.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
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
                        <span class="navbar-brand mb-0 h1">Dashboard Pimpinan</span>
                        <div class="d-flex align-items-center">
                            <span class="me-3">Selamat datang, <strong><?php echo e($_SESSION['nama'] ?? 'Pimpinan'); ?></strong></span>
                        </div>
                    </div>
                </nav>

                <!-- Content -->
                <div class="p-4">
                    <h4 class="mb-4">Overview Dokumen Penjaminan Mutu</h4>
                    
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

                    <!-- All Documents -->
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Dokumen Terbaru</h5>
                            <a href="../dokumen/list.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Judul Dokumen</th>
                                            <th>Kategori</th>
                                            <th width="8%">Versi</th>
                                            <th width="10%">Status</th>
                                            <th>Uploader</th>
                                            <th width="15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recent_dokumen)): ?>
                                            <?php $no = 1; foreach ($recent_dokumen as $row): ?>
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
                                                <td><?php echo e($row['uploader']); ?></td>
                                                <td>
                                                    <a href="../dokumen/download.php?id=<?php echo (int)$row['id']; ?>" 
                                                       class="btn btn-sm btn-success" 
                                                       title="Download">
                                                        <i class="bi bi-download me-1"></i>Download
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                                    <p class="text-muted mt-2">Belum ada dokumen dalam sistem</p>
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