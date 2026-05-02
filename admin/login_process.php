<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_nama'] = $admin['nama_admin'];
        
        header("Location: dashboard.php");
        exit;
    } else {
        echo "<h3 style='color:red;text-align:center;margin-top:100px;'>❌ Username atau Password SALAH!</h3>";
        echo "<p style='text-align:center;'><a href='login.php' class='btn btn-primary mt-3'>Coba Lagi</a></p>";
    }
}
?>