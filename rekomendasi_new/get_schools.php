<?php
require_once 'config.php';

// Return all schools (basic fields) as JSON for remote requests
$schools = fetch_all("SELECT id_sekolah, nama_sekolah, alamat, nama_kecamatan, nama_desa, tingkat_pendidikan, latitude, longitude FROM sekolah ORDER BY nama_sekolah");

header('Content-Type: application/json; charset=utf-8');
echo json_encode($schools, JSON_UNESCAPED_UNICODE);
