<?php
session_start();
require_once '../config/database.php';

// Cek apakah request method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'], $_POST['password'])) {
    header("Location: login.php");
    exit();
}

// Ambil input
$email = trim($_POST['email']);
$password = $_POST['password'];

// Validasi email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: login.php?error=invalid_email");
    exit();
}

try {
    // Gunakan db_select dengan prepared statement
    $user = db_select_one("SELECT * FROM users WHERE email = ?", [$email]);
    
    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session ID untuk keamanan
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];
        
        // Log login (opsional)
        db_insert('log_aktivitas', [
            'user_id' => $user['id'],
            'aksi' => 'login',
            'detail' => 'Login berhasil',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Redirect ke dashboard sesuai role
        $dashboard_urls = [
            'admin' => '../dashboard/admin.php',
            'operator' => '../dashboard/operator.php',
            'pimpinan' => '../dashboard/pimpinan.php'
        ];
        
        $redirect = $dashboard_urls[$user['role']] ?? '../index.php';
        header("Location: " . $redirect);
        exit();
    }
    
    // Login gagal - delay untuk mencegah brute force
    sleep(1);
    header("Location: login.php?error=invalid_credentials");
    exit();
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    header("Location: login.php?error=system");
    exit();
}
?>