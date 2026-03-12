<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman - hanya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Proses tambah kategori
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $nama_kategori = escape($_POST['nama_kategori']);
    $sql = "INSERT INTO kategori (nama_kategori) VALUES ('$nama_kategori')";
    query($sql);
    header("Location: list.php?success=Kategori berhasil ditambahkan");
    exit();
}

// Proses hapus kategori
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    query("DELETE FROM kategori WHERE id = $id");
    header("Location: list.php?success=Kategori berhasil dihapus");
    exit();
}

// Ambil data kategori
$kategori = query("SELECT * FROM kategori ORDER BY nama_kategori");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - SPMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
   