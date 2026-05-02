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
    $stmt = $pdo->prepare("DELETE FROM pengajuan WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>alert('Pengajuan berhasil dihapus!'); window.location='pengajuan.php';</script>";
    exit;
}

// Proses Update Status (Approve / Reject)
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];
  
    if (in_array($status, ['Approved', 'Rejected'])) {
        // Ambil data pengajuan
        $stmt = $pdo->prepare("SELECT * FROM pengajuan WHERE id = ?");
        $stmt->execute([$id]);
        $pengajuan = $stmt->fetch();
        if ($pengajuan) {
            // Update status pengajuan
            $stmt = $pdo->prepare("UPDATE pengajuan SET status = ?, approved_by = ? WHERE id = ?");
            $stmt->execute([$status, $_SESSION['admin_id'], $id]);

            // ==================== JIKA APPROVED → MASUKKAN KE ABSENSI ====================
            if ($status === 'Approved') {
                $siswa_id = $pengajuan['siswa_id'];
                $tanggal = $pengajuan['tanggal'];
                $jenis = $pengajuan['jenis'];

                $cek = $pdo->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND tanggal = ?");
                $cek->execute([$siswa_id, $tanggal]);
                if ($cek->rowCount() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO absensi
                        (siswa_id, tanggal, status, jam_datang, jam_pulang, keterangan)
                        VALUES (?, ?, ?, NULL, NULL, ?)");
                    $stmt->execute([$siswa_id, $tanggal, $jenis, $pengajuan['keterangan']]);
                } else {
                    $stmt = $pdo->prepare("UPDATE absensi SET status = ?, keterangan = ?
                                           WHERE siswa_id = ? AND tanggal = ?");
                    $stmt->execute([$jenis, $pengajuan['keterangan'], $siswa_id, $tanggal]);
                }
            }
            // ============================================================================
            echo "<script>alert('Status pengajuan berhasil diupdate!'); window.location='pengajuan.php';</script>";
            exit;
        }
    }
}

// ==================== SORTING ====================
$sort  = $_GET['sort'] ?? 'tanggal';
$order = $_GET['order'] ?? 'DESC';

$allowed_sort = ['tanggal', 'nama_siswa', 'nama_kelas', 'jenis', 'status'];
if (!in_array($sort, $allowed_sort)) $sort = 'tanggal';
if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';

// Ambil semua pengajuan dengan sorting
$sql = "SELECT p.*, s.nama_siswa, s.nis, k.nama_kelas
        FROM pengajuan p
        JOIN siswa s ON p.siswa_id = s.id
        LEFT JOIN kelas k ON s.kelas_id = k.id
        ORDER BY ";

switch($sort) {
    case 'nama_siswa': $sql .= "s.nama_siswa $order"; break;
    case 'nama_kelas': $sql .= "k.nama_kelas $order, s.nama_siswa ASC"; break;
    case 'jenis':      $sql .= "p.jenis $order"; break;
    case 'status':     $sql .= "p.status $order, p.tanggal DESC"; break;
    default:           $sql .= "p.tanggal $order, p.id DESC"; break;
}

$pengajuan = $pdo->query($sql)->fetchAll();
// ===============================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Izin / Sakit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-envelope"></i> Pengajuan Izin / Sakit</h3>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>
                            <a href="?sort=tanggal&order=<?= $sort=='tanggal' && $order=='DESC' ? 'ASC' : 'DESC' ?>" class="text-white text-decoration-none">
                                Tanggal <?= $sort=='tanggal' ? ($order=='DESC' ? '▼' : '▲') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=nama_siswa&order=<?= $sort=='nama_siswa' && $order=='DESC' ? 'ASC' : 'DESC' ?>" class="text-white text-decoration-none">
                                Siswa <?= $sort=='nama_siswa' ? ($order=='DESC' ? '▼' : '▲') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=nama_kelas&order=<?= $sort=='nama_kelas' && $order=='DESC' ? 'ASC' : 'DESC' ?>" class="text-white text-decoration-none">
                                Kelas <?= $sort=='nama_kelas' ? ($order=='DESC' ? '▼' : '▲') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=jenis&order=<?= $sort=='jenis' && $order=='DESC' ? 'ASC' : 'DESC' ?>" class="text-white text-decoration-none">
                                Jenis <?= $sort=='jenis' ? ($order=='DESC' ? '▼' : '▲') : '' ?>
                            </a>
                        </th>
                        <th>Keterangan</th>
                        <th>Bukti</th>
                        <th>
                            <a href="?sort=status&order=<?= $sort=='status' && $order=='DESC' ? 'ASC' : 'DESC' ?>" class="text-white text-decoration-none">
                                Status <?= $sort=='status' ? ($order=='DESC' ? '▼' : '▲') : '' ?>
                            </a>
                        </th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($pengajuan)): ?>
                    <tr><td colspan="8" class="text-center py-4">Belum ada pengajuan</td></tr>
                    <?php else: ?>
                        <?php foreach($pengajuan as $p): ?>
                        <tr>
                            <td><?= $p['tanggal'] ?></td>
                            <td><?= htmlspecialchars($p['nama_siswa']) ?><br><small><?= $p['nis'] ?></small></td>
                            <td><?= htmlspecialchars($p['nama_kelas'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= $p['jenis']=='Sakit'?'info':'warning' ?>"><?= $p['jenis'] ?></span></td>
                            <td><?= htmlspecialchars($p['keterangan']) ?></td>
                            <td>
                                <?php if($p['bukti']): ?>
                                    <a href="../assist/<?= htmlspecialchars($p['bukti']) ?>" target="_blank" class="text-primary">Lihat</a>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if($p['status']=='Approved') echo '<span class="badge bg-success">✅ Disetujui</span>';
                                elseif($p['status']=='Rejected') echo '<span class="badge bg-danger">❌ Ditolak</span>';
                                else echo '<span class="badge bg-warning">⏳ Pending</span>';
                                ?>
                            </td>
                            <td>
                                <?php if($p['status'] == 'Pending'): ?>
                                    <a href="?id=<?= $p['id'] ?>&status=Approved" class="btn btn-sm btn-success" onclick="return confirm('Setujui pengajuan ini?')">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="?id=<?= $p['id'] ?>&status=Rejected" class="btn btn-sm btn-danger" onclick="return confirm('Tolak pengajuan ini?')">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="edit_pengajuan.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?hapus=<?= $p['id'] ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Hapus pengajuan ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
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