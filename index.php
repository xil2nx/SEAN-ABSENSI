<?php
require_once 'config.php';
require_once 'functions.php';

if (isset($_SESSION['siswa_id'])) {
    header("Location: dashboard.php");
    exit;
}

$settings = getSettings($pdo);
$logoUrl = !empty($settings['logo']) ? 'assist/' . $settings['logo'] : 'https://via.placeholder.com/120';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi - Login Siswa</title>
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
            max-height: 110px;
            max-width: 110px;
            object-fit: contain;
            border-radius: 15px;
            background: white;
            padding: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="d-flex align-items-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-11">
            <div class="card p-5 text-center">
                <div class="mb-4">
                    <img src="<?= htmlspecialchars($logoUrl) ?>" 
                         alt="Logo Sekolah" 
                         class="logo-login mx-auto d-block">
                </div>
                <h4 class="mb-1"><?= htmlspecialchars($settings['nama_lembaga'] ?? 'SMAN 1 Pelabuhanratu') ?></h4>
                <p class="text-light mb-4">Sistem Absensi Siswa</p>
                
                <form action="login_process.php" method="POST">
                    <div class="mb-3">
                        <input type="text" name="nis" class="form-control" placeholder="Nomor Induk Siswa (NIS)" required autocomplete="off">
                    </div>
                    <div class="mb-4">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <input type="hidden" name="device_id" id="device_id">
                    <div class="form-check mb-3 text-start">
                        <input type="checkbox" class="form-check-input" name="remember" id="remember" checked>
                        <label class="form-check-label text-light" for="remember">Ingat saya (tetap login)</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3">LOGIN SISWA</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Device ID
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
}
let deviceId = localStorage.getItem('device_id');
if (!deviceId) {
    deviceId = generateUUID();
    localStorage.setItem('device_id', deviceId);
}
document.getElementById('device_id').value = deviceId;
</script>
</body>
</html>