<?php
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Proses Hapus Kelas
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM kelas WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>alert('Kelas berhasil dihapus!'); window.location='kelas.php';</script>";
    exit;
}

// Proses Tambah Kelas
if (isset($_POST['tambah_kelas'])) {
    $nama_kelas = trim($_POST['nama_kelas']);
    if (!empty($nama_kelas)) {
        $stmt = $pdo->prepare("INSERT INTO kelas (nama_kelas) VALUES (?)");
        $stmt->execute([$nama_kelas]);
        echo "<script>alert('Kelas berhasil ditambahkan!'); window.location='kelas.php';</script>";
        exit;
    }
}

// Proses Edit Kelas
if (isset($_POST['edit_kelas'])) {
    $id = $_POST['id'];
    $nama_kelas = trim($_POST['nama_kelas']);
    if (!empty($nama_kelas)) {
        $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = ? WHERE id = ?");
        $stmt->execute([$nama_kelas, $id]);
        echo "<script>alert('Kelas berhasil diperbarui!'); window.location='kelas.php';</script>";
        exit;
    }
}

// Ambil semua kelas
$kelas_list = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-4">

    <!-- HEADER DENGAN TOMBOL KEMBALI DI ATAS -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-school"></i> Kelola Kelas</h3>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <!-- Tombol Tambah Kelas -->
    <div class="mb-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tambahModal">
            <i class="fas fa-plus"></i> Tambah Kelas Baru
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Nama Kelas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($kelas_list as $i => $k): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($k['nama_kelas']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                    data-bs-target="#editModal" 
                                    data-id="<?= $k['id'] ?>" 
                                    data-nama="<?= htmlspecialchars($k['nama_kelas']) ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="?hapus=<?= $k['id'] ?>" class="btn btn-sm btn-danger" 
                               onclick="return confirm('Yakin ingin menghapus kelas ini?')">
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

<!-- Modal Tambah -->
<div class="modal fade" id="tambahModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kelas Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="nama_kelas" class="form-control" placeholder="Nama Kelas (contoh: X IPA 1)" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_kelas" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="nama_kelas" id="edit_nama" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_kelas" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto fill edit modal
    const editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        document.getElementById('edit_id').value = button.getAttribute('data-id');
        document.getElementById('edit_nama').value = button.getAttribute('data-nama');
    });
</script>
</body>
</html>