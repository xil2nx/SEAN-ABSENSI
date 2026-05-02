<?php
// functions.php
require_once 'config.php';

function getSettings($pdo) {
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1 LIMIT 1");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getLogoUrl($pdo) {
    $settings = getSettings($pdo);
    $logo = $settings['logo'] ?? '';
    return !empty($logo) ? 'assist/' . $logo : 'https://via.placeholder.com/150x150?text=Logo+Sekolah';
}

function getSiswa($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // meter
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($R * $c, 2);
}
?>