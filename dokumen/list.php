<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = intval($_SESSION['user_id']);

// Fungsi helper untuk escaping output
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Inisialisasi variabel
$result = [];
$error = '';

try {
    // Gunakan db_select untuk prepared statements
    if ($role == 'admin') {
        $result = db_select("SELECT d.*, k.nama_kategori, u.nama as uploader 
                            FROM dokumen d 
                            JOIN kategori k ON d.kategori_id = k.id 
                            JOIN users u ON d.uploaded_by = u.id 
                            ORDER BY d.created_at DESC");
    } elseif ($role == 'operator') {
        $result = db_select("SELECT d.*, k.nama_kategori, u.nama as uploader 
                            FROM dokumen d 
                            JOIN kategori k ON d.kategori_id = k.id 
                            JOIN users u ON d.uploaded_by = u.id 
                            WHERE d.uploaded_by = ?
                            ORDER BY d.created_at DESC", [$user_id]);
    } else {
        // Role pimpinan atau lainnya
        $result = db_select("SELECT d.*, k.nama_kategori, u.nama as uploader 
                            FROM dokumen d 
                            JOIN kategori k ON d.kategori_id = k.id 
                            JOIN users u ON d.uploaded_by = u.id 
                            ORDER BY d.created_at DESC");
    }
    
    if ($result === false) {
        throw new Exception("Gagal mengambil data dokumen");
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Fungsi untuk mengecek status kadaluarsa
function getStatusBadge($row) {
    $status = $row['status'];
    $tanggal_kadaluarsa = $row['tanggal_kadaluarsa'];
    
    // Cek apakah tanggal valid
    if (empty($tanggal_kadaluarsa) || $tanggal_kadaluarsa == '0000-00-00') {
        $badge_class = $status == 'aktif' ? 'success' : 'warning';
        return '<span class="badge bg-' . $badge_class . '">' . ucfirst(e($status)) . '</span>';
    }
    
    $kadaluarsa_timestamp = strtotime($tanggal_kadaluarsa);
    $sekarang = time();
    
    if ($kadaluarsa_timestamp !== false && $kadaluarsa_timestamp < $sekarang && $status == 'aktif') {
        return '<span class="badge bg-danger">Perlu Update</span>';
    } else {
        $badge_class = $status == 'aktif' ? 'success' : 'warning';
        return '<span class="badge bg-' . $badge_class . '">' . ucfirst(e($status)) . '</span>';
    }
}

// Fungsi format tanggal aman
function formatTanggal($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') {
        return '-';
    }
    $timestamp = strtotime($tanggal);
    return $timestamp !== false ? date('d-m-Y', $timestamp) : '-';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Dokumen - SPMI</title>
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
        .table td {
            vertical-align: middle;
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
                    <?php if ($role == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../users/list.php">
                            <i class="bi bi-people me-2"></i> Kelola User
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../kategori/list.php">
                            <i class="bi bi-tags me-2"></i> Kelola Kategori
                        </a>
                    </li>
                    <?php endif; ?>
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
                        <span class="navbar-brand mb-0 h1">Daftar Dokumen</span>
                    </div>
                </nav>

                <div class="p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo e($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo e($_GET['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Semua Dokumen</h5>
                            <?php if ($role == 'operator'): ?>
                            <a href="upload.php" class="btn btn-success btn-sm">
                                <i class="bi bi-plus me-1"></i>Tambah Dokumen
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Judul</th>
                                            <th>Kategori</th>
                                            <th width="8%">Versi</th>
                                            <th width="12%">Status</th>
                                            <th width="12%">Tanggal Upload</th>
                                            <th width="12%">Tanggal Kadaluarsa</th>
                                            <th>Uploader</th>
                                            <th width="15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                       <?php if (!empty($result)): ?>
                                           <?php $no = 1; foreach ($result as $row): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo e($row['judul_dokumen']); ?></td>
                                                <td><?php echo e($row['nama_kategori']); ?></td>
                                                <td><?php echo e($row['versi']); ?></td>
                                                <td><?php echo getStatusBadge($row); ?></td>
                                                <td><?php echo formatTanggal($row['tanggal_upload']); ?></td>
                                                <td><?php echo formatTanggal($row['tanggal_kadaluarsa']); ?></td>
                                                <td><?php echo e($row['uploader']); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="download.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-info" title="Download">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                        <?php if ($role == 'operator' && $row['uploaded_by'] == $user_id): ?>
                                                        <a href="edit.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                        <?php if ($role == 'admin'): ?>
                                                        <a href="delete.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus dokumen <?php echo e(addslashes($row['judul_dokumen'])); ?>?')" title="Hapus">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                                    <p class="text-muted mt-2">Tidak ada dokumen</p>
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