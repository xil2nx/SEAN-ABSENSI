<?php
// ================================================
// PROSES_ABSEN.PHP - VERSI OPTIMAL
// ================================================
error_reporting(E_ALL);
ini_set('display_errors', 0); // Matikan di production

require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: text/plain; charset=utf-8');

// Validasi Session
if (!isset($_SESSION['siswa_id'])) {
    die("Akses ditolak. Silakan login kembali.");
}

$siswa_id = $_SESSION['siswa_id'];
$type     = $_POST['type'] ?? '';
$lat      = $_POST['latitude'] ?? '';
$lng      = $_POST['longitude'] ?? '';

if (empty($type) || empty($lat) || empty($lng)) {
    die("Data tidak lengkap. Pastikan GPS aktif dan izinkan lokasi.");
}

// Ambil Setting Sekali Saja
$setting = $pdo->query("SELECT jam_masuk, jam_maksimal_masuk, radius_meter FROM settings WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$jam_masuk     = $setting['jam_masuk'] ?? '07:00:00';
$jam_maksimal  = $setting['jam_maksimal_masuk'] ?? '08:00:00';
$radius_server = $setting['radius_meter'] ?? 100;

$today = date('Y-m-d');
$now   = date('H:i:s');

try {
    $pdo->beginTransaction();

    if ($type === 'datang') {
        // Cek duplikat
        $cek = $pdo->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND tanggal = ?");
        $cek->execute([$siswa_id, $today]);
        if ($cek->rowCount() > 0) {
            throw new Exception("Anda sudah absen datang hari ini.");
        }

        $status = (strtotime($now) <= strtotime($jam_masuk)) ? 'Hadir' : 'Kesiangan';

        $stmt = $pdo->prepare("INSERT INTO absensi 
            (siswa_id, tanggal, jam_datang, latitude, longitude, status) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$siswa_id, $today, $now, $lat, $lng, $status]);

        echo "Absen datang berhasil!";

    } 
    elseif ($type === 'pulang') {
        $cek = $pdo->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND tanggal = ? AND jam_datang IS NOT NULL AND jam_pulang IS NULL");
        $cek->execute([$siswa_id, $today]);
        $data = $cek->fetch();

        if (!$data) {
            throw new Exception("Belum absen datang atau sudah absen pulang.");
        }

        $stmt = $pdo->prepare("UPDATE absensi 
            SET jam_pulang = ?, latitude = ?, longitude = ? 
            WHERE id = ?");
        $stmt->execute([$now, $lat, $lng, $data['id']]);

        echo "Absen pulang berhasil!";
    } 
    else {
        throw new Exception("Tipe absen tidak valid.");
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    echo $e->getMessage();
}
?>