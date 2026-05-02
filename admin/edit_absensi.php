<?php
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT a.*, s.nis, s.nama_siswa, k.nama_kelas 
                       FROM absensi a 
                       JOIN siswa s ON a.siswa_id = s.id 
                       LEFT JOIN kelas k ON s.kelas_id = k.id 
                       WHERE a.id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die("Data absensi tidak ditemukan!");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jam_datang = $_POST['jam_datang'];
    $jam_pulang = $_POST['jam_pulang'];
    $status     = $_POST['status'];
    $keterangan = trim($_POST['keterangan']);

    $stmt = $pdo->prepare("UPDATE absensi SET 
        jam_datang = ?, 
        jam_pulang = ?, 
        status = ?, 
        keterangan = ? 
        WHERE id = ?");
    
    $stmt->execute([$jam_datang, $jam_pulang, $status, $keterangan, $id]);

    echo "<script>
        alert('Data absensi berhasil diperbarui!');
        window.location='absensi.php';
    </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card mx-auto" style="max-width: 700px;">
        <div class="card-header bg-primary text-white">
            <h5>Edit Data Absensi - <?= htmlspecialchars($data['nama_siswa']) ?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" class="form-control" value="<?= $data['tanggal'] ?>" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Hadir" <?= $data['status']=='Hadir'?'selected':'' ?>>Hadir</option>
                            <option value="Terlambat" <?= $data['status']=='Terlambat'?'selected':'' ?>>Terlambat</option>
                            <option value="Izin" <?= $data['status']=='Izin'?'selected':'' ?>>Izin</option>
                            <option value="Sakit" <?= $data['status']=='Sakit'?'selected':'' ?>>Sakit</option>
                            <option value="Alpa" <?= $data['status']=='Alpa'?'selected':'' ?>>Alpa</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jam Datang</label>
                        <input type="time" name="jam_datang" class="form-control" value="<?= $data['jam_datang'] ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jam Pulang</label>
                        <input type="time" name="jam_pulang" class="form-control" value="<?= $data['jam_pulang'] ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($data['keterangan'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3">💾 SIMPAN PERUBAHAN</button>
            </form>
        </div>
        <div class="card-footer text-center">
            <a href="absensi.php" class="btn btn-secondary">Kembali ke Laporan</a>
        </div>
    </div>
</div>
</body>
</html>