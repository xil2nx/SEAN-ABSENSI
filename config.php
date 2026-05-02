<?php
// config.php
date_default_timezone_set('Asia/Jakarta');   // ← TAMBAHKAN BARIS INI

$host = 'localhost';
$dbname = 'absen';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

session_start();
?>