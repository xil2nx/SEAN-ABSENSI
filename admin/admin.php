<?php
require_once '../config.php';
require_once '../functions.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Filter
$tanggal_awal  = $_GET['tanggal_awal'] ?? date('Y-m-d', strtotime('-7 days'));
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$kelas_id      = $_GET['kelas_id'] ?? '';
$cari          = $_GET['cari'] ?? '';

// Query
$where = "WHERE a.tanggal BETWEEN ? AND ?";
$params = [$tanggal_awal, $tanggal_akhir];

if ($kelas_id) {
    $where .= " AND s.kelas_id = ?";
    $params[] = $kelas_id;
}
if ($cari) {
    $where .= " AND (s.nama_siswa LIKE ? OR s.nis LIKE ?)";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}

$sql = "SELECT a.*, s.nis, s.nama_siswa, k.nama_kelas 
        FROM absensi a 
        JOIN siswa s ON a.siswa_id = s.id 
        LEFT JOIN kelas k ON s.kelas_id = k.id 
        $where 
        ORDER BY a.tanggal DESC, s.nama_siswa ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$absensi = $stmt->fetchAll();

// Data untuk filter
$kelas_list = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <h3><i class="fas fa-list-check"></i> Laporan Absensi</h3>

    <!-- Form Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tanggal Awal</label>
                    <input type="date" name="tanggal_awal" class="form-control" value="<?= $tanggal_awal ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" class="form-control" value="<?= $tanggal_akhir ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kelas</label>
                    <select name="kelas_id" class="form-select">
                        <option value="">Semua Kelas</option>
                        <?php foreach($kelas_list as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $k['id']==$kelas_id?'selected':'' ?>>
                            <?= htmlspecialchars($k['nama_kelas']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cari Siswa</label>
                    <input type="text" name="cari" class="form-control" placeholder="Nama atau NIS" value="<?= htmlspecialchars($cari) ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tampilkan Laporan
                    </button>
                    <a href="absensi.php" class="btn btn-secondary">Reset Filter</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Laporan -->
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Tanggal</th>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>Jam Datang</th>
                    <th>Jam Pulang</th>
                    <th>Status</th>
                    <th>Jarak (m)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($absensi)): ?>
                <tr><td colspan="8" class="text-center py-4">Tidak ada data absensi</td></tr>
                <?php else: ?>
                    <?php foreach($absensi as $a): ?>
                    <tr>
                        <td><?= $a['tanggal'] ?></td>
                        <td><?= htmlspecialchars($a['nis']) ?></td>
                        <td><?= htmlspecialchars($a['nama_siswa']) ?></td>
                        <td><?= htmlspecialchars($a['nama_kelas'] ?? '-') ?></td>
                        <td><?= $a['jam_datang'] ?? '-' ?></td>
                        <td><?= $a['jam_pulang'] ?? '-' ?></td>
                        <td>
                            <span class="badge bg-<?= 
                                $a['status'] == 'Hadir' ? 'success' : 
                                ($a['status'] == 'Terlambat' ? 'warning' : 'danger') ?>">
                                <?= $a['status'] ?>
                            </span>
                        </td>
                        <td><?= number_format($a['jarak_meter'], 0) ?> m</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <a href="dashboard.php" class="btn btn-secondary">← Kembali ke Dashboard</a>
    </div>
</div>
</body>
</html>