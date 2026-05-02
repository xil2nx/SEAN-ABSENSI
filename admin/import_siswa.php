<?php
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$kelas_list = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_csv'])) {
    $file = $_FILES['file_csv'];
    if ($file['error'] == 0 && pathinfo($file['name'], PATHINFO_EXTENSION) == 'csv') {
        $handle = fopen($file['tmp_name'], "r");
        $success = 0;
        $failed = 0;

        // Skip header
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) >= 4) {
                $nis           = trim($data[0]);
                $nama_siswa    = trim($data[1]);
                $kelas_nama    = trim($data[2]);
                $tanggal_lahir = trim($data[3]);

                // Cari ID kelas
                $stmt = $pdo->prepare("SELECT id FROM kelas WHERE nama_kelas = ?");
                $stmt->execute([$kelas_nama]);
                $kelas = $stmt->fetch();

                if ($kelas && !empty($nis) && !empty($nama_siswa)) {
                    $password = password_hash($nis, PASSWORD_DEFAULT); // default password = NIS

                    $stmt = $pdo->prepare("INSERT INTO siswa (nis, nama_siswa, kelas_id, tanggal_lahir, password) 
                                           VALUES (?, ?, ?, ?, ?) 
                                           ON DUPLICATE KEY UPDATE nama_siswa=VALUES(nama_siswa), kelas_id=VALUES(kelas_id)");
                    $stmt->execute([$nis, $nama_siswa, $kelas['id'], $tanggal_lahir, $password]);
                    $success++;
                } else {
                    $failed++;
                }
            }
        }
        fclose($handle);

        echo "<script>alert('Import selesai! Berhasil: $success | Gagal: $failed'); window.location='siswa.php';</script>";
        exit;
    } else {
        echo "<script>alert('File harus berformat CSV!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-header bg-primary text-white text-center">
            <h5><i class="fas fa-file-import"></i> Import Siswa dari CSV</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                Format CSV: <strong>nis,nama_siswa,nama_kelas,tanggal_lahir</strong><br>
                Contoh: <code>12345678,Andi Saputra,X-1,2008-05-15</code>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Pilih File CSV</label>
                    <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Import Siswa</button>
            </form>
        </div>
        <div class="card-footer text-center">
            <a href="siswa.php" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>
</body>
</html>