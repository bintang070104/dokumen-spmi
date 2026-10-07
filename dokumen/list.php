<?php
session_start();
require_once '../algoritma/kmp.php';
require_once '../config/database.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = intval($_SESSION['user_id']);

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Ambil parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$current_folder = isset($_GET['folder']) ? intval($_GET['folder']) : 0;

// Cek apakah sedang mode pencarian global
$is_searching = !empty($search) || $kategori_filter > 0 || !empty($status_filter);

// Ambil daftar kategori
$kategori_list = db_select("SELECT * FROM kategori ORDER BY nama_kategori ASC");

// Ambil breadcrumb
$breadcrumb = [];
if ($current_folder > 0) {
    $folder_id = $current_folder;
    while ($folder_id > 0) {
        $folder = db_select("SELECT * FROM folders WHERE id = ?", [$folder_id]);
        if (!empty($folder)) {
            array_unshift($breadcrumb, $folder[0]);
            $folder_id = $folder[0]['parent_id'];
        } else {
            break;
        }
    }
}

// Ambil subfolder (selalu berdasarkan folder aktif, tidak terpengaruh pencarian)
$folder_where = "WHERE parent_id " . ($current_folder > 0 ? "= ?" : "IS NULL");
$folder_params = $current_folder > 0 ? [$current_folder] : [];
if ($role == 'operator') {
    $folder_where .= " AND created_by = ?";
    $folder_params[] = $user_id;
}
$folders = db_select("SELECT * FROM folders $folder_where ORDER BY nama_folder ASC", $folder_params);

// Build query dokumen
$params = [];
$where_clauses = [];

if ($role == 'operator') {
    $where_clauses[] = "d.uploaded_by = ?";
    $params[] = $user_id;
}

