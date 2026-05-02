<?php
require_once '../config.php';
require_once '../functions.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
$settings = getSettings($pdo);
// Hari Libur yang tersimpan
$hari_libur_terpilih = !empty($settings['hari_libur']) ? explode(',', $settings['hari_libur']) : [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lembaga = trim($_POST['nama_lembaga']);
    $lat = trim($_POST['lat']);
    $lng = trim($_POST['lng']);
    $radius = (int)$_POST['radius_meter'];
    $jam_masuk = $_POST['jam_masuk'];
    $jam_pulang = $_POST['jam_pulang'];
    $jam_maksimal = $_POST['jam_maksimal_masuk'];
    // Ambil hari libur dari checkbox
    $hari_libur_array = $_POST['hari_libur'] ?? [];
    $hari_libur = implode(',', $hari_libur_array);
    $stmt = $pdo->prepare("UPDATE settings SET
        nama_lembaga = ?,
        lat = ?,
        lng = ?,
        radius_meter = ?,
        jam_masuk = ?,
        jam_pulang = ?,
        jam_maksimal_masuk = ?,
        hari_libur = ?
        WHERE id = 1");
    $stmt->execute([$nama_lembaga, $lat, $lng, $radius, $jam_masuk, $jam_pulang, $jam_maksimal, $hari_libur]);
    // Upload Logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $uploadDir = '../assist/';
        $fileName = time() . '_' . basename($_FILES['logo']['name']);
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $fileName)) {
            $stmt = $pdo->prepare("UPDATE settings SET logo = ? WHERE id = 1");
            $stmt->execute([$fileName]);
        }
    }
    echo "<script>alert('✅ Pengaturan berhasil disimpan!'); window.location='setting.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <h3><i class="fas fa-cog"></i> Pengaturan Sekolah</h3>
    <div class="card shadow">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <!-- Logo -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Logo Sekolah</label><br>
                    <?php if(!empty($settings['logo'])): ?>
                        <img src="../assist/<?= htmlspecialchars($settings['logo']) ?>" class="img-thumbnail mb-3" style="max-height:120px;">
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Nama Lembaga</label>
                        <input type="text" name="nama_lembaga" class="form-control" value="<?= htmlspecialchars($settings['nama_lembaga'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Latitude</label>
                        <input type="text" name="lat" class="form-control" value="<?= $settings['lat'] ?? '-6.98731040' ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Longitude</label>
                        <input type="text" name="lng" class="form-control" value="<?= $settings['lng'] ?? '106.55920010' ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Radius Absensi (Meter)</label>
                        <input type="number" name="radius_meter" class="form-control" value="<?= $settings['radius_meter'] ?? 100 ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Hari Libur</label><br>
                        <div class="row">
                            <?php
                            $hari = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
                            foreach($hari as $h):
                            ?>
                            <div class="col-6 col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="hari_libur[]"
                                           value="<?= $h ?>" id="<?= $h ?>"
                                           <?= in_array($h, $hari_libur_terpilih) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="<?= $h ?>"><?= $h ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Jam Masuk</label>
                        <input type="time" name="jam_masuk" class="form-control" value="<?= substr($settings['jam_masuk'] ?? '07:00:00', 0, 5) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Jam Maksimal Masuk</label>
                        <input type="time" name="jam_maksimal_masuk" class="form-control" value="<?= substr($settings['jam_maksimal_masuk'] ?? '07:30:00', 0, 5) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Jam Pulang</label>
                        <input type="time" name="jam_pulang" class="form-control" value="<?= substr($settings['jam_pulang'] ?? '15:00:00', 0, 5) ?>" required>
                    </div>
                </div>
                <hr>
                <button type="submit" class="btn btn-success btn-lg w-100">
                    <i class="fas fa-save"></i> SIMPAN SEMUA PENGATURAN
                </button>
            </form>
        </div>
    </div>
    <div class="mt-4 text-center">
        <a href="dashboard.php" class="btn btn-secondary">← Kembali ke Dashboard</a>
    </div>
</div>
</body>
</html>