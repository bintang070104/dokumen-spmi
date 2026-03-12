<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Query berdasarkan role
if ($role == 'admin') {
    $sql = "SELECT d.*, k.nama_kategori, u.nama as uploader 
            FROM dokumen d 
            JOIN kategori k ON d.kategori_id = k.id 
            JOIN users u ON d.uploaded_by = u.id 
            ORDER BY d.created_at DESC";
} elseif ($role == 'operator') {
    $sql = "SELECT d.*, k.nama_kategori, u.nama as uploader 
            FROM dokumen d 
            JOIN kategori k ON d.kategori_id = k.id 
            JOIN users u ON d.uploaded_by = u.id 
            WHERE d.uploaded_by = $user_id
            ORDER BY d.created_at DESC";
} else {
    $sql = "SELECT d.*, k.nama_kategori, u.nama as uploader 
            FROM dokumen d 
            JOIN kategori k ON d.kategori_id = k.id 
            JOIN users u ON d.uploaded_by = u.id 
            ORDER BY d.created_at DESC";
}

$result = query($sql);
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
        .sidebar .nav-link:hover {
            opacity: 0.8;
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
            <div class="col-md-2 sidebar p-0 sidebar-<?php echo $role; ?>">
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
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_GET['success']; ?>
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
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>No</th>
                                            <th>Judul</th>
                                            <th>Kategori</th>
                                            <th>Versi</th>
                                            <th>Status</th>
                                            <th>Tanggal Upload</th>
                                            <th>Tanggal Kadaluarsa</th>
                                            <th>Uploader</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo $row['judul_dokumen']; ?></td>
                                            <td><?php echo $row['nama_kategori']; ?></td>
                                            <td><?php echo $row['versi']; ?></td>
                                            <td>
                                                <?php 
                                                $badge_class = $row['status'] == 'aktif' ? 'success' : 'warning';
                                                $kadaluarsa = strtotime($row['tanggal_kadaluarsa']) < time();
                                                if ($kadaluarsa && $row['status'] == 'aktif') {
                                                    $badge_class = 'danger';
                                                    echo '<span class="badge bg-danger">Perlu Update</span>';
                                                } else {
                                                    echo '<span class="badge bg-' . $badge_class . '">' . ucfirst($row['status']) . '</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo date('d-m-Y', strtotime($row['tanggal_upload'])); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($row['tanggal_kadaluarsa'])); ?></td>
                                            <td><?php echo $row['uploader']; ?></td>
                                            <td>
                                                <a href="download.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Download">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <?php if ($role == 'operator' && $row['uploaded_by'] == $user_id): ?>
                                                <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($role == 'admin'): ?>
                                                <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if ($result->num_rows == 0): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">Tidak ada dokumen</td>
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