<?php

session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "adresdefteridb_v2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız.");
}

$conn->set_charset("utf8mb4");