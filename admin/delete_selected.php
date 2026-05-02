<?php
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ids'])) {
    $ids = explode(',', $_GET['ids']);
    $ids = array_filter(array_map('intval', $ids)); // Keamanan

    if (!empty($ids)) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM siswa WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        echo "<script>
            alert('✅ " . count($ids) . " siswa berhasil dihapus!');
            window.location='siswa.php';
        </script>";
        exit;
    }
}

echo "<script>
    alert('Tidak ada siswa yang dipilih!');
    window.location='siswa.php';
</script>";
?>