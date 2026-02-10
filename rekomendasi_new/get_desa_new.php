<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Ambil parameter kecamatan dari query string
$kecamatan = isset($_GET['kecamatan']) ? escape(trim($_GET['kecamatan'])) : '';

// Validasi input
if (empty($kecamatan)) {
    echo json_encode([]);
    exit;
}

// Query desa dari tabel desa_new berdasarkan kecamatan
// Ambil desa unik untuk menghindari duplikat
$query = "SELECT DISTINCT desa FROM desa_new 
          WHERE kecamatan = '" . $kecamatan . "' 
          AND desa IS NOT NULL
          AND TRIM(desa) <> ''
          ORDER BY desa";

$result = fetch_all($query);

// Transform hasil menjadi array dengan format yang diharapkan JavaScript
$desa_list = [];
foreach ($result as $row) {
    $desa_list[] = [
        'desa' => $row['desa'] ?? '',
        'nama_desa' => $row['desa'] ?? ''  // backward compatibility
    ];
}

echo json_encode($desa_list);
exit;
?>
