<?php
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['siswa_id'])) {
    header("Location: index.php"); exit;
}

$siswa = getSiswa($pdo, $_SESSION['siswa_id']);
$today = date('Y-m-d');

$stmt = $pdo->prepare("SELECT * FROM absensi WHERE siswa_id=? AND tanggal=?");
$stmt->execute([$siswa['id'], $today]);
$absenToday = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi - <?= htmlspecialchars($siswa['nama_siswa']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/idb@7/build/umd.js"></script>
    <style>
        .connection-status { position:fixed; top:10px; right:10px; padding:8px 15px; border-radius:30px; font-weight:bold; z-index:9999; }
        .online { background:#28a745; color:white; }
        .offline { background:#dc3545; color:white; }
        .camera-box { max-width:420px; margin:auto; border-radius:15px; overflow:hidden; background:#000; }
    </style>
</head>
<body class="bg-light">

<div id="connStatus" class="connection-status offline">● OFFLINE</div>

<div class="container py-4 text-center">
    <h4><?= htmlspecialchars($siswa['nama_siswa']) ?></h4>
    <p class="text-muted"><?= date('l, d F Y') ?></p>

    <div class="camera-box mb-3">
        <video id="video" autoplay playsinline style="width:100%;"></video>
        <canvas id="canvas" style="display:none; width:100%;"></canvas>
    </div>

    <button class="btn btn-primary btn-lg w-100 mb-2" id="btnCapture">📸 Ambil Foto & Absen</button>
    <button class="btn btn-warning w-100" id="btnUlang" style="display:none;">Ulang Foto</button>
</div>

<script>
// ================= OFFLINE DATABASE =================
const dbPromise = idb.openDB('AbsensiSiswa', 1, {
    upgrade(db) {
        db.createObjectStore('pending', { keyPath: 'id', autoIncrement: true });
    }
});

// Update status koneksi
function updateStatus() {
    const el = document.getElementById('connStatus');
    if (navigator.onLine) {
        el.className = 'connection-status online';
        el.textContent = '● ONLINE';
    } else {
        el.className = 'connection-status offline';
        el.textContent = '● OFFLINE';
    }
}
window.addEventListener('online', updateStatus);
window.addEventListener('offline', updateStatus);
setInterval(updateStatus, 3000);
updateStatus();

// ================= CAMERA =================
let video = document.getElementById('video');
navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" }})
.then(s => video.srcObject = s);

document.getElementById('btnCapture').onclick = async () => {
    const canvas = document.getElementById('canvas');
    canvas.width = 800;
    canvas.height = (video.videoHeight / video.videoWidth) * 800;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

    const foto = canvas.toDataURL('image/jpeg', 0.7);

    navigator.geolocation.getCurrentPosition(async (pos) => {
        const data = {
            siswa_id: <?= $siswa['id'] ?>,
            type: '<?= $absenToday ? "pulang" : "datang" ?>',
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
            foto_base64: foto,
            tanggal: '<?= $today ?>',
            waktu: '<?= date('H:i:s') ?>'
        };

        const db = await dbPromise;
        await db.add('pending', data);

        alert('✅ Absen berhasil disimpan di HP!\nAkan otomatis terkirim saat online.');
        window.location.href = 'dashboard.php';
    }, () => alert('GPS harus aktif!'));
};

// ================= AUTO SYNC =================
async function syncPending() {
    if (!navigator.onLine) return;
    
    const db = await dbPromise;
    const pending = await db.getAll('pending');

    for (let item of pending) {
        try {
            const form = new FormData();
            Object.keys(item).forEach(k => form.append(k, item[k]));

            const res = await fetch('proses_absen_offline.php', {
                method: 'POST',
                body: form
            });

            if (res.ok) {
                await db.delete('pending', item.id);
            }
        } catch(e) {}
    }
}

// Sync otomatis
setInterval(syncPending, 5000);           // setiap 5 detik
window.addEventListener('online', syncPending);
</script>
</body>
</html>