// Batasi folder HANYA kalau tidak sedang searching
if (!$is_searching) {
    if ($current_folder > 0) {
        $where_clauses[] = "d.folder_id = ?";
        $params[] = $current_folder;
    } else {
        $where_clauses[] = "d.folder_id IS NULL";
    }
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

// Query utama - tambah LEFT JOIN folders untuk tahu lokasi
$sql = "SELECT d.*, k.nama_kategori, u.nama as uploader, f.nama_folder, f.id as folder_real_id 
        FROM dokumen d 
        JOIN kategori k ON d.kategori_id = k.id 
        JOIN users u ON d.uploaded_by = u.id 
        LEFT JOIN folders f ON d.folder_id = f.id 
        $where_sql
        ORDER BY d.created_at DESC";

// Ambil data dari database (pre-filtering)
$raw_result = !empty($params) ? db_select($sql, $params) : db_select($sql);

// Jika ada keyword pencarian, filter dengan algoritma KMP
if (!empty($search)) {
    $result = kmp_filter_documents($raw_result, $search);
} else {
    $result = $raw_result;
}

// Fungsi helper
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

function formatTanggal($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') return '-';
    $timestamp = strtotime($tanggal);
    return $timestamp !== false ? date('d M Y', $timestamp) : '-';
}

function getFileIcon($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $icons = [
        'pdf' => 'bi-file-earmark-pdf text-danger',
        'doc' => 'bi-file-earmark-word text-primary',
        'docx' => 'bi-file-earmark-word text-primary',
        'xls' => 'bi-file-earmark-excel text-success',
        'xlsx' => 'bi-file-earmark-excel text-success',
        'ppt' => 'bi-file-earmark-slides text-warning',
        'pptx' => 'bi-file-earmark-slides text-warning',
        'jpg' => 'bi-file-earmark-image text-info',
        'jpeg' => 'bi-file-earmark-image text-info',
        'png' => 'bi-file-earmark-image text-info'
    ];
    return $icons[strtolower($ext)] ?? 'bi-file-earmark-text text-secondary';
}

$total_results = count($result);
$total_folders = count($folders);
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
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }
        .breadcrumb-item.active {
            color: #5f6368;
        }
        
        .folder-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .folder-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: flex-start;
            transition: all 0.2s;
            position: relative;
        }
        .folder-card:hover {
            background: var(--hover-bg);
            box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3);
            border-color: var(--primary-color);
        }
        
        .folder-link {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            flex: 1;
            min-width: 0;
            text-decoration: none;
            color: inherit;
        }
        
        .folder-link i.bi-folder-fill {
            font-size: 2.2rem;
            color: #f4b400;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .folder-info {
            flex: 1;
            min-width: 0;
            padding-right: 4px;
        }
        
        .folder-name {
            font-weight: 500;
            color: #202124;
            font-size: 0.95rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }
        
        .folder-meta {
            font-size: 0.8rem;
            color: #5f6368;
            margin-top: 4px;
        }
        
        /* Menu titik tiga */
        .folder-menu {
            flex-shrink: 0;
        }
        .folder-menu-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: transparent;
            color: #5f6368;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            padding: 0;
        }
        .folder-menu-btn:hover, .folder-menu-btn:focus {
            background: #e8eaed;
            color: #202124;
        }
        .folder-menu .dropdown-menu {
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
            padding: 6px 0;
            min-width: 140px;
        }
        .folder-menu .dropdown-item {
            padding: 8px 16px;
            font-size: 0.875rem;
            color: #202124;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .folder-menu .dropdown-item:hover {
            background: var(--hover-bg);
        }
        .folder-menu .dropdown-item.text-danger:hover {
            background: #fce8e6;
        }
        .folder-menu .dropdown-divider {
            margin: 4px 0;
            border-color: var(--border-color);
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
        
        .section-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #5f6368;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            margin-top: 8px;
        }
        
        .search-global-badge {
            background: #e8f0fe;
            color: var(--primary-color);
            border-radius: 16px;
            padding: 6px 14px;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        @media (max-width: 768px) {
            .action-btn { opacity: 1; }
            .search-box { max-width: 100%; }
            .folder-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
        }
        
        .sidebar-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0 sidebar-<?php echo e($role); ?>">
                <div class="p-4 text-center border-bottom border-light border-opacity-25">
                    <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo mb-2">
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
                        <a class="nav-link" href="upload.php<?php echo $current_folder > 0 ? '?folder=' . $current_folder : ''; ?>">
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
                    <div>
                        <h4 class="mb-0 fw-normal text-secondary">Daftar Dokumen</h4>
                        <nav aria-label="breadcrumb" class="mt-1">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="list.php"><i class="bi bi-house-door"></i> Beranda</a>
                                </li>
                                <?php foreach ($breadcrumb as $crumb): ?>
                                <li class="breadcrumb-item">
                                    <?php if ($crumb['id'] != $current_folder): ?>
                                    <a href="?folder=<?php echo $crumb['id']; ?>"><?php echo e($crumb['nama_folder']); ?></a>
                                    <?php else: ?>
                                    <span class="active"><?php echo e($crumb['nama_folder']); ?></span>
                                    <?php endif; ?>
                                </li>
                                <?php endforeach; ?>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if ($role == 'operator'): ?>
                        <button class="btn btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalFolder">
                            <i class="bi bi-folder-plus me-2"></i>Folder Baru
                        </button>
                        <a href="upload.php<?php echo $current_folder > 0 ? '?folder=' . $current_folder : ''; ?>" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-plus-lg me-2"></i>Baru
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <form method="GET" action="" class="row g-3 align-items-end">
                        <?php if ($current_folder > 0 && !$is_searching): ?>
                        <input type="hidden" name="folder" value="<?php echo $current_folder; ?>">
                        <?php endif; ?>
                        <div class="col-md-5">
                            <div class="search-box">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" class="form-control" placeholder="Cari dokumen di semua folder..." 
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
                    <?php if ($is_searching): ?>
                    <div class="mt-3">
                        <div class="search-global-badge">
                            <i class="bi bi-globe"></i>
                            <strong>Mode Pencarian Global</strong> — mencakup semua folder
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($search) || $kategori_filter > 0 || !empty($status_filter)): ?>
                    <div class="mt-2">
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
                        <a href="list.php<?php echo $current_folder > 0 ? '?folder=' . $current_folder : ''; ?>" class="btn btn-sm btn-link text-decoration-none">Reset semua</a>
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
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <?php echo e($_GET['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Folders Section -->
                    <?php if ($total_folders > 0 && empty($search)): ?>
                    <div class="section-title">Folder (<?php echo $total_folders; ?>)</div>
                    <div class="folder-grid">
                        <?php foreach ($folders as $folder): ?>
                        <div class="folder-card">
                            <a href="?folder=<?php echo $folder['id']; ?>" class="folder-link">
                                <i class="bi bi-folder-fill"></i>
                                <div class="folder-info">
                                    <div class="folder-name"><?php echo e($folder['nama_folder']); ?></div>
                                    <div class="folder-meta">Dibuat <?php echo formatTanggal($folder['created_at']); ?></div>
                                </div>
                            </a>
                            <?php if ($role == 'admin' || $folder['created_by'] == $user_id): ?>
                            <div class="folder-menu dropdown">
                                <button class="folder-menu-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="folder_edit.php?id=<?php echo $folder['id']; ?>&current=<?php echo $current_folder; ?>">
                                            <i class="bi bi-pencil text-primary"></i> Edit
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="folder_delete.php?id=<?php echo $folder['id']; ?>&current=<?php echo $current_folder; ?>" 
                                           onclick="return confirm('Yakin ingin menghapus folder [<?php echo e($folder['nama_folder']); ?>]?');">
                                            <i class="bi bi-trash text-danger"></i> Hapus
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Files Section -->
                    <?php if ($total_results > 0 || $is_searching): ?>
                    <div class="section-title">
                        <?php 
                        if ($is_searching) {
                            echo 'Hasil Pencarian Global';
                        } else {
                            echo 'Dokumen';
                        }
                        ?> (<?php echo $total_results; ?>)
                    </div>
                    <?php endif; ?>

                    <div class="drive-table">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 35%;">Nama</th>
                                        <?php if ($is_searching): ?>
                                        <th style="width: 15%;">Lokasi</th>
                                        <?php endif; ?>
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
                                            <?php if ($is_searching): ?>
                                            <td>
                                                <?php if (!empty($row['nama_folder'])): ?>
                                                <a href="?folder=<?php echo (int)$row['folder_real_id']; ?>" class="badge bg-light text-dark border text-decoration-none">
                                                    <i class="bi bi-folder me-1"></i><?php echo e($row['nama_folder']); ?>
                                                </a>
                                                <?php else: ?>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="bi bi-house-door me-1"></i>Beranda
                                                </span>
                                                <?php endif; ?>
                                            </td>
                                            <?php endif; ?>
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
                                            <td colspan="<?php echo $is_searching ? 7 : 6; ?>" class="border-0">
                                                <div class="empty-state">
                                                    <i class="bi bi-folder-x"></i>
                                                    <h5 class="text-muted">Tidak ada dokumen ditemukan</h5>
                                                    <p class="text-muted mb-3">
                                                        <?php if ($is_searching): ?>
                                                        Coba ubah kata kunci atau filter pencarian Anda
                                                        <?php else: ?>
                                                        Belum ada dokumen yang diupload di folder ini
                                                        <?php endif; ?>
                                                    </p>
                                                    <?php if ($role == 'operator' && !$is_searching): ?>
                                                    <a href="upload.php<?php echo $current_folder > 0 ? '?folder=' . $current_folder : ''; ?>" class="btn btn-primary rounded-pill">
                                                        <i class="bi bi-cloud-upload me-2"></i>Upload Dokumen
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
                            <?php if ($is_searching): ?>
                            dari seluruh sistem
                            <?php elseif (!empty($search) || $kategori_filter > 0 || !empty($status_filter)): ?>
                            (difilter dari total)
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Buat Folder -->
    <div class="modal fade" id="modalFolder" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-folder-plus me-2 text-primary"></i>Buat Folder Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="folder_create.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="parent_id" value="<?php echo $current_folder; ?>">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Nama Folder</label>
                            <input type="text" name="nama_folder" class="form-control form-control-lg" 
                                   placeholder="Contoh: Dokumen Akreditasi 2024" required autofocus>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Folder akan dibuat di: 
                            <strong><?php echo $current_folder > 0 ? e($breadcrumb[count($breadcrumb)-1]['nama_folder']) : 'Beranda'; ?></strong>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Buat Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    </script>
</body>
</html>