<?php
require_once '../config.php';
require_once '../functions.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$kelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis           = trim($_POST['nis']);
    $nama_siswa    = trim($_POST['nama_siswa']);
    $kelas_id      = $_POST['kelas_id'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $password      = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO siswa (nis, nama_siswa, kelas_id, tanggal_lahir, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nis, $nama_siswa, $kelas_id, $tanggal_lahir, $password]);

    echo "<script>alert('Siswa berhasil ditambahkan!'); window.location='siswa.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-header bg-success text-white">
            <h5>Tambah Siswa Baru</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label>NIS</label>
                    <input type="text" name="nis" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Nama Siswa</label>
                    <input type="text" name="nama_siswa" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Kelas</label>
                    <select name="kelas_id" class="form-select" required>
                        <option value="">Pilih Kelas</option>
                        <?php foreach($kelas as $k): ?>
                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Simpan Siswa</button>
            </form>
        </div>
        <div class="card-footer">
            <a href="siswa.php" class="btn btn-secondary">Batal</a>
        </div>
    </div>
</div>
</body>
</html>