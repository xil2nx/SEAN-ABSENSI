<?php
require_once '../config.php';
require_once '../functions.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
$settings = getSettings($pdo);
$today = date('Y-m-d');
$now = date('H:i:s');

// Ambil daftar kelas
$kelas_list = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Otomatis Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .header { background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; padding: 25px 0; }
        .card { border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
        }
    </style>
</head>
<body>
<div class="header text-center">
    <div class="container">
        <h3><i class="fas fa-cogs"></i> Proses Otomatis Absensi</h3>
        <p class="mb-0">Tanggal: <strong><?= $today ?></strong> | Waktu: <strong><?= $now ?></strong></p>
    </div>
</div>

<div class="container py-4">
    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
        <!-- FORM PILIH KELAS -->
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3"><i class="fas fa-list-check"></i> Pilih Kelas yang Akan Diproses</h5>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="selectAll" onclick="toggleAll(this)">
                    <label class="form-check-label fw-bold" for="selectAll">Pilih Semua Kelas</label>
                </div>

                <div class="checkbox-grid">
                    <?php foreach($kelas_list as $k): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="kelas_id[]" value="<?= $k['id'] ?>" id="kelas<?= $k['id'] ?>">
                        <label class="form-check-label" for="kelas<?= $k['id'] ?>">
                            <?= htmlspecialchars($k['nama_kelas']) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <hr class="my-4">
                <button type="submit" form="processForm" class="btn btn-danger btn-lg w-100">
                    <i class="fas fa-play"></i> PROSES SEKARANG
                </button>
            </div>
        </div>

        <form id="processForm" method="POST"></form>

    <?php else: ?>
        <!-- PROSES EKSEKUSI -->
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3"><i class="fas fa-spinner fa-spin"></i> Sedang Memproses...</h5>
                <div class="log-box p-4 border bg-light">
                    <?php
                    $kelas_selected = $_POST['kelas_id'] ?? [];
                    if (empty($kelas_selected)) {
                        echo "<b class='text-danger'>Tidak ada kelas yang dipilih!</b>";
                    } else {
                        echo "<b>Memproses ALPA & Izin/Sakit untuk tanggal: $today</b><br>";
                        echo "Kelas yang diproses: " . count($kelas_selected) . " kelas<br><hr>";

                        $placeholders = str_repeat('?,', count($kelas_selected) - 1) . '?';
                        $siswa_list = $pdo->prepare("SELECT s.id, s.nama_siswa 
                                                     FROM siswa s 
                                                     WHERE s.kelas_id IN ($placeholders)");
                        $siswa_list->execute($kelas_selected);
                        $siswa_list = $siswa_list->fetchAll();

                        foreach ($siswa_list as $siswa) {
                            $siswa_id = $siswa['id'];
                            $nama = htmlspecialchars($siswa['nama_siswa']);

                            $stmt = $pdo->prepare("SELECT jenis FROM pengajuan WHERE siswa_id = ? AND tanggal = ? AND status = 'Approved' LIMIT 1");
                            $stmt->execute([$siswa_id, $today]);
                            $pengajuan = $stmt->fetch();

                            $stmt = $pdo->prepare("SELECT status FROM absensi WHERE siswa_id = ? AND tanggal = ?");
                            $stmt->execute([$siswa_id, $today]);
                            $absen = $stmt->fetch();

                            if ($pengajuan) {
                                if (!$absen) {
                                    $stmt = $pdo->prepare("INSERT INTO absensi (siswa_id, tanggal, status, keterangan) VALUES (?, ?, ?, 'Otomatis dari pengajuan')");
                                    $stmt->execute([$siswa_id, $today, $pengajuan['jenis']]);
                                    echo "✅ <span class='text-success'>$nama → <b>{$pengajuan['jenis']}</b></span><br>";
                                } else {
                                    echo "⏭️ $nama → Sudah ada record<br>";
                                }
                            } elseif (!$absen) {
                                if ($now > ($settings['jam_maksimal_masuk'] ?? '08:00:00')) {
                                    $stmt = $pdo->prepare("INSERT INTO absensi (siswa_id, tanggal, status) VALUES (?, ?, 'Alpa')");
                                    $stmt->execute([$siswa_id, $today]);
                                    echo "🚫 <span class='text-warning'>$nama → <b>ALPA</b></span><br>";
                                } else {
                                    echo "⏳ $nama → Belum waktunya ALPA<br>";
                                }
                            } else {
                                echo "✓ $nama → Sudah tercatat<br>";
                            }
                        }
                    }
                    ?>
                </div>
                <div class="mt-4 text-center">
                    <h5 class="text-success">✅ Proses Otomatis Selesai!</h5>
                    <a href="dashboard.php" class="btn btn-primary btn-lg mt-3">
                        <i class="fas fa-home"></i> Kembali ke Dashboard Admin
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Pilih Semua Checkbox
function toggleAll(source) {
    document.querySelectorAll('input[name="kelas_id[]"]').forEach(checkbox => {
        checkbox.checked = source.checked;
    });
}
</script>
</body>
</html>