<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'spmi_system');

// Membuat koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");


/*
|--------------------------------------------------------------------------
| Helper Function
|--------------------------------------------------------------------------
*/

// Query SELECT
function query($sql) {
    global $conn;

    $result = $conn->query($sql);
    $rows = [];

    if($result){
        while($row = $result->fetch_assoc()){
            $rows[] = $row;
        }
    }

    return $rows;
}


// Query INSERT / UPDATE / DELETE
function execute($sql){
    global $conn;
    return $conn->query($sql);
}


// Escape string (anti SQL injection dasar)
function escape($string){
    global $conn;
    return $conn->real_escape_string($string);
}
?>