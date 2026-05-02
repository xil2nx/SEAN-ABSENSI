<?php
require_once 'config.php';
require_once 'functions.php';
if (!isset($_SESSION['siswa_id'])) {
    header("Location: index.php");
    exit;
}
// ================= PROSES ABSEN =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswa_id = $_SESSION['siswa_id'];
    $type = $_POST['type'] ?? '';
    $lat = $_POST['latitude'] ?? '';
    $lng = $_POST['longitude'] ?? '';
 
    if (!$type || !$lat || !$lng) die("Data tidak lengkap.");
    $settings = getSettings($pdo);
    $today = date('Y-m-d');
    $now = date('H:i:s');
    $jarak = calculateDistance($lat, $lng, $settings['lat'], $settings['lng']);
    try {
        $pdo->beginTransaction();
        if ($type === 'datang') {
            $cek = $pdo->prepare("SELECT id FROM absensi WHERE siswa_id=? AND tanggal=?");
            $cek->execute([$siswa_id, $today]);
            if ($cek->rowCount() > 0) throw new Exception("Sudah absen datang hari ini.");
            $status = (strtotime($now) <= strtotime($settings['jam_masuk'])) ? 'Hadir' : 'Terlambat';
            $stmt = $pdo->prepare("INSERT INTO absensi (siswa_id, tanggal, jam_datang, latitude, longitude, status, foto_datang) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$siswa_id, $today, $now, $lat, $lng, $status, $jarak." meter"]);
            echo "✅ Absen DATANG berhasil!";
        } else {
            $cek = $pdo->prepare("SELECT id FROM absensi WHERE siswa_id=? AND tanggal=? AND jam_datang IS NOT NULL AND jam_pulang IS NULL");
            $cek->execute([$siswa_id, $today]);
            if (!$cek->fetch()) throw new Exception("Belum absen datang atau sudah pulang.");
            $stmt = $pdo->prepare("UPDATE absensi SET jam_pulang=?, latitude=?, longitude=?, foto_pulang=? WHERE siswa_id=? AND tanggal=? AND jam_pulang IS NULL");
            $stmt->execute([$now, $lat, $lng, $jarak." meter", $siswa_id, $today]);
            echo "✅ Absen PULANG berhasil!";
        }
        $pdo->commit();
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo $e->getMessage();
        exit;
    }
}
// ================= DATA =================
$siswa = getSiswa($pdo, $_SESSION['siswa_id']);
$settings = getSettings($pdo);
$today = date('Y-m-d');
$now = date('H:i:s');
$stmt = $pdo->prepare("SELECT * FROM absensi WHERE siswa_id = ? AND tanggal = ?");
$stmt->execute([$siswa['id'], $today]);
$absenToday = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT jenis FROM pengajuan WHERE siswa_id = ? AND tanggal = ? AND status = 'Approved' LIMIT 1");
$stmt->execute([$siswa['id'], $today]);
$status_izin = $stmt->fetchColumn();
$hari_ini = date('l');
$hari_map = ['Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'];
$is_libur = !empty($settings['hari_libur']) && in_array($hari_map[$hari_ini] ?? $hari_ini, explode(',', $settings['hari_libur']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Absensi - <?= htmlspecialchars($siswa['nama_siswa']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f8fbff; font-family: 'Segoe UI', sans-serif; }
        * { font-size: 0.85rem !important; line-height: 1.3 !important; }
        .header { background: white; padding: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .marquee {
            background: linear-gradient(90deg, #1e40af, #3b82f6);
            color: white;
            padding: 8px 0;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .logo-circle {
            width: 170px; height: 170px; margin: 15px auto;
            background: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            animation: pulse 2s infinite;
            cursor: pointer;
            position: relative;
        }
        .logo-circle img { width: 100px; height: 100px; object-fit: contain; border-radius: 50%; }
        .logo-circle.disabled { animation: none; filter: grayscale(80%); opacity: 0.65; cursor: not-allowed; }
        @keyframes pulse { 0%{transform:scale(1)} 50%{transform:scale(1.08)} 100%{transform:scale(1)} }
        .done-stamp {
            position: absolute; transform: rotate(-12deg);
            font-size: 1.8rem; font-weight: bold; color: #10b981;
            border: 6px solid #10b981; padding: 4px 20px; border-radius: 8px;
            background: rgba(255,255,255,0.95);
        }
        #liveClock { font-size: 2.2rem !important; }
    </style>
</head>
<body>
<!-- HEADER -->
<div class="header text-center">
    <?php if(!empty($settings['logo'])): ?>
        <img src="assist/<?= htmlspecialchars($settings['logo']) ?>" alt="Logo" style="max-height:60px; border-radius:12px;">
    <?php endif; ?>
    <h5 class="mt-1"><?= htmlspecialchars($settings['nama_lembaga'] ?? 'SMAN 1 Pelabuhan Ratu') ?></h5>
    <h6><?= htmlspecialchars($siswa['nama_siswa']) ?> (<?= htmlspecialchars($siswa['nis']) ?>)</h6>
</div>
<!-- MARQUEE PENGUMUMAN -->
<div class="marquee">
    <marquee behavior="scroll" direction="left" scrollamount="4">
        <i class="fas fa-bullhorn"></i>
        &nbsp; PENGUMUMAN :
        <span id="marqueeText"><?= htmlspecialchars($settings['marquee_text'] ?? 'Selamat datang di Smart Electronic Attendance Network (SEAN V.01) SMANSAPAL. Jangan lupa absen datang dan pulang dengan tepat waktu.') ?></span>
    </marquee>
</div>
<div class="container text-center">
    <!-- Tanggal & Jam -->
    <div class="my-2">
        <div id="fullDate" class="fw-bold text-primary"></div>
        <div id="liveClock" class="fw-bold text-dark"></div>
    </div>
    <!-- Jarak -->
    <div class="mb-2">
        <strong>Jarak Saat Ini: <span id="jarakSekarang" class="text-danger">— m</span></strong>
    </div>
    <!-- Lingkaran Tombol Utama -->
    <div class="logo-circle" id="absenCircle">
        <?php if(!empty($settings['logo'])): ?>
            <img src="assist/<?= htmlspecialchars($settings['logo']) ?>" alt="Logo">
        <?php endif; ?>
        <?php if ($absenToday && !empty($absenToday['jam_datang']) && !empty($absenToday['jam_pulang'])): ?>
            <div class="done-stamp">DONE</div>
        <?php endif; ?>
    </div>
    <h5 id="statusText" class="fw-bold mt-1">Menunggu GPS...</h5>
    <!-- Jam Masuk & Pulang -->
    <div class="row g-3 mt-3">
        <div class="col-6">
            <div class="bg-white rounded-3 shadow-sm p-3">
                <small class="text-muted">MASUK</small><br>
                <strong><?= substr($settings['jam_masuk'] ?? '07:00',0,5) ?></strong><br>
                <span class="text-success"><?= $absenToday['jam_datang'] ?? '—' ?></span>
            </div>
        </div>
        <div class="col-6">
            <div class="bg-white rounded-3 shadow-sm p-3">
                <small class="text-muted">PULANG</small><br>
                <strong><?= substr($settings['jam_pulang'] ?? '15:00',0,5) ?></strong><br>
                <span class="text-success"><?= $absenToday['jam_pulang'] ?? '—' ?></span>
            </div>
        </div>
    </div>
    <a href="ajukan_izin.php" class="btn btn-warning btn-lg w-100 mt-3">
        <i class="fas fa-file-alt"></i> AJUKAN IZIN / SAKIT
    </a>
    <div class="text-center mt-3">
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>
</div>
<script>
// Live Time
function updateClock() {
    const now = new Date();
    document.getElementById('fullDate').textContent = now.toLocaleDateString('id-ID', {weekday:'long', day:'numeric', month:'long', year:'numeric'}).toUpperCase();
    document.getElementById('liveClock').textContent = now.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
}
setInterval(updateClock, 1000);
updateClock();
// GPS
let currentLat = 0, currentLng = 0, gpsReady = false;
navigator.geolocation.watchPosition(pos => {
    currentLat = pos.coords.latitude;
    currentLng = pos.coords.longitude;
    gpsReady = true;
    const jarak = calculateDistance(currentLat, currentLng, <?= $settings['lat'] ?? 0 ?>, <?= $settings['lng'] ?? 0 ?>);
    document.getElementById('jarakSekarang').textContent = jarak + " m";
    document.getElementById('jarakSekarang').className = (jarak <= <?= $settings['radius_meter'] ?? 100 ?>) ? "text-success" : "text-danger";
}, () => {}, {enableHighAccuracy: true});
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2)*Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLon/2)*Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return Math.round(R * c * 100) / 100;
}
// LOGIKA TOMBOL
const circle = document.getElementById('absenCircle');
const statusText = document.getElementById('statusText');
const hasDatang = <?= $absenToday && !empty($absenToday['jam_datang']) ? 'true' : 'false' ?>;
const hasPulang = <?= $absenToday && !empty($absenToday['jam_pulang']) ? 'true' : 'false' ?>;
const jamSekarang = '<?= $now ?>';
const jamMasuk = '<?= $settings['jam_masuk'] ?? "07:00:00" ?>';
const jamPulang = '<?= $settings['jam_pulang'] ?? "15:00:00" ?>';
const jamMaksimalMasuk = '<?= $settings['jam_maksimal_masuk'] ?? "08:00:00" ?>';
function updateButtonStatus() {
    if (<?= $is_libur ? 'true' : 'false' ?>) {
        statusText.innerHTML = '<span class="text-primary">HARI LIBUR</span>';
        circle.classList.add('disabled');
    } else if ('<?= $status_izin ?>') {
        statusText.innerHTML = `<span class="text-info">ANDA <?= strtoupper($status_izin) ?> HARI INI</span>`;
        circle.classList.add('disabled');
    } else if (hasPulang) {
        statusText.innerHTML = '<span class="text-success">✅ SUDAH ABSEN PULANG</span>';
        circle.classList.add('disabled');
        // Tampilkan stiker DONE setelah pulang
        document.querySelector('.done-stamp') ? document.querySelector('.done-stamp').style.display = 'block' : null;
    } else if (hasDatang) {
        if (jamSekarang >= jamPulang) {
            statusText.innerHTML = 'Tekan untuk <strong>ABSEN PULANG</strong>';
            circle.classList.remove('disabled');
            // Hilangkan stiker DONE saat mau pulang
            if (document.querySelector('.done-stamp')) document.querySelector('.done-stamp').style.display = 'none';
        } else {
            statusText.innerHTML = '<span class="text-success">✅ Sudah Absen Datang</span>';
            circle.classList.add('disabled');
        }
    } else {
        if (jamSekarang >= '04:30:00' && jamSekarang <= jamMaksimalMasuk) {
            statusText.innerHTML = 'Tekan untuk <strong>ABSEN DATANG</strong>';
            circle.classList.remove('disabled');
        } else if (jamSekarang > jamMaksimalMasuk) {
            statusText.innerHTML = '<span class="text-danger">ANDA ALPA HARI INI</span>';
            circle.classList.add('disabled');
        } else {
            statusText.innerHTML = 'Belum waktu absen';
            circle.classList.add('disabled');
        }
    }
}
circle.onclick = function() {
    if (circle.classList.contains('disabled') || !gpsReady) {
        alert("Tombol belum aktif atau GPS belum siap.");
        return;
    }
    const jarak = calculateDistance(currentLat, currentLng, <?= $settings['lat'] ?? 0 ?>, <?= $settings['lng'] ?? 0 ?>);
    if (jarak > <?= $settings['radius_meter'] ?? 100 ?>) {
        return alert(`Jarak Anda ${jarak} m — Diluar radius absensi!`);
    }
    const type = hasDatang ? 'pulang' : 'datang';
    const formData = new FormData();
    formData.append('type', type);
    formData.append('latitude', currentLat);
    formData.append('longitude', currentLng);
    fetch('', { method: 'POST', body: formData })
    .then(r => r.text())
    .then(text => {
        alert(text);
        if (text.includes('berhasil')) location.reload();
    })
    .catch(() => alert('Gagal koneksi'));
};
updateButtonStatus();
</script>
</body>
</html>