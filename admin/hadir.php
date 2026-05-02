<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../config.php';
require_once '../functions.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT s.nis, s.nama_siswa, k.nama_kelas, a.jam_datang 
                       FROM absensi a 
                       JOIN siswa s ON a.siswa_id = s.id 
                       LEFT JOIN kelas k ON s.kelas_id = k.id 
                       WHERE a.tanggal = ? AND a.status = 'Hadir' 
                       ORDER BY a.jam_datang ASC");
$stmt->execute([$today]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Hadir Hari Ini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        th { cursor: pointer; user-select: none; }
        th:hover { background-color: #f8f9fa; }
        .sort-asc::after { content: " ▲"; }
        .sort-desc::after { content: " ▼"; }
    </style>
</head>
<body class="bg-light p-3">
    <h3>Siswa Hadir Hari Ini (<?= date('d M Y') ?>)</h3>
    <a href="dashboard.php" class="btn btn-secondary mb-3">← Kembali</a>
    <div class="card">
        <div class="card-body">
            <?php if (empty($data)): ?>
                <div class="alert alert-info text-center">Belum ada siswa hadir hari ini.</div>
            <?php else: ?>
                <table class="table table-bordered table-hover" id="dataTable">
                    <thead class="table-success">
                        <tr>
                            <th onclick="sortTable(0)">NIS</th>
                            <th onclick="sortTable(1)">Nama Siswa</th>
                            <th onclick="sortTable(2)">Kelas</th>
                            <th onclick="sortTable(3)">Jam Datang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nis']) ?></td>
                            <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['jam_datang']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
<script>
// Sorting
let sortDir = {};
function sortTable(n) {
    const table = document.getElementById("dataTable");
    const tbody = table.tBodies[0];
    const rows = Array.from(tbody.rows);
    sortDir[n] = !sortDir[n];
    const asc = sortDir[n];
    rows.sort((a,b) => {
        let x = a.cells[n].textContent.trim();
        let y = b.cells[n].textContent.trim();
        if (!isNaN(x) && !isNaN(y)) return asc ? x-y : y-x;
        return asc ? x.localeCompare(y) : y.localeCompare(x);
    });
    rows.forEach(r => tbody.appendChild(r));
}
</script>
</body>
</html>