<?php
require_once '../config.php';
require_once '../functions.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->execute([$id]);
$siswa = $stmt->fetch();

if (!$siswa) {
    die("Siswa tidak ditemukan!");
}

$kelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis           = trim($_POST['nis']);
    $nama_siswa    = trim($_POST['nama_siswa']);
    $kelas_id      = $_POST['kelas_id'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $password_baru = $_POST['password'];

    // Update data utama
    $sql = "UPDATE siswa SET nis=?, nama_siswa=?, kelas_id=?, tanggal_lahir=?";
    $params = [$nis, $nama_siswa, $kelas_id, $tanggal_lahir];

    // Jika password diisi, update password
    if (!empty($password_baru)) {
        $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
        $sql .= ", password=?";
        $params[] = $hashed_password;
    }

    $sql .= " WHERE id=?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo "<script>alert('✅ Data siswa berhasil diperbarui!'); window.location='siswa.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card mx-auto" style="max-width: 650px;">
        <div class="card-header bg-primary text-white">
            <h5>Edit Siswa - <?= htmlspecialchars($siswa['nama_siswa']) ?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">NIS</label>
                        <input type="text" name="nis" class="form-control" value="<?= htmlspecialchars($siswa['nis']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kelas</label>
                        <select name="kelas_id" class="form-select" required>
                            <option value="">Pilih Kelas</option>
                            <?php foreach($kelas as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $k['id'] == $siswa['kelas_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Siswa</label>
                    <input type="text" name="nama_siswa" class="form-control" value="<?= htmlspecialchars($siswa['nama_siswa']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" class="form-control" value="<?= $siswa['tanggal_lahir'] ?>">
                </div>

                <hr>
                <div class="mb-3">
                    <label class="form-label fw-bold">Password Baru <small class="text-muted">(Kosongkan jika tidak ingin mengubah)</small></label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password baru">
                    <small class="text-muted">Minimal 6 karakter</small>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3">
                    <i class="fas fa-save"></i> SIMPAN PERUBAHAN
                </button>
            </form>
        </div>
        <div class="card-footer text-center">
            <a href="siswa.php" class="btn btn-secondary">Batal</a>
        </div>
    </div>
</div>
</body>
</html>