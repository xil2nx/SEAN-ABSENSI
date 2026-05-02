<?php
require_once 'config.php';
require_once 'functions.php';
if (!isset($_SESSION['siswa_id'])) {
    header("Location: index.php");
    exit;
}
$siswa = getSiswa($pdo, $_SESSION['siswa_id']);
$settings = getSettings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis      = $_POST['jenis'];
    $tanggal    = $_POST['tanggal'];
    $keterangan = trim($_POST['keterangan']);

    // === PENCEGAHAN PENGIRIMAN LEBIH DARI 1X PER TANGGAL ===
    $cek = $pdo->prepare("SELECT id FROM pengajuan WHERE siswa_id = ? AND tanggal = ? LIMIT 1");
    $cek->execute([$siswa['id'], $tanggal]);
    if ($cek->rowCount() > 0) {
        echo "<script>
            alert('❌ Anda sudah mengajukan izin/sakit untuk tanggal ini!');
            window.location='ajukan_izin.php';
        </script>";
        exit;
    }

    // === BUKTI WAJIB ===
    if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] != 0) {
        echo "<script>
            alert('❌ Bukti wajib diunggah!');
            window.location='ajukan_izin.php';
        </script>";
        exit;
    }

    $uploadDir = 'assist/';
    $bukti = time() . '_' . basename($_FILES['bukti']['name']);
    move_uploaded_file($_FILES['bukti']['tmp_name'], $uploadDir . $bukti);

    $stmt = $pdo->prepare("INSERT INTO pengajuan (siswa_id, jenis, tanggal, keterangan, bukti, status)
                           VALUES (?, ?, ?, ?, ?, 'Pending')");
    $stmt->execute([$siswa['id'], $jenis, $tanggal, $keterangan, $bukti]);

    echo "<script>
        alert('✅ Pengajuan Izin/Sakit berhasil dikirim! Menunggu persetujuan admin.');
        window.location='dashboard.php';
    </script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Izin / Sakit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
            min-height: 100vh;
        }
        .card {
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .btn-submit {
            height: 55px;
            font-size: 1.2rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-header bg-warning text-dark text-center">
            <h4><i class="fas fa-file-alt"></i> Ajukan Izin / Sakit</h4>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
               
                <div class="mb-3">
                    <label class="form-label fw-bold">Jenis Pengajuan</label>
                    <select name="jenis" class="form-select" required>
                        <option value="">Pilih Jenis</option>
                        <option value="Izin">Izin</option>
                        <option value="Sakit">Sakit</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="4" placeholder="Alasan izin/sakit..." required></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Upload Bukti (Foto/Surat) <span class="text-danger">*</span></label>
                    <input type="file" name="bukti" class="form-control" accept="image/*" required>
                    <small class="text-muted">Wajib diisi (Foto atau Surat Keterangan)</small>
                </div>
                <button type="submit" class="btn btn-warning btn-submit w-100 text-dark">
                    <i class="fas fa-paper-plane"></i> KIRIM PENGAJUAN
                </button>
            </form>
        </div>
        <div class="card-footer text-center">
            <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>