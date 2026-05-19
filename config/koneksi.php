<?php
date_default_timezone_set('Asia/Jakarta');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "sistem_transportasi_kereta";

$conn = mysqli_connect($host,$user,$pass,$db);

if(!$conn){
    die("Koneksi gagal");
}

mysqli_query($conn, "SET time_zone = '+07:00'");
?>
