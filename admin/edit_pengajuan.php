<?php
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT p.*, s.nama_siswa, s.nis, k.nama_kelas 
                       FROM pengajuan p 
                       JOIN siswa s ON p.siswa_id = s.id 
                       LEFT JOIN kelas k ON s.kelas_id = k.id 
                       WHERE p.id = ?");
$stmt->execute([$id]);
$pengajuan = $stmt->fetch();

if (!$pengajuan) {
    die("Pengajuan tidak ditemukan!");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis       = $_POST['jenis'];
    $tanggal     = $_POST['tanggal'];
    $keterangan  = trim($_POST['keterangan']);
    $status      = $_POST['status'];

    $bukti = $pengajuan['bukti']; // default bukti lama

    // Upload bukti baru jika ada
    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] == 0) {
        $uploadDir = '../assist/';
        $newBukti = time() . '_' . basename($_FILES['bukti']['name']);
        move_uploaded_file($_FILES['bukti']['tmp_name'], $uploadDir . $newBukti);
        $bukti = $newBukti;
    }

    $stmt = $pdo->prepare("UPDATE pengajuan SET 
        jenis = ?, 
        tanggal = ?, 
        keterangan = ?, 
        bukti = ?, 
        status = ? 
        WHERE id = ?");

    $stmt->execute([$jenis, $tanggal, $keterangan, $bukti, $status, $id]);

    echo "<script>
        alert('✅ Pengajuan berhasil diperbarui!');
        window.location='pengajuan.php';
    </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengajuan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card mx-auto" style="max-width: 700px;">
        <div class="card-header bg-warning text-dark">
            <h5>Edit Pengajuan - <?= htmlspecialchars($pengajuan['nama_siswa']) ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jenis</label>
                        <select name="jenis" class="form-select" required>
                            <option value="Izin" <?= $pengajuan['jenis']=='Izin'?'selected':'' ?>>Izin</option>
                            <option value="Sakit" <?= $pengajuan['jenis']=='Sakit'?'selected':'' ?>>Sakit</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= $pengajuan['tanggal'] ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="4" required><?= htmlspecialchars($pengajuan['keterangan']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Bukti Saat Ini</label><br>
                    <?php if($pengajuan['bukti']): ?>
                        <a href="../assist/<?= htmlspecialchars($pengajuan['bukti']) ?>" target="_blank" class="text-primary">
                            Lihat Bukti Saat Ini
                        </a>
                    <?php else: ?>
                        <span class="text-muted">Tidak ada bukti</span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Upload Bukti Baru (Opsional)</label>
                    <input type="file" name="bukti" class="form-control" accept="image/*">
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Pending" <?= $pengajuan['status']=='Pending'?'selected':'' ?>>Pending</option>
                        <option value="Approved" <?= $pengajuan['status']=='Approved'?'selected':'' ?>>Approved</option>
                        <option value="Rejected" <?= $pengajuan['status']=='Rejected'?'selected':'' ?>>Rejected</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3">💾 SIMPAN PERUBAHAN</button>
            </form>
        </div>
        <div class="card-footer text-center">
            <a href="pengajuan.php" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>
</body>
</html>