<?php
require_once '../config.php';
require_once '../functions.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
// Proses Hapus
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM absensi WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>alert('Data absensi berhasil dihapus!'); window.location='absensi.php';</script>";
    exit;
}
// ==================== SORTING ====================
$allowed_sort = ['tanggal', 'nis', 'nama_siswa', 'nama_kelas', 'jam_datang', 'jam_pulang', 'status', 'jarak_meter'];
$sort = in_array($_GET['sort'] ?? '', $allowed_sort) ? $_GET['sort'] : 'tanggal';
$order = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$next_order = ($order === 'ASC') ? 'DESC' : 'ASC';
// Filter
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-d', strtotime('-30 days'));
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$kelas_id = $_GET['kelas_id'] ?? '';
$cari = $_GET['cari'] ?? '';
$status_filter = $_GET['status'] ?? '';
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
if ($status_filter === 'Kesiangan') {
    $where .= " AND (a.status = 'Terlambat' OR (a.status = 'Hadir' AND a.jam_datang > st.jam_maksimal_masuk))";
} elseif ($status_filter) {
    $where .= " AND a.status = ?";
    $params[] = $status_filter;
}
$sql = "SELECT a.*, s.nis, s.nama_siswa, k.nama_kelas, st.jam_maksimal_masuk
        FROM absensi a
        JOIN siswa s ON a.siswa_id = s.id
        LEFT JOIN kelas k ON s.kelas_id = k.id
        LEFT JOIN settings st ON st.id = 1
        $where
        ORDER BY $sort $order";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$absensi = $stmt->fetchAll();
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
    <style>
        th a { color: white !important; text-decoration: none; }
        th a:hover { text-decoration: underline; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-list-check"></i> Laporan Absensi</h3>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label>Tanggal Awal</label>
                    <input type="date" name="tanggal_awal" class="form-control" value="<?= $tanggal_awal ?>">
                </div>
                <div class="col-md-2">
                    <label>Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" class="form-control" value="<?= $tanggal_akhir ?>">
                </div>
                <div class="col-md-2">
                    <label>Kelas</label>
                    <select name="kelas_id" class="form-select">
                        <option value="">Semua Kelas</option>
                        <?php foreach($kelas_list as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $k['id']==$kelas_id?'selected':'' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="Hadir" <?= $status_filter=='Hadir'?'selected':'' ?>>Hadir</option>
                        <option value="Kesiangan" <?= $status_filter=='Kesiangan'?'selected':'' ?>>Kesiangan</option>
                        <option value="Izin" <?= $status_filter=='Izin'?'selected':'' ?>>Izin</option>
                        <option value="Sakit" <?= $status_filter=='Sakit'?'selected':'' ?>>Sakit</option>
                        <option value="Alpa" <?= $status_filter=='Alpa'?'selected':'' ?>>Alpa</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Cari Siswa</label>
                    <input type="text" name="cari" class="form-control" placeholder="Nama atau NIS" value="<?= htmlspecialchars($cari) ?>">
                </div>
                <input type="hidden" name="sort" value="<?= $sort ?>">
                <input type="hidden" name="order" value="<?= $order ?>">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                    <a href="absensi.php" class="btn btn-secondary">Reset Filter</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th><a href="?sort=tanggal&order=<?= $next_order ?>&tanggal_awal=<?= urlencode($tanggal_awal) ?>&tanggal_akhir=<?= urlencode($tanggal_akhir) ?>&kelas_id=<?= $kelas_id ?>&cari=<?= urlencode($cari) ?>&status=<?= $status_filter ?>">Tanggal <?= $sort=='tanggal' ? ($order=='ASC'?'↑':'↓') : '' ?></a></th>
                    <th><a href="?sort=nis&order=<?= $next_order ?>&tanggal_awal=<?= urlencode($tanggal_awal) ?>&tanggal_akhir=<?= urlencode($tanggal_akhir) ?>&kelas_id=<?= $kelas_id ?>&cari=<?= urlencode($cari) ?>&status=<?= $status_filter ?>">NIS <?= $sort=='nis' ? ($order=='ASC'?'↑':'↓') : '' ?></a></th>
                    <th><a href="?sort=nama_siswa&order=<?= $next_order ?>&tanggal_awal=<?= urlencode($tanggal_awal) ?>&tanggal_akhir=<?= urlencode($tanggal_akhir) ?>&kelas_id=<?= $kelas_id ?>&cari=<?= urlencode($cari) ?>&status=<?= $status_filter ?>">Nama Siswa <?= $sort=='nama_siswa' ? ($order=='ASC'?'↑':'↓') : '' ?></a></th>
                    <th><a href="?sort=nama_kelas&order=<?= $next_order ?>&tanggal_awal=<?= urlencode($tanggal_awal) ?>&tanggal_akhir=<?= urlencode($tanggal_akhir) ?>&kelas_id=<?= $kelas_id ?>&cari=<?= urlencode($cari) ?>&status=<?= $status_filter ?>">Kelas <?= $sort=='nama_kelas' ? ($order=='ASC'?'↑':'↓') : '' ?></a></th>
                    <th><a href="?sort=jam_datang&order=<?= $next_order ?>&tanggal_awal=<?= urlencode($tanggal_awal) ?>&tanggal_akhir=<?= urlencode($tanggal_akhir) ?>&kelas_id=<?= $kelas_id ?>&cari=<?= urlencode($cari) ?>&status=<?= $status_filter ?>">Jam Datang <?= $sort=='jam_datang' ? ($order=='ASC'?'↑':'↓') : '' ?></a></th>
                    <th><a href="?sort=jam_pulang&order=<?= $next_order ?>&tanggal_awal=<?= urlencode($tanggal_awal) ?>&tanggal_akhir=<?= urlencode($tanggal_akhir) ?>&kelas_id=<?= $kelas_id ?>&cari=<?= urlencode($cari) ?>&status=<?= $status_filter ?>">Jam Pulang <?= $sort=='jam_pulang' ? ($order=='ASC'?'↑':'↓') : '' ?></a></th>
                    <th><a href="?sort=status&order=<?= $next_order ?>&tanggal_awal=<?= urlencode($tanggal_awal) ?>&tanggal_akhir=<?= urlencode($tanggal_akhir) ?>&kelas_id=<?= $kelas_id ?>&cari=<?= urlencode($cari) ?>&status=<?= $status_filter ?>">Status <?= $sort=='status' ? ($order=='ASC'?'↑':'↓') : '' ?></a></th>
                    <th><a href="?sort=jarak_meter&order=<?= $next_order ?>&tanggal_awal=<?= urlencode($tanggal_awal) ?>&tanggal_akhir=<?= urlencode($tanggal_akhir) ?>&kelas_id=<?= $kelas_id ?>&cari=<?= urlencode($cari) ?>&status=<?= $status_filter ?>">Jarak (m) <?= $sort=='jarak_meter' ? ($order=='ASC'?'↑':'↓') : '' ?></a></th>
                    <th>Jarak Datang</th>
                    <th>Jarak Pulang</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($absensi)): ?>
                <tr><td colspan="11" class="text-center py-4">Tidak ada data absensi</td></tr>
                <?php else: ?>
                    <?php foreach($absensi as $a):
                        $status_display = $a['status'];
                        $badge_color = 'danger';
                        if ($a['status'] == 'Hadir') $badge_color = 'success';
                        elseif ($a['status'] == 'Kesiangan' || ($a['jam_datang'] && $a['jam_maksimal_masuk'] && $a['jam_datang'] > $a['jam_maksimal_masuk'])) {
                            $status_display = 'Kesiangan';
                            $badge_color = 'warning';
                        }
                        elseif ($a['status'] == 'Izin' || $a['status'] == 'Sakit') $badge_color = 'info';
                    ?>
                    <tr>
                        <td><?= $a['tanggal'] ?></td>
                        <td><?= htmlspecialchars($a['nis']) ?></td>
                        <td><?= htmlspecialchars($a['nama_siswa']) ?></td>
                        <td><?= htmlspecialchars($a['nama_kelas'] ?? '-') ?></td>
                        <td><?= $a['jam_datang'] ?? '-' ?></td>
                        <td><?= $a['jam_pulang'] ?? '-' ?></td>
                        <td><span class="badge bg-<?= $badge_color ?>"><?= htmlspecialchars($status_display) ?></span></td>
                        <td><?= number_format($a['jarak_meter'] ?? 0, 0) ?></td>
                        
                        <!-- Jarak Datang & Pulang (Langsung tampil teks) -->
                        <td><?= !empty($a['foto_datang']) ? htmlspecialchars($a['foto_datang']) : '-' ?></td>
                        <td><?= !empty($a['foto_pulang']) ? htmlspecialchars($a['foto_pulang']) : '-' ?></td>
                        
                        <td>
                            <a href="edit_absensi.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                            <a href="?hapus=<?= $a['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>