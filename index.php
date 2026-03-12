<?php
session_start();

// Jika sudah login
if (isset($_SESSION['user_id'])) {

    switch ($_SESSION['role']) {

        case 'admin':
            header("Location: dashboard/admin.php");
            break;

        case 'operator':
            header("Location: dashboard/operator.php");
            break;

        case 'pimpinan':
            header("Location: dashboard/pimpinan.php");
            break;

        default:
            // Jika role tidak valid
            session_destroy();
            header("Location: auth/login.php");
            break;
    }

    exit();

} else {

    // Jika belum login
    header("Location: auth/login.php");
    exit();
}
?>