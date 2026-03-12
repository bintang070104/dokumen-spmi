<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman - hanya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Proses tambah user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah') {

    $nama = escape($_POST['nama']);
    $email = escape($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = escape($_POST['role']);

    $sql = "INSERT INTO users (nama,email,password,role) 
            VALUES ('$nama','$email','$password','$role')";

    execute($sql);

    header("Location: list.php?success=User berhasil ditambahkan");
    exit();
}

// Proses hapus user
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    execute("DELETE FROM users WHERE id=$id");

    header("Location: list.php?success=User berhasil dihapus");
    exit();
}

// Ambil data users
$users = query("SELECT * FROM users ORDER BY created_at DESC");

?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Kelola User - SPMI</title>

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
<i class="bi bi-folder me-2"></i> Kelola Dokumen
</a>
</li>

<li class="nav-item">
<a class="nav-link active" href="list.php">
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

<nav class="navbar navbar-light bg-light p-3">

<div class="container-fluid">

<span class="navbar-brand mb-0 h1">
Kelola User
</span>

</div>

</nav>

<div class="p-4">

<?php if(isset($_GET['success'])): ?>

<div class="alert alert-success alert-dismissible fade show">

<?php echo $_GET['success']; ?>

<button type="button" class="btn-close" data-bs-dismiss="alert"></button>

</div>

<?php endif; ?>

<!-- Form tambah user -->

<div class="card mb-4">

<div class="card-header bg-white">
<h5 class="mb-0">Tambah User Baru</h5>
</div>

<div class="card-body">

<form method="POST">

<input type="hidden" name="action" value="tambah">

<div class="row">

<div class="col-md-3">
<input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required>
</div>

<div class="col-md-3">
<input type="email" name="email" class="form-control" placeholder="Email" required>
</div>

<div class="col-md-2">
<input type="password" name="password" class="form-control" placeholder="Password" required>
</div>

<div class="col-md-2">

<select name="role" class="form-select" required>

<option value="operator">Operator</option>
<option value="pimpinan">Pimpinan</option>
<option value="admin">Admin</option>

</select>

</div>

<div class="col-md-2">

<button type="submit" class="btn btn-success w-100">
<i class="bi bi-plus"></i> Tambah
</button>

</div>

</div>

</form>

</div>

</div>

<!-- Tabel user -->

<div class="card">

<div class="card-header bg-white">
<h5 class="mb-0">Daftar User</h5>
</div>

<div class="card-body">

<table class="table table-striped table-hover">

<thead class="table-dark">

<tr>

<th>No</th>
<th>Nama</th>
<th>Email</th>
<th>Role</th>
<th>Tanggal Dibuat</th>
<th>Aksi</th>

</tr>

</thead>

<tbody>

<?php

$no=1;

foreach($users as $row):

?>

<tr>

<td><?php echo $no++; ?></td>

<td><?php echo $row['nama']; ?></td>

<td><?php echo $row['email']; ?></td>

<td>

<span class="badge bg-<?php
echo $row['role']=='admin'?'danger':($row['role']=='operator'?'success':'info');
?>">

<?php echo ucfirst($row['role']); ?>

</span>

</td>

<td>

<?php echo date('d-m-Y H:i',strtotime($row['created_at'])); ?>

</td>

<td>

<?php if($row['id']!=$_SESSION['user_id']): ?>

<a href="?delete=<?php echo $row['id']; ?>" 
class="btn btn-sm btn-danger"
onclick="return confirm('Yakin ingin menghapus user ini?')">

<i class="bi bi-trash"></i>

</a>

<?php else: ?>

<span class="badge bg-secondary">
Current User
</span>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

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