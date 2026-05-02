<?php
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = trim($_POST['nis']);
    $password = $_POST['password'];
    $device_id = $_POST['device_id'];
    $remember = isset($_POST['remember']);

    $stmt = $pdo->prepare("SELECT * FROM siswa WHERE nis = ?");
    $stmt->execute([$nis]);
    $siswa = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($siswa && password_verify($password, $siswa['password'])) {
        // Cek device binding
        if (!empty($siswa['device_id']) && $siswa['device_id'] !== $device_id) {
            die("Akun ini hanya boleh digunakan di satu perangkat. Hubungi admin untuk reset.");
        }

        // Bind device jika pertama kali
        if (empty($siswa['device_id'])) {
            $stmt = $pdo->prepare("UPDATE siswa SET device_id = ? WHERE id = ?");
            $stmt->execute([$device_id, $siswa['id']]);
        }

        $_SESSION['siswa_id'] = $siswa['id'];

        // Jika "Ingat Saya" di centang → session lebih lama
        if ($remember) {
            session_set_cookie_params(60*60*24*30); // 30 hari
            session_regenerate_id(true);
        }

        header("Location: dashboard.php");
        exit;
    } else {
        die("NIS atau password salah!");
    }
}
?>