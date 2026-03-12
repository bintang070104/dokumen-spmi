<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Hitung statistik
$total_dokumen = query("SELECT COUNT(*) as total FROM dokumen")[0]['total'];
$dokumen_aktif = query("SELECT COUNT(*) as total FROM dokumen WHERE status='aktif'")[0]['total'];
$dokumen_kadaluarsa = query("SELECT COUNT(*) as total FROM dokumen WHERE status='kadaluarsa'")[0]['total'];
$total_users = query("SELECT COUNT(*) as total FROM users")[0]['total'];

// Dokumen terbaru
$recent = query("SELECT d.*, k.nama_kategori 
                FROM dokumen d 
                JOIN kategori k ON d.kategori_id = k.id 
                ORDER BY d.created_at DESC 
                LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - SPMI</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<style>
.sidebar{
min-height:100vh;
background:#2c3e50;
color:white;
}

.sidebar .nav-link{
color:white;
padding:15px 20px;
}

.sidebar .nav-link:hover,
.sidebar .nav-link.active{
background:#34495e;
}

.stat-card{
border:none;
border-radius:10px;
box-shadow:0 4px 6px rgba(0,0,0,0.1);
}

.navbar{
background:white;
box-shadow:0 2px 4px rgba(0,0,0,0.1);
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
<a class="nav-link active" href="admin.php">
<i class="bi bi-speedometer2 me-2"></i> Dashboard
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../dokumen/list.php">
<i class="bi bi-folder me-2"></i> Kelola Dokumen
</a>
</li>

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
<span class="navbar-brand mb-0 h1">
Dashboard Administrator
</span>

<div class="d-flex align-items-center">
<span class="me-3">
Selamat datang, 
<strong><?php echo $_SESSION['nama']; ?></strong>
</span>
</div>

</div>
</nav>

<!-- Content -->
<div class="p-4">

<h4 class="mb-4">Statistik Dokumen</h4>

<div class="row mb-4">

<div class="col-md-3">
<div class="card stat-card bg-primary text-white">
<div class="card-body">
<div class="d-flex justify-content-between">

<div>
<h6>Total Dokumen</h6>
<h3><?php echo $total_dokumen; ?></h3>
</div>

<i class="bi bi-files fs-1"></i>

</div>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card stat-card bg-success text-white">
<div class="card-body">
<div class="d-flex justify-content-between">

<div>
<h6>Dokumen Aktif</h6>
<h3><?php echo $dokumen_aktif; ?></h3>
</div>

<i class="bi bi-check-circle fs-1"></i>

</div>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card stat-card bg-warning text-white">
<div class="card-body">
<div class="d-flex justify-content-between">

<div>
<h6>Dokumen Kadaluarsa</h6>
<h3><?php echo $dokumen_kadaluarsa; ?></h3>
</div>

<i class="bi bi-exclamation-triangle fs-1"></i>

</div>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card stat-card bg-info text-white">
<div class="card-body">
<div class="d-flex justify-content-between">

<div>
<h6>Total User</h6>
<h3><?php echo $total_users; ?></h3>
</div>

<i class="bi bi-people fs-1"></i>

</div>
</div>
</div>
</div>

</div>

<!-- Dokumen terbaru -->
<div class="card">

<div class="card-header bg-white">
<h5 class="mb-0">Dokumen Terbaru</h5>
</div>

<div class="card-body">

<table class="table table-striped">

<thead>
<tr>
<th>No</th>
<th>Judul Dokumen</th>
<th>Kategori</th>
<th>Versi</th>
<th>Status</th>
<th>Tanggal Upload</th>
</tr>
</thead>

<tbody>

<?php
$no = 1;

if(count($recent) > 0):

foreach($recent as $row):
?>

<tr>

<td><?php echo $no++; ?></td>

<td><?php echo $row['judul_dokumen']; ?></td>

<td><?php echo $row['nama_kategori']; ?></td>

<td><?php echo $row['versi']; ?></td>

<td>
<span class="badge bg-<?php echo $row['status']=='aktif' ? 'success' : 'warning'; ?>">
<?php echo ucfirst($row['status']); ?>
</span>
</td>

<td>
<?php echo date('d-m-Y', strtotime($row['tanggal_upload'])); ?>
</td>

</tr>

<?php
endforeach;

else:
?>

<tr>
<td colspan="6" class="text-center">
Belum ada dokumen
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>