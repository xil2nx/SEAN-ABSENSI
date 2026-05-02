<?php
require_once '../config.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

if ($id) {
    $stmt = $pdo->prepare("UPDATE siswa SET device_id = NULL WHERE id = ?");
    $stmt->execute([$id]);
    
    echo "<script>
        alert('✅ Perangkat berhasil direset! Siswa dapat login di HP baru.');
        window.location='siswa.php';
    </script>";
} else {
    echo "<script>alert('ID tidak valid!'); window.location='siswa.php';</script>";
}
?>