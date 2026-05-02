<?php
// config.php
date_default_timezone_set('Asia/Jakarta');   // ← TAMBAHKAN BARIS INI

$host = 'localhost';
$dbname = 'smae1551_absensi';
$username = 'smae1551_il2n';
$password = 'poiuytre123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

session_start();
?>