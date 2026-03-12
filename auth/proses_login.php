<?php
session_start();
require '../config/database.php';

if(!isset($_POST['email'], $_POST['password'])){
    header("Location: login.php");
    exit();
}

$email = escape($_POST['email']);
$password = $_POST['password'];

$result = $conn->query("SELECT * FROM users WHERE email='$email'");

if($result && $result->num_rows > 0){

    $user = $result->fetch_assoc();

    if(password_verify($password, $user['password'])){

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];

        header("Location: ../index.php");
        exit();

    }
}

header("Location: login.php?error=1");
exit();
?>