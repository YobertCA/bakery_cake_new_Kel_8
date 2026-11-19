<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "bakery_cake_db";

// Jika aplikasi dijalankan di hosting
if (
    !empty($_SERVER['HTTP_HOST']) &&
    strpos($_SERVER['HTTP_HOST'], 'bakerycakenew.lovestoblog.com') !== false
) {
    $host = "sql312.infinityfree.com";
    $username = "if0_43359756";
    $password = "K1yY0UWcfR";
    $database = "if0_43359756_bakery_cake_db";
}

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

?>