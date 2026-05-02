<?php
require_once '../config.php';
require_once '../functions.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
// Filter
$kelas_id = $_GET['kelas_id'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'nama_siswa';
$allowed_sort = ['nis', 'nama_siswa', 'nama_kelas', 'device_id', 'tanggal_lahir'];
if (!in_array($sort, $allowed_sort)) $sort = 'nama_siswa';
$where = [];
$params = [];
if ($kelas_id) {
    $where[] = "s.kelas_id = ?";
    $params[] = $kelas_id;
}
if ($search) {
    $where[] = "(s.nis LIKE ? OR s.nama_siswa LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";
// ORDER BY
$order_by = "ORDER BY ";
if ($sort === 'nama_kelas') {
    $order_by .= "k.nama_kelas ASC, s.nama_siswa ASC";
} elseif ($sort === 'device_id') {
    $order_by .= "(s.device_id IS NOT NULL AND s.device_id != '') DESC, s.device_id ASC, s.nama_siswa ASC";
} else {
    $order_by .= "s.$sort ASC";
}
$sql = "SELECT s.*, k.nama_kelas
        FROM siswa s
        LEFT JOIN kelas k ON s.kelas_id = k.id
        $where_clause
        $order_by";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$siswa_list = $stmt->fetchAll();
$kelas_list = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-user-graduate"></i> Kelola Siswa</h3>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
    <!-- Tombol Tambah & Import -->
    <div class="mb-3">
        <a href="tambah_siswa.php" class="btn btn-success me-2">
            <i class="fas fa-plus"></i> Tambah Siswa
        </a>
        <a href="import_siswa.php" class="btn btn-primary">
            <i class="fas fa-file-import"></i> Import Siswa (CSV)
        </a>
        <button class="btn btn-danger" onclick="deleteSelected()">
            <i class="fas fa-trash"></i> Hapus yang Ditandai
        </button>
    </div>
    <!-- Filter + Search + Sort -->
    <div class="row mb-3">
        <div class="col-md-4">
            <form method="GET">
                <select name="kelas_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    <?php foreach($kelas_list as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $k['id']==$kelas_id?'selected':'' ?>>
                        <?= htmlspecialchars($k['nama_kelas']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="col-md-4">
            <form method="GET" class="d-flex">
                <input type="hidden" name="kelas_id" value="<?= htmlspecialchars($kelas_id) ?>">
                <input type="text" name="search" class="form-control me-2"
                       placeholder="Cari NIS atau Nama Siswa..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="col-md-4">
            <form method="GET" class="d-flex">
                <input type="hidden" name="kelas_id" value="<?= htmlspecialchars($kelas_id) ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="nama_siswa" <?= $sort=='nama_siswa'?'selected':'' ?>>Urut Nama Siswa</option>
                    <option value="nis" <?= $sort=='nis'?'selected':'' ?>>Urut NIS</option>
                    <option value="nama_kelas" <?= $sort=='nama_kelas'?'selected':'' ?>>Urut Kelas</option>
                    <option value="tanggal_lahir" <?= $sort=='tanggal_lahir'?'selected':'' ?>>Urut Tanggal Lahir</option>
                    <option value="device_id" <?= $sort=='device_id'?'selected':'' ?>>Urut Device ID (Punya di Atas)</option>
                </select>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped table-hover align-middle" id="siswaTable">
                <thead class="table-dark">
                    <tr>
                        <th><input type="checkbox" onclick="toggleAll(this)"></th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Tanggal Lahir</th>
                        <th>Device ID</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($siswa_list as $s): ?>
                    <tr>
                        <td><input type="checkbox" class="row-check" value="<?= $s['id'] ?>"></td>
                        <td><?= htmlspecialchars($s['nis']) ?></td>
                        <td><?= htmlspecialchars($s['nama_siswa']) ?></td>
                        <td><?= htmlspecialchars($s['nama_kelas'] ?? '-') ?></td>
                        <td><?= $s['tanggal_lahir'] ?? '-' ?></td>
                        <td>
                            <?php if(!empty($s['device_id'])): ?>
                                <span class="text-success">✓ <?= substr(htmlspecialchars($s['device_id']), 0, 15) ?>...</span>
                            <?php else: ?>
                                <em class="text-danger">Belum terdaftar</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_siswa.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="reset_device.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-info"
                               onclick="return confirm('Reset perangkat untuk <?= htmlspecialchars($s['nama_siswa']) ?>?')">
                                <i class="fas fa-redo"></i> Reset Device
                            </a>
                            <a href="delete_siswa.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Yakin hapus <?= htmlspecialchars($s['nama_siswa']) ?>?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Toggle semua checkbox
function toggleAll(source) {
    document.querySelectorAll('.row-check').forEach(checkbox => {
        checkbox.checked = source.checked;
    });
}

// Hapus yang ditandai
function deleteSelected() {
    const selected = Array.from(document.querySelectorAll('.row-check:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('Pilih minimal satu siswa untuk dihapus!');
        return;
    }
    if (confirm(`Yakin menghapus ${selected.length} siswa yang ditandai?`)) {
        window.location.href = `delete_selected.php?ids=${selected.join(',')}`;
    }
}
</script>
</body>
</html>