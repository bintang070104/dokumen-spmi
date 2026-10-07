<?php
session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SPMI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-wrapper {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
        }
        .login-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            height: 100%;
        }
        .login-image img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }
        .login-form {
            padding: 50px 40px;
        }
        .login-title {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
            font-weight: bold;
        }
        .login-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            width: 100%;
            padding: 12px;
            font-weight: bold;
            border-radius: 8px;
        }
        .btn-login:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            transition: all 0.3s ease;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
        }
        .form-label {
            font-weight: 500;
            color: #555;
        }
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
            color: #999;
            font-size: 12px;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        .divider::before {
            margin-right: 10px;
        }
        .divider::after {
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="login-wrapper">
                    <div class="row g-0">
                        <!-- Kolom Gambar (Kiri) -->
                        <div class="col-md-5 d-none d-md-block">
                            <div class="login-image">
                                <!-- Ganti src dengan path gambar kamu -->
                                <img src="../assets/images/logo.png" alt="Logo">
                                <!-- Alternatif: gunakan gambar dari URL -->
                                <!-- <img src="https://img.freepik.com/free-vector/documents-concept-illustration_114360-1280.jpg" alt="Login Illustration"> -->
                            </div>
                        </div>
                        
                        <!-- Kolom Form (Kanan) -->
                        <div class="col-md-7">
                            <div class="login-form">
                                <h3 class="login-title">SPMI System</h3>
                                <p class="login-subtitle">Sistem Informasi Manajemen Dokumen Penjaminan Mutu</p>
                                
                                <?php if (isset($_GET['error'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="bi bi-exclamation-circle"></i> Email atau password salah!
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <form action="proses_login.php" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-envelope"></i> Email
                                        </label>
                                        <input type="email" name="email" class="form-control" required placeholder="Masukkan email">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-lock"></i> Password
                                        </label>
                                        <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-login">
                                        <i class="bi bi-box-arrow-in-right"></i> LOGIN
                                    </button>
                                </form>
                                
                                <div class="divider">atau</div>
                                
                                <div class="text-center">
                                    <small class="text-muted">
                                        <strong>Default Login:</strong><br>
                                        <span class="badge bg-primary">Admin</span> admin@spmi.com / admin123<br>
                                        <span class="badge bg-success">Operator</span> operator@spmi.com / operator123<br>
                                        <span class="badge bg-warning text-dark">Pimpinan</span> pimpinan@spmi.com / pimpinan123
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</body>
</html>