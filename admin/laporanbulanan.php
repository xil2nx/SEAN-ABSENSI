<?php
require_once '../config.php';
require_once '../functions.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Filter
$year = $_GET['year'] ?? date('Y');
$kelas_nama = $_GET['kelas_nama'] ?? '';

// Ambil daftar kelas
$kelas_list = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();

// Query Laporan Bulanan per Jenis Presensi
$sql = "SELECT 
            s.nama_siswa,
            s.nis,
            k.nama_kelas,
            a.status as jenis_presensi,
            COUNT(CASE WHEN MONTH(a.tanggal) = 1 THEN 1 END) as jan,
            COUNT(CASE WHEN MONTH(a.tanggal) = 2 THEN 1 END) as feb,
            COUNT(CASE WHEN MONTH(a.tanggal) = 3 THEN 1 END) as mar,
            COUNT(CASE WHEN MONTH(a.tanggal) = 4 THEN 1 END) as apr,
            COUNT(CASE WHEN MONTH(a.tanggal) = 5 THEN 1 END) as mei,
            COUNT(CASE WHEN MONTH(a.tanggal) = 6 THEN 1 END) as jun,
            COUNT(CASE WHEN MONTH(a.tanggal) = 7 THEN 1 END) as jul,
            COUNT(CASE WHEN MONTH(a.tanggal) = 8 THEN 1 END) as agu,
            COUNT(CASE WHEN MONTH(a.tanggal) = 9 THEN 1 END) as sep,
            COUNT(CASE WHEN MONTH(a.tanggal) = 10 THEN 1 END) as okt,
            COUNT(CASE WHEN MONTH(a.tanggal) = 11 THEN 1 END) as nov,
            COUNT(CASE WHEN MONTH(a.tanggal) = 12 THEN 1 END) as des,
            COUNT(a.id) as total
        FROM siswa s
        LEFT JOIN kelas k ON s.kelas_id = k.id
        LEFT JOIN absensi a ON a.siswa_id = s.id AND YEAR(a.tanggal) = ?
        WHERE 1=1";

$params = [$year];

if ($kelas_nama) {
    $sql .= " AND k.nama_kelas = ?";
    $params[] = $kelas_nama;
}

$sql .= " GROUP BY s.id, s.nama_siswa, s.nis, k.nama_kelas, a.status 
          ORDER BY k.nama_kelas ASC, s.nama_siswa ASC, a.status ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bulanan Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        table { font-size: 0.9rem; }
        th, td { text-align: center; vertical-align: middle; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <h3><i class="fas fa-chart-bar"></i> Laporan Bulanan Absensi Tahun <?= $year ?></h3>
    <a href="dashboard.php" class="btn btn-secondary mb-3">← Kembali ke Dashboard</a>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tahun</label>
                    <select name="year" class="form-select" onchange="this.form.submit()">
                        <?php for($y = 2024; $y <= 2027; $y++): ?>
                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kelas</label>
                    <select name="kelas_nama" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Kelas</option>
                        <?php foreach($kelas_list as $k): ?>
                        <option value="<?= htmlspecialchars($k['nama_kelas']) ?>" 
                                <?= $k['nama_kelas'] == $kelas_nama ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_kelas']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-success">
                    <tr>
                        <th>Nama</th>
                        <th>Jenis Presensi</th>
                        <th>Kelas</th>
                        <th>Jan</th>
                        <th>Feb</th>
                        <th>Mar</th>
                        <th>Apr</th>
                        <th>Mei</th>
                        <th>Jun</th>
                        <th>Jul</th>
                        <th>Agu</th>
                        <th>Sep</th>
                        <th>Okt</th>
                        <th>Nov</th>
                        <th>Des</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($laporan)): ?>
                    <tr><td colspan="16" class="text-center">Tidak ada data untuk filter tersebut.</td></tr>
                    <?php else: ?>
                        <?php foreach($laporan as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                            <td><?= htmlspecialchars($row['jenis_presensi']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                            <td><?= $row['jan'] ?></td>
                            <td><?= $row['feb'] ?></td>
                            <td><?= $row['mar'] ?></td>
                            <td><?= $row['apr'] ?></td>
                            <td><?= $row['mei'] ?></td>
                            <td><?= $row['jun'] ?></td>
                            <td><?= $row['jul'] ?></td>
                            <td><?= $row['agu'] ?></td>
                            <td><?= $row['sep'] ?></td>
                            <td><?= $row['okt'] ?></td>
                            <td><?= $row['nov'] ?></td>
                            <td><?= $row['des'] ?></td>
                            <td class="fw-bold"><?= $row['total'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
