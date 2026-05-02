<?php
// assist/admin/login.php
require_once '../config.php';
require_once '../functions.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$settings = getSettings($pdo);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #007bff, #00c6ff); 
            min-height: 100vh; 
        }
        .card { 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
        }
        .logo-login {
            max-height: 90px;
            max-width: 90px;
            object-fit: contain;
            border-radius: 12px;
            background: white;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="d-flex align-items-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-11">
            <div class="card p-5 text-center">
                
                <!-- Logo yang sudah diketengahkan sempurna -->
                <div class="mb-4">
                    <?php if(!empty($settings['logo'])): ?>
                        <img src="../assist/<?= htmlspecialchars($settings['logo']) ?>" 
                             alt="Logo Sekolah" 
                             class="logo-login mx-auto d-block">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/90" 
                             class="logo-login mx-auto d-block" alt="Logo">
                    <?php endif; ?>
                </div>

                <h4 class="mb-1">Admin Panel</h4>
                <p class="text-muted mb-4"><?= htmlspecialchars($settings['nama_lembaga'] ?? 'SMAN 1 Pelabuhanratu') ?></p>
                
                <form action="login_process.php" method="POST">
                    <div class="mb-3">
                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                    </div>
                    <div class="mb-4">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3">LOGIN ADMIN</button>
                </form>
                
                <a href="../index.php" class="d-block mt-3 text-white">← Login Siswa</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>