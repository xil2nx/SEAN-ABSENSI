<?php
require_once '../config.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM siswa WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>alert('Siswa berhasil dihapus!'); window.location='siswa.php';</script>";
} else {
    echo "<script>alert('ID tidak valid!'); window.location='siswa.php';</script>";
}
?>