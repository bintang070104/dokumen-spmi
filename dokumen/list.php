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

// Fungsi helper
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Ambil parameter filter & search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Ambil daftar kategori untuk dropdown filter
$kategori_list = db_select("SELECT * FROM kategori ORDER BY nama_kategori ASC");

// Build query dengan filter
$params = [];
$where_clauses = [];

if ($role == 'operator') {
    $where_clauses[] = "d.uploaded_by = ?";
    $params[] = $user_id;
}

if (!empty($search)) {
    $where_clauses[] = "d.judul_dokumen LIKE ?";
    $params[] = "%$search%";
}

if ($kategori_filter > 0) {
    $where_clauses[] = "d.kategori_id = ?";
    $params[] = $kategori_filter;
}

if (!empty($status_filter) && in_array($status_filter, ['aktif', 'kadaluarsa'])) {
    $where_clauses[] = "d.status = ?";
    $params[] = $status_filter;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Query utama dengan filter - TANPA k.ikon
$sql = "SELECT d.*, k.nama_kategori, u.nama as uploader 
        FROM dokumen d 
        JOIN kategori k ON d.kategori_id = k.id 
        JOIN users u ON d.uploaded_by = u.id 
        $where_sql
        ORDER BY d.created_at DESC";

$result = !empty($params) ? db_select($sql, $params) : db_select($sql);

// Fungsi status badge
function getStatusBadge($row) {
    $status = $row['status'];
    $tanggal_kadaluarsa = $row['tanggal_kadaluarsa'];
    
    if (empty($tanggal_kadaluarsa) || $tanggal_kadaluarsa == '0000-00-00') {
        $badge_class = $status == 'aktif' ? 'success' : 'secondary';
        return '<span class="badge bg-' . $badge_class . ' rounded-pill">' . ucfirst(e($status)) . '</span>';
    }
    
    $kadaluarsa = strtotime($tanggal_kadaluarsa);
    $sekarang = time();
    
    if ($kadaluarsa !== false && $kadaluarsa < $sekarang && $status == 'aktif') {
        return '<span class="badge bg-danger rounded-pill"><i class="bi bi-exclamation-circle me-1"></i>Perlu Update</span>';
    } else {
        $badge_class = $status == 'aktif' ? 'success' : 'secondary';
        $ikon = $status == 'aktif' ? 'bi-check-circle' : 'bi-clock-history';
        return '<span class="badge bg-' . $badge_class . ' rounded-pill"><i class="bi ' . $ikon . ' me-1"></i>' . ucfirst(e($status)) . '</span>';
    }
}

// Fungsi format tanggal
function formatTanggal($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') return '-';
    $timestamp = strtotime($tanggal);
    return $timestamp !== false ? date('d M Y', $timestamp) : '-';
}

// Fungsi ikon file
function getFileIcon($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $icons = [
        'pdf' => 'bi-file-earmark-pdf text-danger',
        'doc' => 'bi-file-earmark-word text-primary',
        'docx' => 'bi-file-earmark-word text-primary'
    ];
    return $icons[strtolower($ext)] ?? 'bi-file-earmark-text text-secondary';
}

// Hitung total hasil
$total_results = count($result);
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
        :root {
            --primary-color: #1a73e8;
            --hover-bg: #f1f3f4;
            --border-color: #dadce0;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            color: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar-admin { background: #2c3e50; }
        .sidebar-operator { background: #27ae60; }
        .sidebar-pimpinan { background: #8e44ad; }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 12px 20px;
            border-radius: 0 25px 25px 0;
            margin-right: 12px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }
        .sidebar .nav-link i {
            font-size: 1.1rem;
        }
        
        .main-header {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .search-box {
            position: relative;
            max-width: 600px;
        }
        .search-box input {
            border-radius: 24px;
            border: 1px solid var(--border-color);
            padding-left: 45px;
            background: #f1f3f4;
            transition: all 0.3s;
        }
        .search-box input:focus {
            background: white;
            box-shadow: 0 1px 6px rgba(32,33,36,.28);
            border-color: transparent;
        }
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #5f6368;
        }
        
        .filter-bar {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .drive-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
        }
        
        .drive-table th {
            border-bottom: 1px solid var(--border-color);
            color: #5f6368;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 12px 16px;
            white-space: nowrap;
        }
        
        .drive-table td {
            padding: 12px 16px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }
        
        .drive-table tbody tr:hover {
            background-color: var(--hover-bg);
        }
        
        .file-icon {
            width: 40px;
            height: 40px;
            background: #f1f3f4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }
        
        .file-name {
            color: #202124;
            font-weight: 500;
            text-decoration: none;
        }
        .file-name:hover {
            color: var(--primary-color);
        }
        
        .action-btn {
            opacity: 0;
            transition: opacity 0.2s;
        }
        .drive-table tbody tr:hover .action-btn {
            opacity: 1;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        .empty-state i {
            font-size: 4rem;
            color: #dadce0;
            margin-bottom: 1rem;
        }
        
        .filter-chip {
            background: #e8f0fe;
            color: var(--primary-color);
            border-radius: 16px;
            padding: 4px 12px;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-right: 8px;
        }
        .filter-chip a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .action-btn { opacity: 1; }
            .search-box { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0 sidebar-<?php echo e($role); ?>">
                <div class="p-4 text-center border-bottom border-light border-opacity-25">
                    <h5 class="mb-1 fw-bold">SPMI System</h5>
                    <small class="opacity-75"><?php echo e(ucfirst($role)); ?> Panel</small>
                </div>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/<?php echo e($role); ?>.php">
                            <i class="bi bi-speedometer2 me-3"></i> Dashboard
                        </a>
                    </li>
                    <?php if ($role == 'operator'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="upload.php">
                            <i class="bi bi-cloud-upload me-3"></i> Upload Dokumen
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="list.php">
                            <i class="bi bi-folder me-3"></i> Daftar Dokumen
                        </a>
                    </li>
                    <?php if ($role == 'admin'): ?>
                    <li class="nav-item mt-3">
                        <small class="text-white-50 px-4 text-uppercase" style="font-size: 0.75rem;">Admin Menu</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../users/list.php">
                            <i class="bi bi-people me-3"></i> Kelola User
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../kategori/list.php">
                            <i class="bi bi-tags me-3"></i> Kelola Kategori
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item mt-auto">
                        <a class="nav-link text-danger" href="../auth/logout.php">
                            <i class="bi bi-box-arrow-right me-3"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-0">
                <!-- Header -->
                <div class="main-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 fw-normal text-secondary">Daftar Dokumen</h4>
                    <?php if ($role == 'operator'): ?>
                    <a href="upload.php" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-plus-lg me-2"></i>Baru
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <form method="GET" action="" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <div class="search-box">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" class="form-control" placeholder="Cari dokumen..." 
                                       value="<?php echo e($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="kategori" class="form-select rounded-pill">
                                <option value="">Semua Kategori</option>
                                <?php foreach ($kategori_list as $kat): ?>
                                <option value="<?php echo (int)$kat['id']; ?>" 
                                    <?php echo $kategori_filter == $kat['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($kat['nama_kategori']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select rounded-pill">
                                <option value="">Semua Status</option>
                                <option value="aktif" <?php echo $status_filter == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="kadaluarsa" <?php echo $status_filter == 'kadaluarsa' ? 'selected' : ''; ?>>Kadaluarsa</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-outline-secondary w-100 rounded-pill">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                        </div>
                    </form>

                    <!-- Active Filters -->
                    <?php if (!empty($search) || $kategori_filter > 0 || !empty($status_filter)): ?>
                    <div class="mt-3">
                        <small class="text-muted me-2">Filter aktif:</small>
                        <?php if (!empty($search)): ?>
                        <span class="filter-chip">
                            <i class="bi bi-search"></i> "<?php echo e($search); ?>"
                            <a href="?<?php echo http_build_query(array_diff_key($_GET, ['search' => ''])); ?>"><i class="bi bi-x-circle"></i></a>
                        </span>
                        <?php endif; ?>
                        <?php if ($kategori_filter > 0): 
                            $kat_name = '';
                            foreach ($kategori_list as $k) {
                                if ($k['id'] == $kategori_filter) $kat_name = $k['nama_kategori'];
                            }
                        ?>
                        <span class="filter-chip">
                            <i class="bi bi-folder"></i> <?php echo e($kat_name); ?>
                            <a href="?<?php echo http_build_query(array_diff_key($_GET, ['kategori' => ''])); ?>"><i class="bi bi-x-circle"></i></a>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($status_filter)): ?>
                        <span class="filter-chip">
                            <i class="bi bi-check-circle"></i> <?php echo ucfirst($status_filter); ?>
                            <a href="?<?php echo http_build_query(array_diff_key($_GET, ['status' => ''])); ?>"><i class="bi bi-x-circle"></i></a>
                        </span>
                        <?php endif; ?>
                        <a href="list.php" class="btn btn-sm btn-link text-decoration-none">Reset semua</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Content -->
                <div class="p-4">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo e($_GET['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="drive-table">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 40%;">Nama</th>
                                        <th>Owner</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th>Tanggal Upload</th>
                                        <th style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($result)): ?>
                                        <?php foreach ($result as $row): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="file-icon">
                                                        <i class="bi <?php echo getFileIcon($row['nama_file']); ?> fs-4"></i>
                                                    </div>
                                                    <div>
                                                        <a href="download.php?id=<?php echo (int)$row['id']; ?>" class="file-name d-block">
                                                            <?php echo e($row['judul_dokumen']); ?>
                                                        </a>
                                                        <small class="text-muted">v<?php echo e($row['versi']); ?> • <?php echo e(pathinfo($row['nama_file'], PATHINFO_EXTENSION)); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 28px; height: 28px; font-size: 0.75rem;">
                                                        <?php echo strtoupper(substr($row['uploader'], 0, 1)); ?>
                                                    </div>
                                                    <span><?php echo e($row['uploader']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="bi bi-folder me-1"></i><?php echo e($row['nama_kategori']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo getStatusBadge($row); ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo formatTanggal($row['tanggal_upload']); ?><br>
                                                    <span class="text-danger"><i class="bi bi-calendar-x me-1"></i>Exp: <?php echo formatTanggal($row['tanggal_kadaluarsa']); ?></span>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="action-btn btn-group">
                                                    <a href="download.php?id=<?php echo (int)$row['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary rounded-circle" 
                                                       style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;"
                                                       title="Download">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                    <?php if ($role == 'operator' && $row['uploaded_by'] == $user_id): ?>
                                                    <a href="edit.php?id=<?php echo (int)$row['id']; ?>" 
                                                       class="btn btn-sm btn-outline-warning rounded-circle ms-1" 
                                                       style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;"
                                                       title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php if ($role == 'admin'): ?>
                                                    <a href="delete.php?id=<?php echo (int)$row['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger rounded-circle ms-1" 
                                                       style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;"
                                                       onclick="return confirm('Yakin ingin menghapus dokumen ini?')" 
                                                       title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="border-0">
                                                <div class="empty-state">
                                                    <i class="bi bi-folder-x"></i>
                                                    <h5 class="text-muted">Tidak ada dokumen ditemukan</h5>
                                                    <p class="text-muted mb-3">
                                                        <?php if (!empty($search) || $kategori_filter > 0): ?>
                                                        Coba ubah filter pencarian Anda
                                                        <?php else: ?>
                                                        Belum ada dokumen yang diupload
                                                        <?php endif; ?>
                                                    </p>
                                                    <?php if ($role == 'operator' && empty($search)): ?>
                                                    <a href="upload.php" class="btn btn-primary rounded-pill">
                                                        <i class="bi bi-cloud-upload me-2"></i>Upload Dokumen Pertama
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_results > 0): ?>
                        <div class="p-3 border-top text-muted small">
                            Menampilkan <?php echo $total_results; ?> dokumen
                            <?php if (!empty($search) || $kategori_filter > 0 || !empty($status_filter)): ?>
                            (difilter dari total)
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto submit on search enter
        document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    </script>
</body>
</html>