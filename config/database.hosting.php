<?php

$host = "localhost";
$username = "username_hosting";
$password = "password_hosting";
$database = "nama_database_hosting";

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

?>