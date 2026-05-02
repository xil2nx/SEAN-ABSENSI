<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT s.nis, s.nama_siswa, k.nama_kelas, s.device_id 
                       FROM siswa s 
                       LEFT JOIN kelas k ON s.kelas_id = k.id 
                       WHERE s.device_id IS NOT NULL AND s.device_id != '' 
                       ORDER BY s.nama_siswa ASC");
$stmt->execute();
$siswa_device = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siswa dengan Device ID</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        th { cursor: pointer; user-select: none; }
        th:hover { background-color: #f8f9fa; }
        .sort-asc::after { content: " ▲"; }
        .sort-desc::after { content: " ▼"; }
    </style>
</head>
<body class="bg-light p-3">
    <h3 class="mb-3">Siswa yang Sudah Memiliki Device ID</h3>
    <a href="dashboard.php" class="btn btn-secondary mb-3">← Kembali ke Dashboard</a>

    <div class="card">
        <div class="card-body">
            <?php if (empty($siswa_device)): ?>
                <div class="alert alert-info text-center">
                    Belum ada siswa yang terdaftar dengan Device ID.
                </div>
            <?php else: ?>
                <table class="table table-bordered table-hover" id="dataTable">
                    <thead class="table-dark">
                        <tr>
                            <th onclick="sortTable(0, this)">NIS</th>
                            <th onclick="sortTable(1, this)">Nama Siswa</th>
                            <th onclick="sortTable(2, this)">Kelas</th>
                            <th onclick="sortTable(3, this)">Device ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($siswa_device as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nis']) ?></td>
                            <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                            <td><code><?= htmlspecialchars($row['device_id']) ?></code></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

<script>
// Sorting yang lebih baik
let sortDirection = {};

function sortTable(colIndex, th) {
    const table = document.getElementById("dataTable");
    const tbody = table.tBodies[0];
    const rows = Array.from(tbody.rows);
    
    // Toggle direction
    sortDirection[colIndex] = !sortDirection[colIndex];
    const asc = sortDirection[colIndex];
    
    // Reset all headers
    document.querySelectorAll('th').forEach(header => {
        header.classList.remove('sort-asc', 'sort-desc');
    });
    th.classList.add(asc ? 'sort-asc' : 'sort-desc');
    
    rows.sort((a, b) => {
        let valA = a.cells[colIndex].textContent.trim();
        let valB = b.cells[colIndex].textContent.trim();
        
        // Jika berisi angka (NIS)
        if (!isNaN(valA) && !isNaN(valB)) {
            return asc ? valA - valB : valB - valA;
        }
        
        // Teks biasa
        return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
    });
    
    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
}
</script>
</body>
</html>