<?php
require_once 'config.php';

header('Content-Type: application/json');

$kecamatan = isset($_GET['kecamatan']) ? escape($_GET['kecamatan']) : '';

if (empty($kecamatan)) {
    echo json_encode([]);
    exit;
}

$query = "SELECT DISTINCT s.nama_desa 
          FROM sekolah s 
          INNER JOIN kecamatan k ON s.id_kecamatan = k.id_kecamatan 
          WHERE k.nama_kecamatan = '$kecamatan' 
          AND s.nama_desa IS NOT NULL
          ORDER BY s.nama_desa";
$results = fetch_all($query);

echo json_encode($results);
?>